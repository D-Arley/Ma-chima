<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Caja de {{ now()->format('d/m/Y') }}</p>
            <h1 class="mt-1 font-serif text-3xl">Balance financiero</h1>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        @if (session('success'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

        @if (! $caja)
            <section class="rounded-3xl border border-[#e7e0d2] bg-white p-7 sm:p-9">
                <h2 class="font-serif text-3xl">Abrir caja del día</h2>
                <p class="mt-3 max-w-xl text-sm leading-6 text-[#716d65]">Configure el efectivo físico disponible al iniciar la jornada. El mínimo obligatorio es 200.000 COP.</p>
                <form method="POST" action="{{ route('caja.open') }}" class="mt-6 max-w-md">
                    @csrf
                    <label for="opening_cash" class="text-sm font-semibold">Monto inicial COP</label>
                    <input id="opening_cash" name="opening_cash" type="number" min="200000" step="1000" value="200000" required class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3">
                    <button class="mt-4 w-full rounded-full bg-[#171511] px-5 py-3.5 text-sm font-semibold text-white">Abrir caja</button>
                </form>
            </section>
        @else
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-[#171511] p-5 text-white"><p class="text-xs uppercase tracking-[0.15em] text-white/50">Caja física</p><p class="mt-3 font-serif text-3xl">${{ number_format($caja->cash_balance, 0, ',', '.') }}</p></div>
                <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5"><p class="text-xs uppercase tracking-[0.15em] text-[#8c8679]">Ventas efectivo hoy</p><p class="mt-3 font-serif text-3xl">${{ number_format($efectivoHoy, 0, ',', '.') }}</p></div>
                <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5"><p class="text-xs uppercase tracking-[0.15em] text-[#8c8679]">Transferencias hoy</p><p class="mt-3 font-serif text-3xl">${{ number_format($transferenciasHoy, 0, ',', '.') }}</p></div>
                <div class="rounded-2xl border border-[#e7e0d2] bg-[#fbf6e9] p-5"><p class="text-xs uppercase tracking-[0.15em] text-[#8d6929]">Cuenta bancaria</p><p class="mt-3 font-serif text-3xl">${{ number_format($caja->bank_balance, 0, ',', '.') }}</p></div>
            </section>
        @endif

        @php
            $maxVentasSemana = max(1, (float) $semanal->max('ventas'));
        @endphp
        <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_340px]">
                <div class="rounded-2xl border border-[#e7e0d2] bg-white p-6">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Semana calendario · lunes a domingo</p>
                            <h2 class="mt-1 font-serif text-2xl">Rendimiento de ventas</h2>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-[#8c8679]">Total semanal</p>
                            <p class="mt-1 font-serif text-2xl">${{ number_format($totalSemana, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="mt-7 flex h-52 items-end gap-3 border-b border-[#e7e0d2] px-2">
                        @foreach ($semanal as $dia)
                            @php $height = $dia['ventas'] > 0 ? max(12, ($dia['ventas'] / $maxVentasSemana) * 100) : 5; @endphp
                            <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-[0.65rem] font-semibold text-[#716d65]">${{ number_format($dia['ventas'] / 1000, 0) }}k</span>
                                <div class="w-full max-w-12 rounded-t-lg {{ $dia['fecha'] === now()->format('d/m') ? 'bg-[#171511]' : 'bg-[#d6b668]' }}" style="height: {{ $height }}%"></div>
                                <div class="pb-2 text-center">
                                    <p class="text-xs font-bold {{ $dia['fecha'] === now()->format('d/m') ? 'text-[#171511]' : 'text-[#716d65]' }}">{{ $dia['dia'] }}</p>
                                    <p class="text-[0.65rem] text-[#9a958a]">{{ $dia['fecha'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="rounded-2xl border border-[#e7e0d2] bg-[#171511] p-6 text-white">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#d9b866]">Ganancia neta</p>
                    <p class="mt-3 font-serif text-4xl">${{ number_format($gananciaSemana, 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm leading-6 text-white/55">Resultado estimado de la semana fuera del IVA y costo de mercancía.</p>
                    <div class="mt-6 border-t border-white/10 pt-5">
                        <p class="text-xs text-white/45">Desglose del mes · ventas y ganancia neta</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($semanasMes as $semana)
                                <div class="flex items-center justify-between gap-3 rounded-lg bg-white/5 px-3 py-2">
                                    <div><p class="text-xs font-semibold">{{ $semana['semana'] }}</p><p class="mt-0.5 text-[0.65rem] text-white/45">{{ $semana['rango'] }}</p></div>
                                    <div class="text-right">
                                        <strong class="block text-sm">${{ number_format($semana['ventas'], 0, ',', '.') }}</strong>
                                        <span class="text-[0.65rem] text-[#d9b866]">Ganancia ${{ number_format($semana['ganancia'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </aside>
        </section>

        @if ($caja)
            <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="rounded-2xl border border-[#e7e0d2] bg-white p-6">
                    <div class="flex items-end justify-between gap-4">
                        <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Ventas</p><h2 class="mt-1 font-serif text-2xl">Ventas registradas hoy</h2></div>
                        <p class="text-sm text-[#716d65]">{{ $ventasHoy->count() }} transacciones</p>
                    </div>
                    <div class="mt-6 space-y-3">
                        @forelse ($ventasHoy as $venta)
                            <a href="{{ route('ventas.show', $venta) }}" class="flex items-center justify-between rounded-xl bg-[#f8f6f0] px-4 py-3">
                                <div><p class="font-semibold">{{ $venta->comprobante }}</p><p class="mt-1 text-xs text-[#8c8679]">{{ $venta->fecha->format('H:i') }} · {{ ucfirst($venta->metodo_pago) }}</p></div>
                                <strong>${{ number_format($venta->total, 0, ',', '.') }}</strong>
                            </a>
                        @empty
                            <p class="rounded-xl border border-dashed border-[#ddd4c3] p-6 text-center text-sm text-[#8c8679]">Aún no hay ventas en esta caja.</p>
                        @endforelse
                    </div>
                </div>

                <aside class="rounded-2xl border border-[#e7e0d2] bg-white p-6">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a47b31]">Traslado manual</p>
                    <h2 class="mt-1 font-serif text-2xl">Cuenta bancaria</h2>
                    <p class="mt-3 text-sm leading-6 text-[#716d65]">Puede transferir como máximo <strong>${{ number_format(max(0, $caja->cash_balance - 200000), 0, ',', '.') }}</strong>, conservando siempre los 200.000 COP mínimos.</p>
                    <form method="POST" action="{{ route('caja.transfer') }}" class="mt-5">
                        @csrf
                        <label for="amount" class="text-sm font-semibold">Monto a trasladar</label>
                        <input id="amount" name="amount" type="number" min="1000" max="{{ max(0, $caja->cash_balance - 200000) }}" step="1000" required class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3">
                        <button class="mt-4 w-full rounded-full bg-[#171511] px-5 py-3.5 text-sm font-semibold text-white">Transferir a cuenta</button>
                    </form>
                </aside>
            </section>
        @endif
    </main>
</x-app-layout>
