<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', "Ma'chima") }} | Acceso</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f3eb] font-sans text-[#171615] antialiased">
        <main class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
            <section class="relative hidden min-h-screen overflow-hidden bg-[#0c0b0a] px-10 py-9 text-white lg:flex lg:flex-col lg:justify-between xl:px-16">
                <div class="absolute -right-32 top-1/4 h-96 w-96 rounded-full bg-[#a87623]/15 blur-3xl"></div>
                <a href="{{ url('/') }}" class="relative flex items-center gap-3">
                    <img src="{{ asset('images/ma-chima-logo.png') }}" alt="" class="h-14 w-14 rounded-full object-cover ring-1 ring-[#c99c42]/60">
                    <span class="text-sm font-semibold tracking-[0.2em]">MA'CHIMA</span>
                </a>
                <div class="relative mx-auto w-full max-w-lg py-12">
                    <div class="rounded-[2rem] border border-white/10 bg-black/30 p-5 shadow-2xl shadow-black/40">
                        <img src="{{ asset('images/ma-chima-logo.png') }}" alt="Logo de Ma'chima" class="mx-auto aspect-square w-full max-w-sm rounded-[1.5rem] object-contain">
                    </div>
                    <p class="mt-8 text-center font-serif text-3xl text-[#e4c577]">Elegancia en cada detalle.</p>
                    <p class="mt-3 text-center text-sm text-white/55">Inventario y ventas con el sello de Ma'chima.</p>
                </div>
                <p class="relative text-xs tracking-wide text-white/40">© {{ now()->year }} Ma'chima · Acceso al equipo</p>
            </section>

            <section class="flex min-h-screen flex-col px-5 py-6 sm:px-10 lg:px-14 xl:px-20">
                <div class="flex items-center justify-between lg:justify-end">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 lg:hidden">
                        <img src="{{ asset('images/ma-chima-logo.png') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                        <span class="text-xs font-bold tracking-[0.18em]">MA'CHIMA</span>
                    </a>
                    <a href="{{ url('/') }}" class="text-sm font-medium text-[#716d65] transition hover:text-[#a47b31]">
                        <span aria-hidden="true">←</span> Volver al inicio
                    </a>
                </div>

                <div class="flex flex-1 items-center justify-center py-10">
                    <div class="w-full max-w-md">
                        <div class="mb-8">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-[#a47b31]">Portal del equipo</p>
                            <h1 class="mt-3 font-serif text-4xl tracking-tight text-[#171615] sm:text-5xl">Qué bueno verte.</h1>
                            <p class="mt-3 text-sm leading-6 text-[#716d65]">Ingresa con la cuenta asignada para continuar al sistema.</p>
                        </div>
                        <div class="rounded-3xl border border-[#e7e0d2] bg-white p-6 shadow-[0_24px_70px_-38px_rgba(27,22,12,0.28)] sm:p-8">
                            {{ $slot }}
                        </div>
                        <p class="mt-6 text-center text-xs leading-5 text-[#89847a]">
                            Acceso reservado para el equipo de Ma'chima.
                        </p>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
