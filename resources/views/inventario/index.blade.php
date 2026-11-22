<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Bodega y logística</p>
                <h1 class="mt-1 font-serif text-3xl">Inventario</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('logistica.index') }}" class="rounded-full border border-[#d8cfbe] px-5 py-3 text-sm font-semibold">Entregas y conteos</a>
                <a href="{{ route('inventario.create') }}" class="rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Nuevo producto</a>
            </div>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->has('producto'))
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first('producto') }}</div>
        @endif
        <section class="mb-6 rounded-2xl border border-[#e7d8b5] bg-[#fbf6e9] p-5">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#8d6929]">Valor total en bodega</p>
            <p class="mt-2 font-serif text-3xl">${{ number_format($valorBodega, 0, ',', '.') }} <span class="font-sans text-sm font-medium">COP</span></p>
            <p class="mt-1 text-xs text-[#716d65]">Costo de adquisición por las existencias activas.</p>
        </section>
        <form method="GET" class="grid gap-3 rounded-2xl border border-[#e7e0d2] bg-white p-4 sm:grid-cols-[1fr_220px_auto]">
            <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código..." class="rounded-xl border-[#ded8cc] px-4 py-3 text-sm">
            <select name="categoria" class="rounded-xl border-[#ded8cc] px-4 py-3 text-sm">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(request('categoria') == $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Filtrar</button>
        </form>

        <section class="mt-6 overflow-hidden rounded-2xl border border-[#e7e0d2] bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f8f6f0] text-xs uppercase tracking-[0.12em] text-[#777167]">
                        <tr>
                            <th class="px-5 py-4">Producto</th>
                            <th class="px-5 py-4">Código</th>
                            <th class="px-5 py-4">Costo</th>
                            <th class="px-5 py-4">Precio</th>
                            <th class="px-5 py-4">Margen neto</th>
                            <th class="px-5 py-4">Stock</th>
                            <th class="px-5 py-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eee9df]">
                        @foreach ($productos as $producto)
                            @php
                                $neto = (float) $producto->precio / 1.19;
                                $margen = $neto - (float) $producto->costo;
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $producto->imagen ?: asset('images/ma-chima-logo.png') }}" alt="" class="h-12 w-12 rounded-lg object-cover">
                                        <div><p class="font-semibold">{{ $producto->nombre }}</p><p class="text-xs text-[#8c8679]">{{ $producto->categoria->nombre }} · {{ $producto->talla }}</p></div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">{{ $producto->codigo }}</td>
                                <td class="px-5 py-4">${{ number_format($producto->costo, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 font-semibold">${{ number_format($producto->precio, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 {{ $margen >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">${{ number_format($margen, 0, ',', '.') }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $producto->stock <= 5 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $producto->stock }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('inventario.edit', $producto) }}" class="font-semibold text-[#9b742f]">Editar</a>
                                        <form method="POST" action="{{ route('inventario.destroy', $producto) }}" onsubmit="return confirm('¿Archivar este producto? El historial se conservará.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-semibold text-rose-700 disabled:cursor-not-allowed disabled:opacity-40" @disabled($producto->stock > 0) title="{{ $producto->stock > 0 ? 'Ajuste el stock a cero antes de archivar.' : 'Archivar producto' }}">Archivar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <div class="mt-8">{{ $productos->links() }}</div>
    </main>
</x-app-layout>
