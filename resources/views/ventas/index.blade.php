<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Punto de venta</p>
                <h1 class="mt-1 font-serif text-3xl">Caja y ventas</h1>
            </div>
            <a href="{{ route('caja.index') }}" class="rounded-full border border-[#d8cfbe] px-5 py-3 text-sm font-semibold">Ver caja</a>
        </div>
    </x-slot>

    <main x-data="{
        cart: [],
        descuento: 0,
        metodo: 'efectivo',
        recibido: 0,
        get bruto() { return this.cart.reduce((sum, item) => sum + item.precio * item.cantidad, 0); },
        get total() { return Math.max(0, this.bruto - Number(this.descuento || 0)); },
        get neto() { return this.total / 1.19; },
        get iva() { return this.total - this.neto; },
        get cambio() { return Math.max(0, Number(this.recibido || 0) - this.total); },
        add(producto) {
            const existing = this.cart.find(item => item.producto_id === producto.id);
            if (existing) { if (existing.cantidad < producto.stock) existing.cantidad++; }
            else { this.cart.push({ producto_id: producto.id, nombre: producto.nombre, precio: Number(producto.precio), cantidad: 1, stock: producto.stock }); }
        },
        remove(productoId) { this.cart = this.cart.filter(item => item.producto_id !== productoId); },
        increment(item) { if (item.cantidad < item.stock) item.cantidad++; },
        decrement(item) { if (item.cantidad > 1) item.cantidad--; }
    }" class="mx-auto max-w-[90rem] px-5 py-8 sm:px-8">
        @if (! $caja)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <p class="font-semibold text-amber-900">La caja todavía no está abierta.</p>
                <p class="mt-1 text-sm text-amber-800">Abra la caja antes de registrar ventas.</p>
                <a href="{{ route('caja.index') }}" class="mt-4 inline-flex rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Ir a caja</a>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_350px] xl:items-start">
            <section>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($productos as $producto)
                        <article class="group overflow-hidden rounded-2xl border border-[#e7e0d2] bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                            <button
                                type="button"
                                data-producto-id="{{ $producto->id }}"
                                data-producto-nombre="{{ $producto->nombre }}"
                                data-producto-precio="{{ $producto->precio }}"
                                data-producto-stock="{{ $producto->stock }}"
                                x-on:click="add({ id: Number($event.currentTarget.dataset.productoId), nombre: $event.currentTarget.dataset.productoNombre, precio: Number($event.currentTarget.dataset.productoPrecio), stock: Number($event.currentTarget.dataset.productoStock) })"
                                class="block w-full text-left"
                            >
                                <div class="aspect-[4/3] overflow-hidden bg-[#ece7dc]">
                                    <img src="{{ $producto->imagen ?: asset('images/ma-chima-logo.png') }}" alt="{{ $producto->nombre }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.05]">
                                </div>
                                <div class="p-4">
                                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-[#a47b31]">{{ $producto->categoria->nombre }}</p>
                                    <h2 class="mt-2 font-serif text-lg">{{ $producto->nombre }}</h2>
                                    <div class="mt-4 flex items-center justify-between">
                                        <p class="font-bold">${{ number_format($producto->precio, 0, ',', '.') }}</p>
                                        <span class="rounded-full bg-[#171511] px-3 py-1.5 text-xs font-semibold text-white">Agregar</span>
                                    </div>
                                    <p class="mt-2 text-xs font-semibold {{ $producto->stock <= 5 ? 'text-rose-700' : 'text-[#716d65]' }}">Disponibles en bodega: {{ $producto->stock }}</p>
                                </div>
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="h-fit rounded-3xl border border-[#e7e0d2] bg-white p-4 shadow-sm xl:sticky xl:top-24">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Pedido actual</p>
                        <h2 class="mt-1 font-serif text-2xl">Carrito</h2>
                    </div>
                    <button type="button" x-show="cart.length" x-on:click="cart = []" class="text-xs font-semibold text-rose-700">Vaciar</button>
                </div>

                <div class="mt-4 max-h-64 space-y-3 overflow-y-auto overscroll-contain pr-2">
                    <template x-if="cart.length === 0">
                        <div class="rounded-xl border border-dashed border-[#ddd4c3] p-6 text-center text-sm text-[#8c8679]">Seleccione productos para comenzar.</div>
                    </template>
                    <template x-for="item in cart" :key="item.producto_id">
                        <div class="flex items-center gap-3 border-b border-[#eee9df] pb-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold" x-text="item.nombre"></p>
                                <p class="mt-1 text-xs text-[#8c8679]" x-text="'$' + item.precio.toLocaleString('es-CO') + ' c/u'"></p>
                                <p class="mt-1 text-[0.65rem] font-semibold text-[#716d65]" x-text="'Disponibles en bodega: ' + item.stock"></p>
                            </div>
                            <div class="flex items-center rounded-lg border border-[#ded8cc]">
                                <button type="button" x-on:click="decrement(item)" class="px-2 py-1">−</button>
                                <span class="w-7 text-center text-sm" x-text="item.cantidad"></span>
                                <button type="button" x-on:click="increment(item)" class="px-2 py-1">+</button>
                            </div>
                            <button type="button" x-on:click="remove(item.producto_id)" class="text-xs text-rose-700">×</button>
                        </div>
                    </template>
                </div>

                <form method="POST" action="{{ route('ventas.store') }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="items" x-bind:value="JSON.stringify(cart)">
                    <div class="space-y-3 rounded-2xl bg-[#f8f6f0] p-4">
                        <div class="flex justify-between text-sm"><span>Subtotal sin IVA</span><strong x-text="'$' + neto.toLocaleString('es-CO', {maximumFractionDigits: 0})"></strong></div>
                        <div class="flex justify-between text-sm"><span>IVA 19%</span><strong x-text="'$' + iva.toLocaleString('es-CO', {maximumFractionDigits: 0})"></strong></div>
                        <div>
                            <label class="text-xs text-[#716d65]" for="descuento">Descuento COP</label>
                            <input id="descuento" name="descuento" type="number" min="0" x-model.number="descuento" class="mt-1 w-full rounded-lg border-[#ded8cc] px-3 py-2 text-sm">
                        </div>
                        <div class="flex items-end justify-between border-t border-[#ddd4c3] pt-3">
                            <span class="font-semibold">Total a pagar</span>
                            <strong class="font-serif text-3xl" x-text="'$' + total.toLocaleString('es-CO', {maximumFractionDigits: 0})"></strong>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="metodo_pago" value="efectivo" x-model="metodo" class="peer sr-only">
                            <span class="block rounded-xl border border-[#ded8cc] px-3 py-3 text-center text-sm peer-checked:border-[#171511] peer-checked:bg-[#171511] peer-checked:text-white">Efectivo</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="metodo_pago" value="transferencia" x-model="metodo" class="peer sr-only">
                            <span class="block rounded-xl border border-[#ded8cc] px-3 py-3 text-center text-sm peer-checked:border-[#171511] peer-checked:bg-[#171511] peer-checked:text-white">Transferencia</span>
                        </label>
                    </div>

                    <div x-show="metodo === 'efectivo'" class="mt-4 grid gap-3">
                        <label class="text-sm font-semibold" for="efectivo_recibido">Efectivo recibido</label>
                        <input id="efectivo_recibido" name="efectivo_recibido" type="number" min="0" x-model.number="recibido" class="rounded-xl border-[#ded8cc] px-4 py-3">
                        <div class="flex justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><span>Cambio</span><strong x-text="'$' + cambio.toLocaleString('es-CO', {maximumFractionDigits: 0})"></strong></div>
                    </div>

                    <button type="submit" x-bind:disabled="cart.length === 0 || !{{ $caja ? 'true' : 'false' }}" class="mt-5 w-full rounded-full bg-[#171511] px-5 py-4 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Registrar venta</button>
                </form>
            </aside>
        </div>

        @if ($ventasHoy->isNotEmpty())
            <section class="mt-10">
                <h2 class="font-serif text-2xl">Ventas de hoy</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($ventasHoy as $venta)
                        <a href="{{ route('ventas.show', $venta) }}" class="rounded-2xl border border-[#e7e0d2] bg-white p-4 transition hover:-translate-y-1 hover:shadow-md">
                            <p class="text-xs text-[#8c8679]">{{ $venta->comprobante }}</p>
                            <p class="mt-2 font-serif text-2xl">${{ number_format($venta->total, 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs font-semibold capitalize text-[#a47b31]">{{ $venta->metodo_pago }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-app-layout>
