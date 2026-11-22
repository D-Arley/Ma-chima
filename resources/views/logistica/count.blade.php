<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Control de existencias</p>
            <h1 class="mt-1 font-serif text-3xl">Conteo físico #{{ $countRun->id }}</h1>
        </div>
    </x-slot>

    <main class="mx-auto max-w-5xl px-5 py-10 sm:px-8">
        <a href="{{ route('logistica.index') }}" class="text-sm font-semibold text-[#8d6929]">← Volver a entregas y conteos</a>

        @if (session('success'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        @if ($countRun->estado === 'en_progreso')
            <section class="mt-6 rounded-2xl border border-[#e7d8b5] bg-[#fbf6e9] p-5 text-sm leading-6 text-[#66532e]">
                Registre las unidades encontradas físicamente. Las existencias del sistema permanecen ocultas durante el conteo. Al finalizar, los ajustes se aplicarán de inmediato si usted es administrador o bodeguero; los conteos de vendedor se enviarán a revisión.
            </section>

            <form method="POST" action="{{ route('logistica.count-runs.finish', $countRun) }}" class="mt-6 rounded-2xl border border-[#e7e0d2] bg-white p-5 sm:p-8">
                @csrf
                @method('PUT')
                <div class="space-y-3">
                    @foreach ($countRun->items as $item)
                        <label for="item-{{ $item->producto_id }}" class="grid items-center gap-3 rounded-xl bg-[#f8f6f0] p-4 sm:grid-cols-[1fr_180px]">
                            <span>
                                <span class="block font-semibold">{{ $item->producto_nombre }}</span>
                                <span class="mt-1 block text-xs text-[#716d65]">Código {{ $item->producto_codigo }}</span>
                            </span>
                            <span>
                                <span class="mb-1 block text-xs font-semibold text-[#716d65]">Cantidad encontrada</span>
                                <input id="item-{{ $item->producto_id }}" name="items[{{ $item->producto_id }}]" type="number" min="0" max="1000000" value="{{ old('items.'.$item->producto_id) }}" required class="w-full rounded-xl border-[#ded8cc] px-4 py-3">
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-5">
                    <label for="nota" class="text-sm font-semibold">Observación (opcional)</label>
                    <textarea id="nota" name="nota" rows="3" maxlength="1000" class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3">{{ old('nota') }}</textarea>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-[#716d65]">{{ $countRun->items->count() }} productos incluidos en este conteo.</p>
                    <button class="rounded-full bg-[#171511] px-6 py-3 text-sm font-semibold text-white">
                        {{ auth()->user()->isAdmin() || auth()->user()->hasRole('bodeguero') ? 'Ajustar existencias' : 'Enviar a revisión' }}
                    </button>
                </div>
            </form>
        @else
            <section class="mt-6 grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-rose-700">Faltantes / pérdidas</p>
                    <p class="mt-2 font-serif text-2xl">{{ $countRunSummary['lost_units'] }} unidades</p>
                    <p class="mt-1 text-sm text-rose-800">${{ number_format($countRunSummary['lost_value'], 0, ',', '.') }} COP al costo</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Sobrantes / agregados</p>
                    <p class="mt-2 font-serif text-2xl">{{ $countRunSummary['added_units'] }} unidades</p>
                    <p class="mt-1 text-sm text-emerald-800">${{ number_format($countRunSummary['added_value'], 0, ',', '.') }} COP al costo</p>
                </article>
            </section>

            <section class="mt-6 overflow-hidden rounded-2xl border border-[#e7e0d2] bg-white">
                <div class="border-b border-[#eee9df] p-5">
                    <p class="text-sm font-semibold">Estado: {{ ucfirst(str_replace('_', ' ', $countRun->estado)) }}</p>
                    <p class="mt-1 text-xs text-[#716d65]">Iniciado {{ $countRun->iniciado_at->format('d/m/Y H:i') }} · Finalizado {{ $countRun->finalizado_at?->format('d/m/Y H:i') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#f8f6f0] text-xs uppercase tracking-[0.12em] text-[#777167]">
                            <tr>
                                <th class="px-5 py-3">Producto</th>
                                <th class="px-5 py-3">Sistema</th>
                                <th class="px-5 py-3">Contado</th>
                                <th class="px-5 py-3">Diferencia</th>
                                <th class="px-5 py-3">Valor diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eee9df]">
                            @foreach ($countRun->items as $item)
                                <tr>
                                    <td class="px-5 py-3">{{ $item->producto_nombre }} <span class="text-xs text-[#8c8679]">· {{ $item->producto_codigo }}</span></td>
                                    <td class="px-5 py-3">{{ $item->stock_sistema }}</td>
                                    <td class="px-5 py-3">{{ $item->cantidad_contada }}</td>
                                    <td class="px-5 py-3 font-semibold {{ $item->diferencia < 0 ? 'text-rose-700' : ($item->diferencia > 0 ? 'text-emerald-700' : 'text-[#716d65]') }}">{{ $item->diferencia > 0 ? '+' : '' }}{{ $item->diferencia }}</td>
                                    <td class="px-5 py-3">${{ number_format(abs($item->diferencia) * (float) $item->costo_unitario, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($countRun->nota)
                    <p class="border-t border-[#eee9df] px-5 py-4 text-sm text-[#716d65]">{{ $countRun->nota }}</p>
                @endif
            </section>
        @endif
    </main>
</x-app-layout>
