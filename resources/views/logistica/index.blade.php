<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Operación de inventario</p>
            <h1 class="mt-1 font-serif text-3xl">Entregas y conteos</h1>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div class="mb-8 rounded-2xl border border-[#e7d8b5] bg-[#fbf6e9] p-5 text-sm leading-6 text-[#66532e]">
            El sistema conserva un solo saldo global. Al despachar, las unidades se descuentan temporalmente para que no puedan venderse antes de la recepción; al confirmar la entrega, vuelven a estar disponibles. El conteo masivo oculta las cantidades del sistema mientras se registra; los conteos de vendedor requieren revisión antes de modificar el saldo.
        </div>

        @if ($canManageInventory)
            <section class="rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:p-8" x-data="{ items: [{ producto_id: '', cantidad: 1 }] }">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Bodeguero</p>
                    <h2 class="mt-1 font-serif text-2xl">Preparar entrega</h2>
                    <p class="mt-2 text-sm text-[#716d65]">Las existencias se reservan al despachar y el vendedor debe confirmar la recepción.</p>
                </div>
                <form method="POST" action="{{ route('logistica.deliver') }}" class="mt-6 space-y-5">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="vendedor_id" class="text-sm font-semibold">Entregar a</label>
                            <select id="vendedor_id" name="vendedor_id" required class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3">
                                <option value="">Seleccione un vendedor</option>
                                @foreach ($vendedores as $vendedor)
                                    <option value="{{ $vendedor->id }}" @selected(old('vendedor_id') == $vendedor->id)>{{ $vendedor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="descripcion" class="text-sm font-semibold">Nota de entrega (opcional)</label>
                            <input id="descripcion" name="descripcion" value="{{ old('descripcion') }}" maxlength="500" class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid gap-3 rounded-xl bg-[#f8f6f0] p-4 sm:grid-cols-[1fr_150px_auto]">
                                <select x-model="item.producto_id" :name="'items[' + index + '][producto_id]'" required class="rounded-xl border-[#ded8cc] px-4 py-3">
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($productos as $producto)
                                        <option value="{{ $producto->id }}">{{ $producto->nombre }} · {{ $producto->codigo }} · disponible: {{ $producto->stock }}</option>
                                    @endforeach
                                </select>
                                <input x-model="item.cantidad" :name="'items[' + index + '][cantidad]'" type="number" min="1" max="999" required aria-label="Cantidad" class="rounded-xl border-[#ded8cc] px-4 py-3">
                                <button type="button" x-show="items.length > 1" x-on:click="items.splice(index, 1)" class="rounded-xl border border-rose-200 px-4 py-3 text-sm font-semibold text-rose-700">Quitar</button>
                            </div>
                        </template>
                        <button type="button" x-on:click="items.push({ producto_id: '', cantidad: 1 })" class="text-sm font-semibold text-[#9b742f]">+ Agregar producto</button>
                    </div>

                    <button class="rounded-full bg-[#171511] px-6 py-3 text-sm font-semibold text-white">Despachar mercancía</button>
                </form>
            </section>
        @endif

        <section class="mt-8 flex flex-col gap-5 rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Conteo físico</p>
                <h2 class="mt-1 font-serif text-2xl">Conteo masivo de inventario</h2>
                <p class="mt-2 max-w-2xl text-sm text-[#716d65]">Se inicia una sesión con todos los productos activos. Las existencias del sistema se ocultan hasta que finalice el conteo.</p>
            </div>
            <form method="POST" action="{{ route('logistica.count-runs.start') }}">
                @csrf
                <button class="whitespace-nowrap rounded-full bg-[#171511] px-6 py-3 text-sm font-semibold text-white">Iniciar conteo</button>
            </form>
        </section>

        <section class="mt-8 grid gap-8 xl:grid-cols-2">
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:p-8">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Trazabilidad</p>
                        <h2 class="mt-1 font-serif text-2xl">Entregas</h2>
                    </div>
                    <span class="text-xs text-[#8c8679]">{{ $transferencias->total() }} registros</span>
                </div>
                <div class="mt-5 space-y-4">
                    @forelse ($transferencias as $transferencia)
                        <article class="rounded-xl bg-[#f8f6f0] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ $transferencia->codigo }}</p>
                                    <p class="mt-1 text-xs text-[#716d65]">
                                        {{ $transferencia->entregado_at->format('d/m/Y H:i') }}
                                        · {{ $canManageInventory ? 'Para '.$transferencia->vendedor->name : 'De '.$transferencia->user->name }}
                                    </p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $transferencia->estado === 'pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $transferencia->estado === 'pendiente' ? 'Pendiente de recepción' : 'Recibida' }}
                                </span>
                            </div>
                            <ul class="mt-3 space-y-1 text-sm text-[#4c4942]">
                                @foreach ($transferencia->items as $item)
                                    <li>{{ $item->producto->nombre }} × {{ $item->cantidad }}</li>
                                @endforeach
                            </ul>
                            @if ($transferencia->descripcion)
                                <p class="mt-2 text-xs text-[#716d65]">{{ $transferencia->descripcion }}</p>
                            @endif
                            @if ($transferencia->estado === 'pendiente' && (! $canManageInventory || auth()->user()->isAdmin()))
                                <form method="POST" action="{{ route('logistica.receive', $transferencia) }}" class="mt-4">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-full bg-[#171511] px-5 py-2.5 text-sm font-semibold text-white">Confirmar recepción</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-[#ddd4c3] p-6 text-center text-sm text-[#8c8679]">Aún no hay entregas registradas.</p>
                    @endforelse
                </div>
                <div class="mt-5">{{ $transferencias->links() }}</div>
            </div>

            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:p-8">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Control y aprobación</p>
                        <h2 class="mt-1 font-serif text-2xl">Sesiones de conteo</h2>
                    </div>
                    <span class="text-xs text-[#8c8679]">{{ $conteos->total() }} registros</span>
                </div>
                <div class="mt-5 space-y-4">
                    @forelse ($conteos as $conteo)
                        <article class="rounded-xl bg-[#f8f6f0] p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('logistica.count-runs.show', $conteo) }}" class="font-semibold text-[#8d6929]">Conteo #{{ $conteo->id }}</a>
                                    <p class="mt-1 text-xs text-[#716d65]">
                                        {{ $conteo->iniciado_at->format('d/m/Y H:i') }}
                                        · {{ $conteo->user->name }}
                                    </p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ in_array($conteo->estado, ['pendiente', 'en_progreso'], true) ? 'bg-amber-100 text-amber-800' : ($conteo->estado === 'aprobado' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $conteo->estado)) }}
                                </span>
                            </div>
                            <p class="mt-3 text-xs text-[#716d65]">{{ $conteo->items->count() }} productos incluidos.</p>
                            @if ($canManageInventory && $conteo->estado === 'pendiente')
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('logistica.review-count', $conteo) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="aprobar">
                                        <button class="rounded-full bg-[#171511] px-4 py-2 text-xs font-semibold text-white">Aprobar y ajustar</button>
                                    </form>
                                    <form method="POST" action="{{ route('logistica.review-count', $conteo) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="rechazar">
                                        <input name="nota" required maxlength="1000" placeholder="Motivo del rechazo" aria-label="Motivo del rechazo" class="w-48 rounded-full border-[#ded8cc] px-4 py-2 text-xs">
                                        <button class="rounded-full border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-700">Rechazar</button>
                                    </form>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-[#ddd4c3] p-6 text-center text-sm text-[#8c8679]">Aún no hay conteos registrados.</p>
                    @endforelse
                </div>
                <div class="mt-5">{{ $conteos->links() }}</div>
            </div>
        </section>
    </main>
</x-app-layout>
