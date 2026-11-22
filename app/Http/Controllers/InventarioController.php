<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\InventoryCountRunItem;
use App\Models\InventoryMovement;
use App\Models\InventoryTransferItem;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request): View
    {
        $query = Producto::with('categoria')->where('activo', true);

        if ($request->filled('buscar')) {
            $buscar = trim($request->string('buscar')->toString());
            $query->where(function ($builder) use ($buscar) {
                $builder->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->integer('categoria'));
        }

        $productos = $query->orderBy('nombre')->paginate(15)->withQueryString();
        $categorias = Categoria::orderBy('nombre')->get();
        $valorBodega = Producto::query()
            ->where('activo', true)
            ->selectRaw('COALESCE(SUM(costo * stock), 0) as total')
            ->value('total');

        return view('inventario.index', compact('productos', 'categorias', 'valorBodega'));
    }

    public function create(): View
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return view('inventario.create', compact('categorias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'talla' => trim((string) $request->input('talla')),
        ]);

        $validated = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('productos', 'nombre')
                    ->where('categoria_id', $request->input('categoria_id'))
                    ->where('talla', $request->input('talla')),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'talla' => ['required', 'string', 'max:20'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = '/storage/'.$request->file('imagen')->store('productos', 'public');
        }

        $producto = DB::transaction(function () use ($validated) {
            Categoria::query()->orderBy('id')->lockForUpdate()->get(['id']);

            if (Producto::query()
                ->where('categoria_id', $validated['categoria_id'])
                ->where('nombre', $validated['nombre'])
                ->where('talla', $validated['talla'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'nombre' => 'Ya existe un producto con ese nombre, categoría y talla.',
                ]);
            }

            $producto = Producto::create([
                ...$validated,
                'codigo' => Producto::nextSequentialCode(),
            ]);
            InventoryMovement::create([
                'user_id' => auth()->id(),
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'producto_codigo' => $producto->codigo,
                'tipo' => 'entrada',
                'cantidad' => $producto->stock,
                'stock_before' => 0,
                'stock_after' => $producto->stock,
                'costo_unitario' => $producto->costo,
                'motivo' => 'Ingreso inicial',
                'descripcion' => 'Creación del producto con stock inicial.',
            ]);

            return $producto;
        });

        return redirect()->route('inventario.index')->with('success', "Producto {$producto->nombre} creado.");
    }

    public function edit(Producto $producto): View
    {
        abort_unless($producto->activo, 404);

        $producto->load('categoria');
        $categorias = Categoria::orderBy('nombre')->get();

        return view('inventario.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        abort_unless($producto->activo, 404);

        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'talla' => trim((string) $request->input('talla')),
        ]);

        $validated = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('productos', 'nombre')
                    ->where('categoria_id', $request->input('categoria_id'))
                    ->where('talla', $request->input('talla'))
                    ->ignore($producto->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'talla' => ['required', 'string', 'max:20'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'ajuste' => ['required', 'integer'],
            'motivo' => ['nullable', Rule::in(['Dano', 'Perdida', 'Correccion', 'Garantia'])],
            'explicacion' => ['nullable', 'string', 'max:1000'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($validated['ajuste'] < 0 && empty($validated['motivo'])) {
            return back()->withErrors(['motivo' => 'Debe seleccionar el motivo al descontar existencias.'])->withInput();
        }

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = '/storage/'.$request->file('imagen')->store('productos', 'public');
        } else {
            unset($validated['imagen']);
        }

        DB::transaction(function () use ($validated, $producto) {
            Categoria::query()->orderBy('id')->lockForUpdate()->get(['id']);
            $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();

            if (Producto::query()
                ->where('categoria_id', $validated['categoria_id'])
                ->where('nombre', $validated['nombre'])
                ->where('talla', $validated['talla'])
                ->where('id', '<>', $producto->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'nombre' => 'Ya existe un producto con ese nombre, categoría y talla.',
                ]);
            }

            $stockBefore = $producto->stock;
            $stockAfter = $stockBefore + $validated['ajuste'];

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'ajuste' => 'El ajuste no puede dejar el inventario con existencias negativas.',
                ]);
            }

            $producto->update([
                'categoria_id' => $validated['categoria_id'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'talla' => $validated['talla'],
                'precio' => $validated['precio'],
                'costo' => $validated['costo'],
                'stock' => $stockAfter,
                'imagen' => $validated['imagen'] ?? $producto->imagen,
            ]);

            if ($validated['ajuste'] !== 0) {
                $tipo = match ($validated['motivo'] ?? null) {
                    'Dano' => 'danio',
                    'Perdida' => 'perdida',
                    'Garantia' => 'garantia',
                    default => $validated['ajuste'] > 0 ? 'entrada' : 'correccion',
                };

                InventoryMovement::create([
                    'user_id' => auth()->id(),
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_codigo' => $producto->codigo,
                    'tipo' => $tipo,
                    'cantidad' => $validated['ajuste'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'costo_unitario' => $producto->costo,
                    'motivo' => $validated['motivo'] ?? ($validated['ajuste'] > 0 ? 'Entrada' : 'Correccion'),
                    'descripcion' => $validated['explicacion'] ?: null,
                ]);
            }
        });

        return redirect()->route('inventario.index')->with('success', 'Producto actualizado y ajuste registrado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        DB::transaction(function () use ($producto): void {
            Categoria::query()->orderBy('id')->lockForUpdate()->get(['id']);
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);

            if (! $producto->activo) {
                throw ValidationException::withMessages([
                    'producto' => 'El producto ya está archivado.',
                ]);
            }

            if ($producto->stock > 0) {
                throw ValidationException::withMessages([
                    'producto' => 'Ajuste el stock a cero antes de archivar el producto; no se registrará una pérdida automática.',
                ]);
            }

            $hasPendingTransfer = InventoryTransferItem::query()
                ->where('producto_id', $producto->id)
                ->whereHas('transfer', fn ($query) => $query->where('estado', 'pendiente'))
                ->exists();
            $hasPendingCount = InventoryCountRunItem::query()
                ->where('producto_id', $producto->id)
                ->whereHas('run', fn ($query) => $query->whereIn('estado', ['en_progreso', 'pendiente']))
                ->exists();

            if ($hasPendingTransfer || $hasPendingCount) {
                throw ValidationException::withMessages([
                    'producto' => 'No se puede archivar mientras haya una entrega o conteo pendiente para este producto.',
                ]);
            }

            $producto->update(['activo' => false, 'codigo' => null]);
            Producto::resequenceActiveCodes();
        });

        return redirect()->route('inventario.index')->with('success', 'Producto archivado; el historial se conserva y los códigos activos se renumeraron.');
    }
}
