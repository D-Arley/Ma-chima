<x-app-layout>
    <style>
        @page {
            size: auto;
            margin: 4mm;
        }

        @media screen {
            .thermal-receipt {
                max-width: 76mm;
            }
        }

        @media print {
            html,
            body {
                width: 100%;
                min-height: 0;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            nav,
            .no-print {
                display: none !important;
            }

            #sale-receipt-page {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .thermal-receipt {
                width: 100% !important;
                max-width: 76mm;
                margin: 0 auto !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                color: #111 !important;
                font-size: 9pt;
            }

            .receipt-item {
                break-inside: avoid;
            }
        }
    </style>

    <div id="sale-receipt-page" class="mx-auto max-w-3xl px-5 py-10 sm:px-8">
        <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('ventas.index') }}" class="text-sm font-semibold text-[#8d6929]">← Volver a ventas</a>
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-xs text-[#8c8679]">En impresión, selecciona rollo de 58 mm u 80 mm.</p>
                <button type="button" onclick="window.print()" class="rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Imprimir recibo</button>
            </div>
        </div>

        <section id="sale-receipt" class="thermal-receipt mx-auto rounded-2xl border border-[#e7e0d2] bg-white p-6 shadow-sm sm:p-8">
            <header class="border-b border-dashed border-[#aaa] pb-4 text-center">
                <p class="font-serif text-2xl font-bold tracking-wide">MA'CHIMA</p>
                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[#555]">Ropa y calzado</p>
                <p class="mt-3 text-[0.7rem] font-semibold uppercase tracking-[0.12em]">Comprobante de venta</p>
                <p class="mt-1 break-all font-mono text-sm font-bold">{{ $venta->comprobante }}</p>
            </header>

            <section class="space-y-1 border-b border-dashed border-[#aaa] py-3 text-xs">
                <div class="flex justify-between gap-3"><span>Fecha</span><strong>{{ $venta->fecha->format('d/m/Y H:i:s') }}</strong></div>
                <div class="flex justify-between gap-3"><span>Atendido por</span><strong class="text-right">{{ $venta->user->name }}</strong></div>
                <div class="flex justify-between gap-3"><span>Cliente</span><strong>Consumidor final</strong></div>
                <div class="flex justify-between gap-3"><span>Forma de pago</span><strong>{{ ucfirst($venta->metodo_pago) }}</strong></div>
            </section>

            <section class="border-b border-dashed border-[#aaa] py-3">
                <h1 class="mb-3 text-xs font-bold uppercase tracking-[0.12em]">Detalle de compra</h1>
                @foreach ($venta->detalles as $detalle)
                    <article class="receipt-item border-t border-dotted border-[#ccc] py-2 first:border-0 first:pt-0">
                        <p class="text-xs font-bold">{{ $detalle->producto_nombre ?? $detalle->producto->nombre }}</p>
                        <p class="mt-0.5 text-[0.65rem] text-[#555]">{{ $detalle->producto_codigo ?? $detalle->producto->codigo }} · Talla {{ $detalle->producto_talla ?? $detalle->producto->talla }}</p>
                        <div class="mt-1 flex justify-between gap-3 text-xs">
                            <span>{{ $detalle->cantidad }} × ${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</span>
                            <strong>${{ number_format($detalle->subtotal, 0, ',', '.') }}</strong>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="space-y-2 border-b border-dashed border-[#aaa] py-3 text-xs">
                <div class="flex justify-between gap-3"><span>Subtotal artículos (IVA incluido)</span><strong>${{ number_format($venta->detalles->sum('subtotal'), 0, ',', '.') }}</strong></div>
                @if ($venta->descuento > 0)
                    <div class="flex justify-between gap-3"><span>Descuento</span><strong>−${{ number_format($venta->descuento, 0, ',', '.') }}</strong></div>
                @endif
                <div class="flex justify-between gap-3"><span>Base gravable (sin IVA)</span><strong>${{ number_format($venta->subtotal, 0, ',', '.') }}</strong></div>
                <div class="flex justify-between gap-3"><span>IVA incluido (19%)</span><strong>${{ number_format($venta->iva, 0, ',', '.') }}</strong></div>
                <div class="flex items-end justify-between gap-3 border-t border-[#777] pt-2">
                    <span class="text-sm font-bold uppercase">Total pagado</span>
                    <strong class="text-xl">${{ number_format($venta->total, 0, ',', '.') }}</strong>
                </div>
            </section>

            <section class="space-y-1 border-b border-dashed border-[#aaa] py-3 text-xs">
                @if ($venta->metodo_pago === 'efectivo')
                    <div class="flex justify-between gap-3"><span>Efectivo recibido</span><strong>${{ number_format($venta->efectivo_recibido, 0, ',', '.') }}</strong></div>
                    <div class="flex justify-between gap-3"><span>Cambio entregado</span><strong>${{ number_format($venta->cambio, 0, ',', '.') }}</strong></div>
                @else
                    <p class="text-center">Pago recibido por transferencia.</p>
                @endif
                <p class="pt-1 text-center text-[0.65rem] text-[#555]">Valores expresados en pesos colombianos (COP).</p>
            </section>

            <footer class="space-y-2 pt-4 text-center">
                <p class="text-xs font-semibold">¡Gracias por comprar en Ma'chima!</p>
                <p class="text-[0.6rem] leading-relaxed text-[#555]">Comprobante interno de venta. No es factura electrónica ni documento tributario equivalente.</p>
            </footer>
        </section>
    </div>
</x-app-layout>
