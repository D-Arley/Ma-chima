<x-app-layout>
    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <a href="{{ route('catalogo.index') }}" class="text-sm font-semibold text-[#8d6929]">← Volver al catálogo</a>
        <section class="mt-6 grid gap-8 rounded-3xl border border-[#e7e0d2] bg-white p-5 sm:p-8 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="overflow-hidden rounded-2xl bg-[#ece7dc]">
                <img src="{{ $producto->imagen ?: asset('images/ma-chima-logo.png') }}" alt="{{ $producto->nombre }}" class="aspect-[4/5] w-full object-cover">
            </div>
            <div class="flex flex-col justify-center">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a47b31]">{{ $producto->categoria->nombre }} · {{ $producto->codigo }}</p>
                <h1 class="mt-4 font-serif text-4xl sm:text-5xl">{{ $producto->nombre }}</h1>
                <p class="mt-5 max-w-xl leading-7 text-[#716d65]">{{ $producto->descripcion ?: 'Producto de la colección Ma’chima.' }}</p>
                <div class="mt-8 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-[#f8f6f0] p-4">
                        <p class="text-xs text-[#8c8679]">Talla</p>
                        <p class="mt-1 font-semibold">{{ $producto->talla }}</p>
                    </div>
                    <div class="rounded-xl bg-[#f8f6f0] p-4">
                        <p class="text-xs text-[#8c8679]">Disponible</p>
                        <p class="mt-1 font-semibold">{{ $producto->stock }} unidades</p>
                    </div>
                </div>
                <div class="mt-8 border-t border-[#eee9df] pt-6">
                    <p class="text-xs uppercase tracking-[0.14em] text-[#8c8679]">Precio al consumidor</p>
                    <p class="mt-2 font-serif text-4xl text-[#171511]">${{ number_format($producto->precio, 0, ',', '.') }}</p>
                    @if (auth()->user()->isAdmin() || auth()->user()->hasRole('bodeguero'))
                        @can('manage-inventory')
                            <a href="{{ route('inventario.edit', $producto) }}" class="mt-6 inline-flex rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Gestionar en inventario</a>
                        @endcan
                    @endif
                </div>
            </div>
        </section>
    </main>
</x-app-layout>
