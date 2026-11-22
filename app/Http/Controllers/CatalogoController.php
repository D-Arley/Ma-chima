<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(Request $request): View
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $query = Producto::with('categoria')->where('activo', true);

        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->integer('categoria'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->string('buscar')->toString());
            $query->where(function ($builder) use ($buscar) {
                $builder->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%")
                    ->orWhere('talla', 'like', "%{$buscar}%");
            });
        }

        $productos = $query->orderBy('nombre')->paginate(12)->withQueryString();

        return view('catalogo.index', compact('productos', 'categorias'));
    }

    public function show(Producto $producto): View
    {
        abort_unless($producto->activo, 404);
        $producto->load('categoria');

        return view('catalogo.show', compact('producto'));
    }
}
