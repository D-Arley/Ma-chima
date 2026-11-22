<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b border-white/10 bg-[#0c0b0a] text-white shadow-lg shadow-black/5">
    @php
        $activeLinkClasses = 'border-[#d2aa5c] text-[#e4c577]';
        $inactiveLinkClasses = 'border-transparent text-white/70 hover:text-[#e4c577]';
        $mobileActiveLinkClasses = 'bg-white/10 text-[#e4c577]';
        $mobileInactiveLinkClasses = 'text-white/80 hover:bg-white/5';
    @endphp

    <div class="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between px-5 sm:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="h-11 w-11 rounded-full" />
                <span class="hidden text-sm font-bold tracking-[0.18em] sm:block">MA'CHIMA</span>
            </a>
            <a href="{{ route('dashboard') }}" @class(['hidden border-b-2 py-6 text-sm font-medium sm:block', $activeLinkClasses => request()->routeIs('dashboard'), $inactiveLinkClasses => ! request()->routeIs('dashboard')])>
                Panel de trabajo
            </a>
            <div class="hidden items-center gap-5 lg:flex">
                @can('view-catalog')
                    <a href="{{ route('catalogo.index') }}" @class(['border-b-2 py-6 text-sm', $activeLinkClasses => request()->routeIs('catalogo.*'), $inactiveLinkClasses => ! request()->routeIs('catalogo.*')])>Catálogo</a>
                @endcan
                @can('count-inventory')
                    <a href="{{ route('logistica.index') }}" @class(['border-b-2 py-6 text-sm', $activeLinkClasses => request()->routeIs('logistica.*'), $inactiveLinkClasses => ! request()->routeIs('logistica.*')])>Logística</a>
                @endcan
                @can('manage-inventory')
                    <a href="{{ route('inventario.index') }}" @class(['border-b-2 py-6 text-sm', $activeLinkClasses => request()->routeIs('inventario.*'), $inactiveLinkClasses => ! request()->routeIs('inventario.*')])>Inventario</a>
                @endcan
                @can('open-pos')
                    <a href="{{ route('ventas.index') }}" @class(['border-b-2 py-6 text-sm', $activeLinkClasses => request()->routeIs('ventas.*'), $inactiveLinkClasses => ! request()->routeIs('ventas.*')])>Ventas</a>
                    <a href="{{ route('caja.index') }}" @class(['border-b-2 py-6 text-sm', $activeLinkClasses => request()->routeIs('caja.*'), $inactiveLinkClasses => ! request()->routeIs('caja.*')])>Caja</a>
                @endcan
                @can('manage-users')
                    <a href="{{ route('admin.usuarios.index') }}" @class(['border-b-2 py-6 text-sm', $activeLinkClasses => request()->routeIs('admin.usuarios.*'), $inactiveLinkClasses => ! request()->routeIs('admin.usuarios.*')])>Usuarios</a>
                    <a href="{{ route('movimientos.index') }}" @class(['border-b-2 py-6 text-sm', $activeLinkClasses => request()->routeIs('movimientos.*'), $inactiveLinkClasses => ! request()->routeIs('movimientos.*')])>Movimientos</a>
                @endcan
            </div>
        </div>

        <div class="hidden items-center gap-4 sm:flex">
            <div class="text-right">
                <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                <p class="text-xs text-white/50">
                    {{ match (auth()->user()->role) { 'admin' => 'Administrador', 'vendedor' => 'Vendedor', 'bodeguero' => 'Bodeguero', default => 'Usuario' } }}
                </p>
            </div>
            <x-dropdown align="right" width="48" contentClasses="py-1 bg-white rounded-xl shadow-lg">
                <x-slot name="trigger">
                    <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-[#d2aa5c]/50 bg-[#201d17] text-sm font-semibold text-[#e4c577] transition hover:bg-[#302a20]" aria-label="Abrir menú de usuario">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        <span class="sr-only">Opciones de usuario</span>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')" class="text-[#302d27] hover:bg-[#f8f6f0]">
                        Mi perfil
                    </x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-[#302d27] transition hover:bg-[#f8f6f0]">
                            Cerrar sesión
                        </button>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 text-white sm:hidden"
            x-on:click="open = !open"
            x-bind:aria-expanded="open"
            aria-controls="mobile-navigation"
            aria-label="Abrir menú"
        >
            <svg x-show="!open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" stroke-width="1.7" />
            </svg>
            <svg x-show="open" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" stroke-width="1.7" />
            </svg>
        </button>
    </div>

    <div id="mobile-navigation" x-show="open" x-transition x-cloak class="border-t border-white/10 px-5 py-4 sm:hidden">
        <div class="mb-4">
            <p class="font-semibold">{{ auth()->user()->name }}</p>
            <p class="mt-1 text-xs text-[#e4c577]">
                {{ match (auth()->user()->role) { 'admin' => 'Administrador', 'vendedor' => 'Vendedor', 'bodeguero' => 'Bodeguero', default => 'Usuario' } }}
            </p>
        </div>
        <div class="space-y-1">
            <a href="{{ route('dashboard') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('dashboard'), $mobileInactiveLinkClasses => ! request()->routeIs('dashboard')])>Panel de trabajo</a>
            <a href="{{ route('profile.edit') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('profile.*'), $mobileInactiveLinkClasses => ! request()->routeIs('profile.*')])>Mi perfil</a>
            @can('view-catalog')
                <a href="{{ route('catalogo.index') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('catalogo.*'), $mobileInactiveLinkClasses => ! request()->routeIs('catalogo.*')])>Catálogo</a>
            @endcan
            @can('count-inventory')
                <a href="{{ route('logistica.index') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('logistica.*'), $mobileInactiveLinkClasses => ! request()->routeIs('logistica.*')])>Logística</a>
            @endcan
            @can('manage-inventory')
                <a href="{{ route('inventario.index') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('inventario.*'), $mobileInactiveLinkClasses => ! request()->routeIs('inventario.*')])>Inventario</a>
            @endcan
            @can('open-pos')
                <a href="{{ route('ventas.index') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('ventas.*'), $mobileInactiveLinkClasses => ! request()->routeIs('ventas.*')])>Ventas</a>
                <a href="{{ route('caja.index') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('caja.*'), $mobileInactiveLinkClasses => ! request()->routeIs('caja.*')])>Caja</a>
            @endcan
            @can('manage-users')
                <a href="{{ route('admin.usuarios.index') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('admin.usuarios.*'), $mobileInactiveLinkClasses => ! request()->routeIs('admin.usuarios.*')])>Usuarios</a>
                <a href="{{ route('movimientos.index') }}" @class(['block rounded-lg px-3 py-2.5 text-sm', $mobileActiveLinkClasses => request()->routeIs('movimientos.*'), $mobileInactiveLinkClasses => ! request()->routeIs('movimientos.*')])>Movimientos</a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full rounded-lg px-3 py-2.5 text-left text-sm text-white/80 hover:bg-white/5">Cerrar sesión</button>
            </form>
        </div>
    </div>
</nav>
