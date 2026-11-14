@php
    $roleName = match (auth()->user()->role) {
        'admin' => 'Administrador',
        'vendedor' => 'Vendedor',
        'bodeguero' => 'Bodeguero',
        default => 'Usuario',
    };

    $roleSummary = match (auth()->user()->role) {
        'admin' => 'Tienes acceso completo a la operación de Ma’chima.',
        'vendedor' => 'Tu perfil está enfocado en ventas y atención de mercancía.',
        'bodeguero' => 'Tu perfil está enfocado en el control y movimiento de bodega.',
        default => 'Consulta con el administrador los accesos de tu cuenta.',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Panel de trabajo</p>
                <h1 class="mt-1 font-serif text-3xl text-[#171615]">Hola, {{ auth()->user()->name }}.</h1>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-[#e7d8b5] bg-[#fbf6e9] px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-[#8d6929]">
                <span class="h-2 w-2 rounded-full bg-[#c99c42]"></span>
                {{ $roleName }}
            </span>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-9 sm:px-8 sm:py-12">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-[#11100e] px-6 py-8 text-white shadow-xl shadow-[#1c170d]/10 sm:px-10 sm:py-10">
            <div class="absolute -right-20 -top-32 -z-10 h-80 w-80 rounded-full bg-[#a87623]/20 blur-3xl"></div>
            <div class="grid items-center gap-8 md:grid-cols-[1fr_auto]">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#d9b866]">Ma'chima · Sistema empresarial</p>
                    <h2 class="mt-4 font-serif text-3xl leading-tight sm:text-4xl">Una tienda con identidad, un equipo con propósito.</h2>
                    <p class="mt-4 max-w-xl text-sm leading-6 text-white/60">{{ $roleSummary }}</p>
                </div>
                <img src="{{ asset('images/ma-chima-logo.png') }}" alt="" class="mx-auto h-32 w-32 rounded-full object-cover ring-1 ring-[#c99c42]/50 md:mx-0">
            </div>
        </section>

        <section class="mt-10">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Accesos del perfil</p>
                <h2 class="mt-2 font-serif text-3xl text-[#171615]">Tus responsabilidades</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#716d65]">Los permisos se asignan según tu rol. Las secciones del sistema se irán habilitando en este mismo panel.</p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @if (auth()->user()->isAdmin())
                    @foreach ([
                        ['title' => 'Administración general', 'detail' => 'Acceso total a las áreas de la tienda.'],
                        ['title' => 'Ventas e inventario', 'detail' => 'Supervisión de la operación de caja y bodega.'],
                        ['title' => 'Equipo y alertas', 'detail' => 'Gestión de usuarios e información del negocio.'],
                    ] as $item)
                        <article class="rounded-2xl border border-[#e7e0d2] bg-white p-6 shadow-sm">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#171511] text-[#e4c577]" aria-hidden="true">✦</span>
                            <h3 class="mt-5 font-semibold text-[#24211c]">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-[#716d65]">{{ $item['detail'] }}</p>
                        </article>
                    @endforeach
                @elseif (auth()->user()->hasRole('vendedor'))
                    @foreach ([
                        ['title' => 'Administrar ventas', 'detail' => 'Acceso asignado a la operación de ventas.'],
                        ['title' => 'Hacer conteos', 'detail' => 'Verificación de existencias junto al equipo.'],
                        ['title' => 'Recibir mercancía', 'detail' => 'Recepción de productos entregados desde bodega.'],
                    ] as $item)
                        <article class="rounded-2xl border border-[#e7e0d2] bg-white p-6 shadow-sm">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#f2ead8] text-[#a47b31]" aria-hidden="true">✓</span>
                            <h3 class="mt-5 font-semibold text-[#24211c]">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-[#716d65]">{{ $item['detail'] }}</p>
                        </article>
                    @endforeach
                @elseif (auth()->user()->hasRole('bodeguero'))
                    @foreach ([
                        ['title' => 'Ingresar mercancía', 'detail' => 'Registro de productos recibidos en bodega.'],
                        ['title' => 'Gestionar inventario', 'detail' => 'Organización interna y actualización de productos.'],
                        ['title' => 'Contar y entregar', 'detail' => 'Conteos de existencias y entregas al vendedor.'],
                    ] as $item)
                        <article class="rounded-2xl border border-[#e7e0d2] bg-white p-6 shadow-sm">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#f2ead8] text-[#a47b31]" aria-hidden="true">✓</span>
                            <h3 class="mt-5 font-semibold text-[#24211c]">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-[#716d65]">{{ $item['detail'] }}</p>
                        </article>
                    @endforeach
                @else
                    <article class="rounded-2xl border border-[#e7e0d2] bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-[#24211c]">Rol pendiente de asignación</h3>
                        <p class="mt-2 text-sm leading-6 text-[#716d65]">Contacta al administrador de Ma'chima para revisar tu cuenta.</p>
                    </article>
                @endif
            </div>
        </section>

        <section class="mt-10 rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:p-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Catálogo</p>
                    <h2 class="mt-2 font-serif text-2xl text-[#171615]">Las categorías de Ma'chima</h2>
                </div>
                <p class="text-sm text-[#716d65]">Ropa y calzado para cada estilo.</p>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach (['Zapatos', 'Pantalones', 'Camisas', 'Camisetas'] as $index => $category)
                    <div class="rounded-xl bg-[#f8f6f0] px-4 py-5">
                        <span class="text-[0.65rem] font-bold tracking-[0.18em] text-[#b08a43]">0{{ $index + 1 }}</span>
                        <p class="mt-3 font-medium text-[#302d27]">{{ $category }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</x-app-layout>
