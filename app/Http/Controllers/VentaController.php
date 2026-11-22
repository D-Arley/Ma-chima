<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\DetalleVenta;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function index(Request $request): View
    {
        $productos = Producto::with('categoria')
            ->where('activo', true)
            ->where('stock', '>', 0)
            ->orderBy('nombre')
            ->get();

        $caja = Caja::where('user_id', auth()->id())
            ->whereDate('fecha', today())
            ->where('status', 'abierta')
            ->first();

        $ventasHoy = Venta::with('detalles.producto')
            ->where('user_id', auth()->id())
            ->whereDate('fecha', today())
            ->latest('fecha')
            ->get();

        return view('ventas.index', compact('productos', 'caja', 'ventasHoy'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'metodo_pago' => ['required', 'in:efectivo,transferencia'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'efectivo_recibido' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'string'],
        ]);

        $items = json_decode($validated['items'], true);
        if (! is_array($items) || $items === []) {
            throw ValidationException::withMessages(['items' => 'El carrito está vacío.']);
        }

        foreach ($items as $item) {
            if (! is_array($item) ||
                ! isset($item['producto_id'], $item['cantidad']) ||
                ! is_numeric($item['producto_id']) ||
                ! is_numeric($item['cantidad']) ||
                (int) $item['cantidad'] < 1 ||
                (int) $item['cantidad'] > 999) {
                throw ValidationException::withMessages(['items' => 'Hay un artículo inválido en el carrito.']);
            }
        }

        $validated['items'] = collect($items)
            ->groupBy(fn (array $item) => (int) $item['producto_id'])
            ->map(fn ($productItems, $productId) => [
                'producto_id' => (int) $productId,
                'cantidad' => $productItems->sum(fn (array $item) => (int) $item['cantidad']),
            ])
            ->values()
            ->all();

        $caja = Caja::where('user_id', auth()->id())
            ->whereDate('fecha', today())
            ->where('status', 'abierta')
            ->first();

        if (! $caja) {
            throw ValidationException::withMessages(['items' => 'Debe abrir la caja antes de registrar una venta.']);
        }

        $venta = DB::transaction(function () use ($validated, $caja) {
            $caja = Caja::query()->lockForUpdate()->find($caja->id);

            if (! $caja || $caja->status !== 'abierta' || ! $caja->fecha->isToday()) {
                throw ValidationException::withMessages(['items' => 'La caja ya no está disponible para registrar ventas.']);
            }

            $subtotalBruto = 0;
            $costoTotal = 0;
            $detalles = [];

            foreach ($validated['items'] as $item) {
                $producto = Producto::whereKey($item['producto_id'])->lockForUpdate()->first();

                if (! $producto || ! $producto->activo) {
                    throw ValidationException::withMessages(['items' => 'Hay un producto no disponible en el carrito.']);
                }

                if ($producto->stock < $item['cantidad']) {
                    throw ValidationException::withMessages([
                        'items' => "No hay stock suficiente para {$producto->nombre}. Disponible: {$producto->stock}.",
                    ]);
                }

                $precioFinal = (float) $producto->precio;
                $cantidad = (int) $item['cantidad'];
                $subtotalBruto += $precioFinal * $cantidad;
                $costoTotal += (float) $producto->costo * $cantidad;
                $stockBefore = $producto->stock;

                $detalles[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioFinal,
                    'subtotal' => $precioFinal * $cantidad,
                    'stock_before' => $stockBefore,
                ];
            }

            $descuento = min((float) ($validated['descuento'] ?? 0), $subtotalBruto);
            $total = round($subtotalBruto - $descuento, 2);
            $subtotalNeto = round($total / 1.19, 2);
            $iva = round($total - $subtotalNeto, 2);
            $ganancia = round($subtotalNeto - $costoTotal, 2);

            if ($validated['metodo_pago'] === 'efectivo') {
                $recibido = (float) ($validated['efectivo_recibido'] ?? 0);
                if ($recibido < $total) {
                    throw ValidationException::withMessages(['efectivo_recibido' => 'El efectivo recibido es menor al total.']);
                }
                $cambio = round($recibido - $total, 2);
            } else {
                $recibido = null;
                $cambio = 0;
            }

            $comprobante = 'VEN-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            $venta = Venta::create([
                'user_id' => auth()->id(),
                'caja_id' => $caja->id,
                'comprobante' => $comprobante,
                'subtotal' => $subtotalNeto,
                'descuento' => $descuento,
                'iva' => $iva,
                'ganancia' => $ganancia,
                'total' => $total,
                'metodo_pago' => $validated['metodo_pago'],
                'efectivo_recibido' => $recibido,
                'cambio' => $cambio,
                'fecha' => now(),
            ]);

            foreach ($detalles as $detalle) {
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $detalle['producto']->id,
                    'producto_nombre' => $detalle['producto']->nombre,
                    'producto_codigo' => $detalle['producto']->codigo,
                    'producto_talla' => $detalle['producto']->talla,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['subtotal'],
                ]);

                $producto = $detalle['producto'];
                $stockAfter = $producto->stock - $detalle['cantidad'];
                $producto->decrement('stock', $detalle['cantidad']);

                InventoryMovement::create([
                    'user_id' => auth()->id(),
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_codigo' => $producto->codigo,
                    'tipo' => 'venta',
                    'cantidad' => -$detalle['cantidad'],
                    'stock_before' => $detalle['stock_before'],
                    'stock_after' => $stockAfter,
                    'costo_unitario' => $producto->costo,
                    'motivo' => 'Venta',
                    'descripcion' => "Salida por comprobante {$comprobante}.",
                    'referencia' => $comprobante,
                ]);
            }

            if ($validated['metodo_pago'] === 'efectivo') {
                $caja->update([
                    'cash_balance' => (float) $caja->cash_balance + $total,
                ]);
            } else {
                $caja->update([
                    'transfer_balance' => (float) $caja->transfer_balance + $total,
                    'bank_balance' => (float) $caja->bank_balance + $total,
                ]);
            }

            CajaMovimiento::create([
                'caja_id' => $caja->id,
                'user_id' => auth()->id(),
                'movable_type' => Venta::class,
                'movable_id' => $venta->id,
                'tipo' => $validated['metodo_pago'] === 'efectivo' ? 'venta_efectivo' : 'venta_transferencia',
                'amount' => $total,
                'cash_after' => $caja->cash_balance,
                'bank_after' => $caja->bank_balance,
                'descripcion' => "Venta {$comprobante} registrada.",
                'fecha' => now(),
            ]);

            return $venta;
        });

        return redirect()->route('ventas.show', $venta)->with('success', 'Venta registrada correctamente.');
    }

    public function show(Venta $venta): View
    {
        abort_unless($venta->user_id === auth()->id() || auth()->user()->isAdmin(), 403);
        $venta->load(['detalles.producto', 'user', 'caja']);

        return view('ventas.show', compact('venta'));
    }
}
