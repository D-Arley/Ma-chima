<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Colección Ma'chima</p>
            <h1 class="mt-1 font-serif text-3xl">Catálogo de productos</h1>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <form method="GET" class="grid gap-3 rounded-2xl border border-[#e7e0d2] bg-white p-4 sm:grid-cols-[1fr_220px_auto]">
            <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, código o talla..." class="rounded-xl border-[#ded8cc] px-4 py-3 text-sm">
            <select name="categoria" class="rounded-xl border-[#ded8cc] px-4 py-3 text-sm">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(request('categoria') == $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Buscar</button>
        </form>

        <section class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($productos as $producto)
                <article class="group overflow-hidden rounded-2xl border border-[#e7e0d2] bg-white shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl">
                    <a href="{{ route('catalogo.show', $producto) }}" class="block">
                        <div class="aspect-[4/5] overflow-hidden bg-[#ece7dc]">
                            <img src="{{ $producto->imagen ?: asset('images/ma-chima-logo.png') }}" alt="{{ $producto->nombre }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.06]">
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.16em] text-[#a47b31]">{{ $producto->categoria->nombre }}</p>
                                    <h2 class="mt-2 font-serif text-xl leading-tight">{{ $producto->nombre }}</h2>
                                </div>
                                @if ($producto->stock <= 5)
                                    <span class="shrink-0 rounded-full bg-rose-50 px-2 py-1 text-[0.65rem] font-bold text-rose-700">Stock {{ $producto->stock }}</span>
                                @endif
                            </div>
                            <div class="mt-5 flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs text-[#8c8679]">Talla {{ $producto->talla }}</p>
                                    <p class="mt-1 text-lg font-bold text-[#171511]">${{ number_format($producto->precio, 0, ',', '.') }}</p>
                                </div>
                                <span class="flex h-10 w-10 items-center justify-center rounded-full border border-[#dfd5c2] text-[#a47b31] transition group-hover:border-[#c99c42] group-hover:bg-[#171511] group-hover:text-[#e4c577]">→</span>
                            </div>
                        </div>
                    </a>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-[#d8cfbe] p-12 text-center text-[#716d65]">No se encontraron productos.</div>
            @endforelse
        </section>
        <div class="mt-8">{{ $productos->links() }}</div>

        <aside class="mt-10 rounded-2xl border border-[#e7e0d2] bg-white p-5 text-xs leading-6 text-[#716d65]">
            <h2 class="font-semibold text-[#302d27]">Créditos de fotografías</h2>
            <ul class="mt-2 list-inside list-disc">
                <li>
                    Oxford negro: Maximilian Kremer,
                    <a href="https://commons.wikimedia.org/wiki/File:Black_Wholecut_Oxfords.png" target="_blank" rel="noopener noreferrer" class="underline">Black Wholecut Oxfords</a>,
                    <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer" class="underline">CC BY 4.0</a>.
                </li>
                <li>
                    Polo verde: Posiwulausilsma LUOA,
                    <a href="https://commons.wikimedia.org/wiki/File:HK_TKO_%E5%B0%87%E8%BB%8D%E6%BE%B3_Tseung_Kwan_O_PopCorn_mall_shop_Uniqlo_Clothing_color_code_53_Green_Short_sleeve_Polo_shirt_China_July_2022_Px3.jpg" target="_blank" rel="noopener noreferrer" class="underline">polo verde</a>,
                    <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer" class="underline">CC BY-SA 4.0</a> (imagen girada para mostrar la prenda derecha).
                </li>
                <li>
                    Camiseta a rayas: Maria Morri,
                    <a href="https://commons.wikimedia.org/wiki/File:Young_woman_in_marini%C3%A8re_shirt.jpg" target="_blank" rel="noopener noreferrer" class="underline">Young woman in marinière shirt</a>,
                    <a href="https://creativecommons.org/licenses/by-sa/2.0/" target="_blank" rel="noopener noreferrer" class="underline">CC BY-SA 2.0</a>.
                </li>
            </ul>
        </aside>
    </main>
</x-app-layout>
