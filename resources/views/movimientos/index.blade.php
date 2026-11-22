<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Auditoría</p>
                <h1 class="mt-1 font-serif text-3xl">Movimientos y transacciones</h1>
            </div>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('movimientos.export.excel', request()->query()) }}" class="rounded-full border border-[#d8cfbe] px-4 py-2.5 text-sm font-semibold">Excel (.xls)</a>
                <a href="{{ route('movimientos.export.csv', request()->query()) }}" class="rounded-full border border-[#d8cfbe] px-4 py-2.5 text-sm font-semibold">CSV</a>
                <a href="{{ route('movimientos.export.pdf', request()->query()) }}" target="_blank" class="rounded-full bg-[#171511] px-4 py-2.5 text-sm font-semibold text-white">PDF</a>
            </div>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5"><p class="text-xs uppercase tracking-[0.15em] text-[#8c8679]">Ventas</p><p class="mt-2 font-serif text-3xl">${{ number_format($resumen['ventas'], 0, ',', '.') }}</p></div>
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5"><p class="text-xs uppercase tracking-[0.15em] text-[#8c8679]">Ganancia neta</p><p class="mt-2 font-serif text-3xl text-emerald-700">${{ number_format($resumen['ganancia'], 0, ',', '.') }}</p></div>
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5"><p class="text-xs uppercase tracking-[0.15em] text-[#8c8679]">IVA registrado</p><p class="mt-2 font-serif text-3xl">${{ number_format($resumen['iva'], 0, ',', '.') }}</p></div>
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5"><p class="text-xs uppercase tracking-[0.15em] text-[#8c8679]">Transacciones</p><p class="mt-2 font-serif text-3xl">{{ $resumen['transacciones'] }}</p></div>
        </section>

        @php $max = max(1, (float) $semanal->max('total')); @endphp
        <section class="mt-6 grid gap-6 lg:grid-cols-[1fr_280px]">
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-6">
                <h2 class="font-serif text-2xl">Ventas de la semana</h2>
                <div class="mt-6 flex h-40 items-end gap-3 border-b border-[#e7e0d2]">
                    @foreach ($semanal as $dia)
                        @php $height = $dia['total'] > 0 ? max(10, ($dia['total'] / $max) * 100) : 4; @endphp
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                            <div class="w-full max-w-10 rounded-t-lg {{ $dia['fecha'] === now()->format('d/m') ? 'bg-[#171511]' : 'bg-[#d6b668]' }}" style="height: {{ $height }}%"></div>
                            <div class="pb-2 text-center"><p class="text-xs font-bold">{{ $dia['dia'] }}</p><p class="text-[0.65rem] text-[#9a958a]">{{ $dia['fecha'] }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <aside class="rounded-2xl bg-[#171511] p-6 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#d9b866]">Ganancia 7 días</p>
                <p class="mt-3 font-serif text-3xl">${{ number_format($semanal->sum('ganancia'), 0, ',', '.') }}</p>
                <p class="mt-3 text-sm leading-6 text-white/55">Margen neto después del costo y el IVA.</p>
            </aside>
        </section>

        @if ($errors->any())
            <div class="mt-8 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-[#e7e0d2] bg-white p-4 md:grid-cols-[1fr_180px_180px_auto]">
            <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Comprobante, producto, usuario o motivo..." class="rounded-xl border-[#ded8cc] px-4 py-3 text-sm">
            <label class="sr-only" for="desde">Fecha inicial</label>
            <input id="desde" type="date" name="desde" value="{{ $desde?->format('Y-m-d') }}" class="rounded-xl border-[#ded8cc] px-3 py-3 text-sm">
            <label class="sr-only" for="hasta">Fecha final</label>
            <input id="hasta" type="date" name="hasta" value="{{ $hasta?->format('Y-m-d') }}" class="rounded-xl border-[#ded8cc] px-3 py-3 text-sm">
            <button class="rounded-xl bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Filtrar</button>
        </form>

        <section class="mt-6 overflow-hidden rounded-2xl border border-[#e7e0d2] bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f8f6f0] text-xs uppercase tracking-[0.12em] text-[#777167]">
                        <tr>
                            <th class="px-5 py-4">Fecha</th>
                            <th class="px-5 py-4">Tipo</th>
                            <th class="px-5 py-4">Detalle</th>
                            <th class="px-5 py-4">Referencia</th>
                            <th class="px-5 py-4">Usuario</th>
                            <th class="px-5 py-4">Unidades / flujo</th>
                            <th class="px-5 py-4 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eee9df]">
                        @forelse ($movimientos as $movimiento)
                            <tr>
                                <td class="px-5 py-4 text-[#716d65]">{{ $movimiento['fecha']->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-[#f2ead8] px-3 py-1 text-xs font-bold text-[#8d6929]">{{ $movimiento['tipo'] }}</span></td>
                                <td class="px-5 py-4"><p class="font-semibold">{{ $movimiento['detalle'] }}</p><p class="mt-1 text-xs text-[#8c8679]">{{ $movimiento['motivo'] }}</p></td>
                                <td class="px-5 py-4">{{ $movimiento['referencia'] }}</td>
                                <td class="px-5 py-4">{{ $movimiento['usuario'] }}</td>
                                <td class="px-5 py-4 text-xs font-semibold">
                                    @if ($movimiento['tipo'] === 'Caja')
                                        {{ $movimiento['salida'] ? '-' : '+' }}${{ number_format($movimiento['salida'] ?? $movimiento['entrada'], 0, ',', '.') }} COP
                                    @elseif ($movimiento['salida'])
                                        -{{ $movimiento['salida'] }} und.
                                    @elseif ($movimiento['entrada'])
                                        +{{ $movimiento['entrada'] }} und.
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right font-semibold">${{ number_format($movimiento['valor'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-[#716d65]">No hay movimientos con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div class="mt-4">{{ $movimientos->links() }}</div>
    </main>
</x-app-layout>
