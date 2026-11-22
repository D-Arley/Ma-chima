<?php

namespace App\Http\Controllers;

use App\Models\InventoryCountRun;
use App\Models\InventoryCountRunItem;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canManageInventory = $user->isAdmin() || $user->hasRole('bodeguero');

        $transferencias = InventoryTransfer::with(['user', 'vendedor', 'items.producto'])
            ->when(! $canManageInventory, fn ($query) => $query->where('vendedor_id', $user->id))
            ->latest('entregado_at')
            ->paginate(12, ['*'], 'transferencias');

        $conteos = InventoryCountRun::with(['user', 'items'])
            ->when(! $canManageInventory, fn ($query) => $query->where('user_id', $user->id))
            ->latest('iniciado_at')
            ->paginate(12, ['*'], 'conteos');

        $productos = Producto::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo', 'stock']);
        $vendedores = $canManageInventory
            ? User::where('role', 'vendedor')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('logistica.index', compact(
            'canManageInventory',
            'conteos',
            'productos',
            'transferencias',
            'vendedores'
        ));
    }

    public function showCount(InventoryCountRun $countRun): View|RedirectResponse
    {
        $this->authorizeCountRun($countRun);

        $countRun->load(['items' => fn ($query) => $query->orderBy('producto_nombre')]);
        $countRunSummary = [
            'lost_units' => $countRun->items->sum(fn (InventoryCountRunItem $item) => max(0, -($item->diferencia ?? 0))),
            'added_units' => $countRun->items->sum(fn (InventoryCountRunItem $item) => max(0, $item->diferencia ?? 0)),
            'lost_value' => $countRun->items->sum(fn (InventoryCountRunItem $item) => max(0, -($item->diferencia ?? 0)) * (float) $item->costo_unitario),
            'added_value' => $countRun->items->sum(fn (InventoryCountRunItem $item) => max(0, $item->diferencia ?? 0) * (float) $item->costo_unitario),
        ];

        return view('logistica.count', compact('countRun', 'countRunSummary'));
    }

    public function startCount(): RedirectResponse
    {
        $countRun = DB::transaction(function (): InventoryCountRun {
            $productos = Producto::query()
                ->where('activo', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'nombre', 'codigo', 'stock', 'costo']);

            if ($productos->isEmpty()) {
                throw ValidationException::withMessages([
                    'conteo' => 'No hay productos activos para iniciar el conteo.',
                ]);
            }

            $lastMovementIds = InventoryMovement::query()
                ->whereIn('producto_id', $productos->modelKeys())
                ->selectRaw('producto_id, MAX(id) as ultimo_id')
                ->groupBy('producto_id')
                ->pluck('ultimo_id', 'producto_id');

            $run = InventoryCountRun::create([
                'user_id' => auth()->id(),
                'estado' => 'en_progreso',
                'iniciado_at' => now(),
            ]);

            foreach ($productos as $producto) {
                $run->items()->create([
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_codigo' => $producto->codigo,
                    'stock_sistema' => $producto->stock,
                    'costo_unitario' => $producto->costo,
                    'ultimo_movimiento_id' => $lastMovementIds[$producto->id] ?? null,
                ]);
            }

            return $run;
        });

        return redirect()->route('logistica.count-runs.show', $countRun);
    }

    public function finishCount(Request $request, InventoryCountRun $countRun): RedirectResponse
    {
        $this->authorizeCountRun($countRun);
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['required', 'integer', 'min:0', 'max:1000000'],
            'nota' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated, $countRun): void {
            $run = InventoryCountRun::query()->lockForUpdate()->findOrFail($countRun->id);

            if ($run->estado !== 'en_progreso') {
                throw ValidationException::withMessages([
                    'conteo' => 'Este conteo ya fue enviado o revisado.',
                ]);
            }

            $items = InventoryCountRunItem::query()
                ->where('inventory_count_run_id', $run->id)
                ->orderBy('producto_id')
                ->lockForUpdate()
                ->get();
            $submittedIds = array_map('intval', array_keys($validated['items']));
            $expectedIds = $items->pluck('producto_id')->map(fn ($id) => (int) $id)->all();

            sort($submittedIds);
            sort($expectedIds);

            if ($submittedIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'items' => 'Debe registrar una cantidad para cada producto del conteo.',
                ]);
            }

            $this->ensureCountSnapshotIsCurrent($items);

            foreach ($items as $item) {
                $quantity = (int) $validated['items'][$item->producto_id];
                $item->update([
                    'cantidad_contada' => $quantity,
                    'diferencia' => $quantity - $item->stock_sistema,
                ]);
            }

            $isInventoryManager = auth()->user()->isAdmin() || auth()->user()->hasRole('bodeguero');
            $run->update([
                'estado' => $isInventoryManager ? 'aprobado' : 'pendiente',
                'nota' => $validated['nota'] ?? null,
                'finalizado_at' => now(),
                'revisado_por' => $isInventoryManager ? auth()->id() : null,
                'revisado_at' => $isInventoryManager ? now() : null,
            ]);

            if ($isInventoryManager) {
                $this->applyCountAdjustments($run, $items);
            }
        });

        return redirect()->route('logistica.count-runs.show', $countRun)
            ->with('success', auth()->user()->isAdmin() || auth()->user()->hasRole('bodeguero')
                ? 'Conteo finalizado y existencias ajustadas.'
                : 'Conteo enviado a revisión; las existencias no cambian hasta su aprobación.');
    }

    public function deliver(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendedor_id' => ['required', 'integer', 'exists:users,id'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $transferencia = DB::transaction(function () use ($validated) {
            $vendedor = User::whereKey($validated['vendedor_id'])
                ->where('role', 'vendedor')
                ->first();

            if (! $vendedor) {
                throw ValidationException::withMessages([
                    'vendedor_id' => 'Seleccione un usuario con rol de vendedor.',
                ]);
            }

            $productos = [];
            foreach ($validated['items'] as $item) {
                $producto = Producto::whereKey($item['producto_id'])->lockForUpdate()->first();

                if (! $producto || ! $producto->activo) {
                    throw ValidationException::withMessages([
                        'items' => 'Hay un producto no disponible en la entrega.',
                    ]);
                }

                if ($producto->stock < $item['cantidad']) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock}.",
                    ]);
                }

                $productos[] = [
                    'producto' => $producto,
                    'cantidad' => (int) $item['cantidad'],
                ];
            }

            $transferencia = InventoryTransfer::create([
                'codigo' => 'TRA-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'user_id' => auth()->id(),
                'vendedor_id' => $vendedor->id,
                'estado' => 'pendiente',
                'descripcion' => $validated['descripcion'] ?? null,
                'entregado_at' => now(),
            ]);

            foreach ($productos as $item) {
                $producto = $item['producto'];
                $cantidad = $item['cantidad'];
                $stockBefore = $producto->stock;
                $stockAfter = $stockBefore - $cantidad;

                $transferencia->items()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                ]);

                $producto->decrement('stock', $cantidad);

                InventoryMovement::create([
                    'user_id' => auth()->id(),
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_codigo' => $producto->codigo,
                    'tipo' => 'entrega_vendedor',
                    'cantidad' => -$cantidad,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'costo_unitario' => $producto->costo,
                    'motivo' => 'Entrega a vendedor',
                    'descripcion' => "Entrega {$transferencia->codigo} pendiente de recepción por {$vendedor->name}.",
                    'referencia' => $transferencia->codigo,
                ]);
            }

            return $transferencia;
        });

        return back()->with('success', "Entrega {$transferencia->codigo} registrada; queda pendiente la recepción del vendedor.");
    }

    public function receive(InventoryTransfer $transferencia): RedirectResponse
    {
        abort_unless(
            auth()->user()->isAdmin() || $transferencia->vendedor_id === auth()->id(),
            403
        );

        DB::transaction(function () use ($transferencia) {
            $transferencia = InventoryTransfer::with('items.producto')
                ->lockForUpdate()
                ->findOrFail($transferencia->id);

            if ($transferencia->estado !== 'pendiente') {
                throw ValidationException::withMessages([
                    'transferencia' => 'Esta entrega ya fue recibida o no está disponible.',
                ]);
            }

            foreach ($transferencia->items as $item) {
                $producto = Producto::whereKey($item->producto_id)->lockForUpdate()->firstOrFail();
                $stockBefore = $producto->stock;
                $stockAfter = $stockBefore + $item->cantidad;

                $producto->increment('stock', $item->cantidad);

                InventoryMovement::create([
                    'user_id' => auth()->id(),
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_codigo' => $producto->codigo,
                    'tipo' => 'recepcion_vendedor',
                    'cantidad' => $item->cantidad,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'costo_unitario' => $producto->costo,
                    'motivo' => 'Recepción de vendedor',
                    'descripcion' => "Recepción confirmada de {$transferencia->codigo}.",
                    'referencia' => $transferencia->codigo,
                ]);
            }

            $transferencia->update([
                'estado' => 'recibida',
                'recibido_por' => auth()->id(),
                'recibido_at' => now(),
            ]);
        });

        return back()->with('success', 'Recepción confirmada; las existencias ya están disponibles para la venta.');
    }

    public function reviewCount(Request $request, InventoryCountRun $countRun): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasRole('bodeguero'), 403);
        $validated = $request->validate([
            'decision' => ['required', 'in:aprobar,rechazar'],
            'nota' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['decision'] === 'rechazar' && blank($validated['nota'] ?? null)) {
            throw ValidationException::withMessages([
                'nota' => 'Explique por qué se rechaza el conteo.',
            ]);
        }

        DB::transaction(function () use ($validated, $countRun): void {
            $run = InventoryCountRun::query()->lockForUpdate()->findOrFail($countRun->id);

            if ($run->estado !== 'pendiente') {
                throw ValidationException::withMessages([
                    'conteo' => 'Este conteo ya fue revisado.',
                ]);
            }

            $items = InventoryCountRunItem::query()
                ->where('inventory_count_run_id', $run->id)
                ->orderBy('producto_id')
                ->lockForUpdate()
                ->get();

            if ($validated['decision'] === 'aprobar') {
                $this->ensureCountSnapshotIsCurrent($items);
                $this->applyCountAdjustments($run, $items);
            }

            $run->update([
                'estado' => $validated['decision'] === 'aprobar' ? 'aprobado' : 'rechazado',
                'revisado_por' => auth()->id(),
                'revisado_at' => now(),
                'nota' => $validated['nota'] ?? $run->nota,
            ]);
        });

        return back()->with(
            'success',
            $validated['decision'] === 'aprobar'
                ? 'Conteo aprobado y existencias actualizadas.'
                : 'Conteo rechazado; las existencias no fueron modificadas.'
        );
    }

    private function authorizeCountRun(InventoryCountRun $countRun): void
    {
        $user = auth()->user();

        abort_unless(
            $user->isAdmin() || $user->hasRole('bodeguero') || $countRun->user_id === $user->id,
            403
        );
    }

    /**
     * @param  EloquentCollection<int, InventoryCountRunItem>  $items
     */
    private function ensureCountSnapshotIsCurrent(EloquentCollection $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::query()->lockForUpdate()->find($item->producto_id);

            if (! $producto ||
                ! $producto->activo ||
                $producto->stock !== $item->stock_sistema ||
                (float) $producto->costo !== (float) $item->costo_unitario ||
                InventoryMovement::query()
                    ->where('producto_id', $item->producto_id)
                    ->where('id', '>', $item->ultimo_movimiento_id ?? 0)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'conteo' => "El inventario de {$item->producto_nombre} cambió después de iniciar el conteo. Inicie uno nuevo antes de ajustar.",
                ]);
            }
        }
    }

    /**
     * @param  EloquentCollection<int, InventoryCountRunItem>  $items
     */
    private function applyCountAdjustments(InventoryCountRun $run, EloquentCollection $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($item->producto_id);
            $difference = (int) $item->diferencia;

            if ($difference === 0) {
                continue;
            }

            $stockBefore = $producto->stock;
            $producto->update(['stock' => $item->cantidad_contada]);

            InventoryMovement::create([
                'user_id' => auth()->id(),
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'producto_codigo' => $producto->codigo,
                'tipo' => 'correccion',
                'cantidad' => $difference,
                'stock_before' => $stockBefore,
                'stock_after' => $item->cantidad_contada,
                'costo_unitario' => $item->costo_unitario,
                'motivo' => 'Conteo físico',
                'descripcion' => $run->nota ?: "Ajuste del conteo #{$run->id}.",
                'referencia' => 'CONTEO-'.$run->id,
            ]);
        }
    }
}
