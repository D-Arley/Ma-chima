<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\DetalleVenta;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoSalesHistorySeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()->where('role', 'vendedor')->orderBy('id')->first();
        $products = Producto::query()
            ->where('activo', true)
            ->where('stock', '>', 0)
            ->orderBy('id')
            ->get();

        if (! $seller || $products->isEmpty()) {
            throw new RuntimeException('Se requiere al menos un vendedor y un producto activo con existencias para crear el historial de demostración.');
        }

        DB::transaction(function () use ($seller, $products) {
            $start = today()->subMonthsNoOverflow(2)->startOfDay();
            $end = today()->startOfDay();
            $pendingSales = collect();

            for ($dayNumber = 0, $saleDate = $start->copy(); $saleDate->lte($end); $dayNumber++, $saleDate->addDay()) {
                $comprobante = 'DEMO-'.$saleDate->format('Ymd');

                if (Venta::query()->where('comprobante', $comprobante)->exists()) {
                    continue;
                }

                if (Caja::query()->where('user_id', $seller->id)->whereDate('fecha', $saleDate)->exists()) {
                    continue;
                }

                $pendingSales->push([
                    'day_number' => $dayNumber,
                    'sale_date' => $saleDate->copy(),
                    'comprobante' => $comprobante,
                    'producto_id' => $products[$dayNumber % $products->count()]->id,
                ]);
            }

            if ($pendingSales->isEmpty()) {
                return;
            }

            $salesPerProduct = $pendingSales->countBy('producto_id');
            $stockBalances = [];

            foreach ($salesPerProduct as $productId => $saleCount) {
                $producto = $products->firstWhere('id', $productId);
                $firstExistingMovement = InventoryMovement::query()
                    ->where('producto_id', $productId)
                    ->where('created_at', '>=', $start)
                    ->oldest('created_at')
                    ->first();
                $stockBeforeDemo = (int) ($firstExistingMovement?->stock_before ?? $producto->stock);
                $stockBalances[$productId] = $stockBeforeDemo + $saleCount;
                $openingReference = 'DEMO-OPENING-'.$start->format('Ymd').'-'.$productId;

                if (! InventoryMovement::query()->where('referencia', $openingReference)->exists()) {
                    $openingMovement = InventoryMovement::create([
                        'user_id' => $seller->id,
                        'producto_id' => $productId,
                        'tipo' => 'entrada',
                        'cantidad' => $saleCount,
                        'stock_before' => $stockBeforeDemo,
                        'stock_after' => $stockBalances[$productId],
                        'costo_unitario' => $producto->costo,
                        'motivo' => 'Saldo inicial de demostración',
                        'descripcion' => '[DEMO] Saldo inicial para conciliar ventas históricas.',
                        'referencia' => $openingReference,
                    ]);
                    $openingMovement->created_at = $start->copy()->setTime(8, 0);
                    $openingMovement->updated_at = $start->copy()->setTime(8, 0);
                    $openingMovement->save();
                }
            }

            foreach ($pendingSales as $pendingSale) {
                $dayNumber = $pendingSale['day_number'];
                $saleDate = $pendingSale['sale_date'];
                $saleAt = $saleDate->copy()->setTime(10 + ($dayNumber % 8), 0);
                $comprobante = $pendingSale['comprobante'];
                $producto = $products->firstWhere('id', $pendingSale['producto_id']);

                if ($stockBalances[$producto->id] < 1) {
                    throw new RuntimeException("No hay existencias para el producto {$producto->nombre}; no se puede completar el historial de demostración.");
                }

                $caja = Caja::query()
                    ->where('user_id', $seller->id)
                    ->whereDate('fecha', $saleDate)
                    ->lockForUpdate()
                    ->first();

                if (! $caja) {
                    $isToday = $saleDate->isToday();
                    $openingAt = $saleAt->copy()->setTime(8, 0);
                    $caja = Caja::create([
                        'user_id' => $seller->id,
                        'fecha' => $saleDate,
                        'opening_cash' => 200000,
                        'cash_balance' => 200000,
                        'transfer_balance' => 0,
                        'bank_balance' => 0,
                        'status' => $isToday ? 'abierta' : 'cerrada',
                        'opened_at' => $openingAt,
                        'closed_at' => $isToday ? null : $saleAt->copy()->setTime(18, 0),
                    ]);

                    CajaMovimiento::create([
                        'caja_id' => $caja->id,
                        'user_id' => $seller->id,
                        'tipo' => 'apertura',
                        'amount' => 200000,
                        'cash_after' => 200000,
                        'bank_after' => 0,
                        'descripcion' => '[DEMO] Apertura de caja para la jornada.',
                        'fecha' => $openingAt,
                    ]);
                }

                $precio = (float) $producto->precio;
                $descuento = $dayNumber % 7 === 0 ? 5000 : 0;
                $total = round($precio - $descuento, 2);
                $subtotal = round($total / 1.19, 2);
                $iva = round($total - $subtotal, 2);
                $ganancia = round($subtotal - (float) $producto->costo, 2);
                $metodoPago = $dayNumber % 2 === 0 ? 'efectivo' : 'transferencia';

                $venta = Venta::create([
                    'user_id' => $seller->id,
                    'caja_id' => $caja->id,
                    'comprobante' => $comprobante,
                    'subtotal' => $subtotal,
                    'descuento' => $descuento,
                    'iva' => $iva,
                    'ganancia' => $ganancia,
                    'total' => $total,
                    'metodo_pago' => $metodoPago,
                    'efectivo_recibido' => $metodoPago === 'efectivo' ? $total : null,
                    'cambio' => 0,
                    'fecha' => $saleAt,
                ]);

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => 1,
                    'precio_unitario' => $precio,
                    'subtotal' => $precio,
                ]);

                $stockBefore = $stockBalances[$producto->id];
                $stockBalances[$producto->id]--;

                $movimiento = InventoryMovement::create([
                    'user_id' => $seller->id,
                    'producto_id' => $producto->id,
                    'tipo' => 'venta',
                    'cantidad' => -1,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBalances[$producto->id],
                    'costo_unitario' => $producto->costo,
                    'motivo' => 'Venta de demostración',
                    'descripcion' => "[DEMO] Salida por comprobante {$comprobante}.",
                    'referencia' => $comprobante,
                ]);
                $movimiento->created_at = $saleAt;
                $movimiento->updated_at = $saleAt;
                $movimiento->save();

                $cashBalance = (float) $caja->cash_balance + ($metodoPago === 'efectivo' ? $total : 0);
                $transferBalance = (float) $caja->transfer_balance + ($metodoPago === 'transferencia' ? $total : 0);
                $bankBalance = (float) $caja->bank_balance + ($metodoPago === 'transferencia' ? $total : 0);

                $caja->update([
                    'cash_balance' => $cashBalance,
                    'transfer_balance' => $transferBalance,
                    'bank_balance' => $bankBalance,
                ]);

                CajaMovimiento::create([
                    'caja_id' => $caja->id,
                    'user_id' => $seller->id,
                    'movable_type' => Venta::class,
                    'movable_id' => $venta->id,
                    'tipo' => $metodoPago === 'efectivo' ? 'venta_efectivo' : 'venta_transferencia',
                    'amount' => $total,
                    'cash_after' => $cashBalance,
                    'bank_after' => $bankBalance,
                    'descripcion' => "[DEMO] Venta {$comprobante} registrada.",
                    'fecha' => $saleAt,
                ]);
            }
        });
    }
}
