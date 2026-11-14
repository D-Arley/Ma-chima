<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Ma'chima | Inventario y ventas</title>
        <meta name="description" content="Sistema de inventario y ventas de Ma'chima, moda con identidad y una gestión más clara.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f6f3eb] font-sans text-[#171615] antialiased">
        <header class="relative z-10 border-b border-white/10 bg-[#0c0b0a] text-white">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3 sm:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Ma'chima, inicio">
                    <img src="{{ asset('images/ma-chima-logo.png') }}" alt="" class="h-14 w-14 rounded-full object-cover ring-1 ring-[#c99c42]/60">
                    <span class="text-lg font-semibold tracking-[0.18em]">MA'CHIMA</span>
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-full bg-[#d2aa5c] px-5 py-2.5 text-sm font-semibold text-[#17130b] transition hover:bg-[#e2c47e]">
                            Ir al panel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full border border-white/25 px-5 py-2.5 text-sm font-semibold text-white transition hover:border-[#d2aa5c] hover:text-[#e3c47b]">
                            Iniciar sesión
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        <main>
            <section class="relative isolate overflow-hidden bg-[#0c0b0a] text-white">
                <div class="absolute -right-36 -top-32 -z-10 h-[34rem] w-[34rem] rounded-full bg-[#a87623]/15 blur-3xl"></div>
                <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.05fr_0.95fr] lg:py-28">
                    <div class="max-w-2xl">
                        <p class="mb-6 inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.28em] text-[#dfbd76]">
                            <span class="h-px w-9 bg-[#c99c42]"></span>
                            Estilo con identidad
                        </p>
                        <h1 class="font-serif text-5xl leading-[1.06] tracking-tight sm:text-6xl lg:text-7xl">
                            Moda que se nota.<br>
                            <span class="italic text-[#d9b866]">Gestión que inspira.</span>
                        </h1>
                        <p class="mt-7 max-w-xl text-base leading-7 text-white/65 sm:text-lg">
                            Bienvenido a Ma'chima: una marca propia de ropa y calzado, con una experiencia pensada para cuidar cada detalle del negocio.
                        </p>
                        <div class="mt-9 flex flex-wrap items-center gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 rounded-full bg-[#d2aa5c] px-6 py-3.5 text-sm font-bold text-[#17130b] shadow-lg shadow-[#c99c42]/10 transition hover:-translate-y-0.5 hover:bg-[#e2c47e]">
                                    Continuar al panel
                                    <span aria-hidden="true">→</span>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-3 rounded-full bg-[#d2aa5c] px-6 py-3.5 text-sm font-bold text-[#17130b] shadow-lg shadow-[#c99c42]/10 transition hover:-translate-y-0.5 hover:bg-[#e2c47e]">
                                    Entrar al sistema
                                    <span aria-hidden="true">→</span>
                                </a>
                            @endauth
                            <a href="#roles" class="rounded-full px-4 py-3 text-sm font-medium text-white/70 transition hover:text-white">
                                Conoce los perfiles
                            </a>
                        </div>
                        <div class="mt-12 flex flex-wrap gap-x-8 gap-y-3 border-t border-white/10 pt-6 text-xs uppercase tracking-[0.16em] text-white/45">
                            <span>Ropa</span>
                            <span>Calzado</span>
                            <span>Hecho para crecer</span>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-[31rem]">
                        <div class="absolute -inset-4 rounded-[2rem] border border-[#c99c42]/20"></div>
                        <div class="relative overflow-hidden rounded-[1.6rem] border border-white/10 bg-[#080808] p-4 shadow-2xl shadow-black/50 sm:p-6">
                            <img src="{{ asset('images/ma-chima-logo.png') }}" alt="Logo Ma'chima: corona, inicial M y bolsa de compras" class="aspect-square w-full rounded-[1.1rem] object-contain">
                            <div class="flex items-center justify-between border-t border-white/10 px-2 pt-4">
                                <span class="text-xs uppercase tracking-[0.2em] text-white/50">Colección propia</span>
                                <span class="text-xs font-semibold tracking-[0.14em] text-[#d9b866]">MA'CHIMA</span>
                            </div>
                        </div>
                        <div class="absolute -bottom-5 -left-5 hidden rounded-2xl border border-[#d6b668]/30 bg-[#171511] px-5 py-4 shadow-xl sm:block">
                            <p class="text-[0.65rem] uppercase tracking-[0.2em] text-white/45">Nuestra esencia</p>
                            <p class="mt-1 font-serif text-lg text-[#e4c577]">Elegancia en cada detalle</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="roles" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-[#a47b31]">Un equipo, tres perfiles</p>
                    <h2 class="mt-4 font-serif text-4xl tracking-tight sm:text-5xl">Cada tarea en buenas manos.</h2>
                    <p class="mt-5 leading-7 text-[#6e6a62]">Los accesos se organizan según la responsabilidad de cada persona en la tienda.</p>
                </div>

                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    <article class="rounded-3xl border border-[#e7e0d2] bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#171511] font-serif text-xl text-[#e4c577]">01</span>
                        <h3 class="mt-6 font-serif text-2xl">Administrador</h3>
                        <p class="mt-3 text-sm leading-6 text-[#6e6a62]">Visión completa del negocio y acceso a todas las áreas del sistema.</p>
                        <p class="mt-5 border-t border-[#eee9df] pt-4 text-xs font-semibold uppercase tracking-[0.15em] text-[#a47b31]">Control general</p>
                    </article>
                    <article class="rounded-3xl border border-[#e7e0d2] bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f2ead8] font-serif text-xl text-[#a47b31]">02</span>
                        <h3 class="mt-6 font-serif text-2xl">Vendedor</h3>
                        <p class="mt-3 text-sm leading-6 text-[#6e6a62]">Enfoque en ventas, conteos y recepción de mercancía para el punto de venta.</p>
                        <p class="mt-5 border-t border-[#eee9df] pt-4 text-xs font-semibold uppercase tracking-[0.15em] text-[#a47b31]">Atención y ventas</p>
                    </article>
                    <article class="rounded-3xl border border-[#e7e0d2] bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f2ead8] font-serif text-xl text-[#a47b31]">03</span>
                        <h3 class="mt-6 font-serif text-2xl">Bodeguero</h3>
                        <p class="mt-3 text-sm leading-6 text-[#6e6a62]">Organización interna, ingreso, conteo y entrega de mercancía.</p>
                        <p class="mt-5 border-t border-[#eee9df] pt-4 text-xs font-semibold uppercase tracking-[0.15em] text-[#a47b31]">Orden de bodega</p>
                    </article>
                </div>
            </section>

            <section class="border-y border-[#e8e0d0] bg-[#eee8dc]">
                <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:py-20">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-[#a47b31]">Lo que nos representa</p>
                        <h2 class="mt-4 font-serif text-4xl leading-tight">Cuatro categorías.<br>Una misma identidad.</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach (['Zapatos', 'Pantalones', 'Camisas', 'Camisetas'] as $index => $category)
                            <div class="rounded-2xl border border-[#dfd5c2] bg-[#f8f5ef] p-5 sm:p-6">
                                <span class="text-xs font-semibold tracking-[0.16em] text-[#b08a43]">0{{ $index + 1 }}</span>
                                <p class="mt-8 font-serif text-lg sm:text-xl">{{ $category }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                <div class="flex flex-col gap-6 rounded-[2rem] bg-[#11100e] px-7 py-10 text-white sm:px-12 sm:py-12 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#d9b866]">Ma'chima</p>
                        <h2 class="mt-3 font-serif text-3xl sm:text-4xl">El siguiente paso empieza aquí.</h2>
                    </div>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center gap-3 rounded-full bg-[#d2aa5c] px-6 py-3.5 text-sm font-bold text-[#17130b] transition hover:bg-[#e2c47e]">
                            Abrir mi panel <span aria-hidden="true">→</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex shrink-0 items-center justify-center gap-3 rounded-full bg-[#d2aa5c] px-6 py-3.5 text-sm font-bold text-[#17130b] transition hover:bg-[#e2c47e]">
                            Iniciar sesión <span aria-hidden="true">→</span>
                        </a>
                    @endauth
                </div>
            </section>
        </main>

        <footer class="border-t border-[#e8e0d0] px-5 py-6 text-center text-xs tracking-wide text-[#777167]">
            © {{ now()->year }} Ma'chima. Estilo con identidad.
        </footer>
    </body>
</html>
