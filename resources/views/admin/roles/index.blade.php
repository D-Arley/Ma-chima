<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Seguridad y permisos</p>
            <h1 class="mt-1 font-serif text-3xl">Roles y responsabilidades</h1>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <section class="grid gap-5 lg:grid-cols-3">
            @foreach ($roles as $key => $role)
                <article class="rounded-3xl border border-[#e7e0d2] bg-white p-6 shadow-sm {{ $key === 'admin' ? 'ring-1 ring-[#c99c42]/40' : '' }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $key === 'admin' ? 'bg-[#171511] text-[#e4c577]' : 'bg-[#f2ead8] text-[#a47b31]' }}">
                            {{ strtoupper(substr($key, 0, 1)) }}
                        </span>
                        @if ($key === 'admin')
                            <span class="rounded-full bg-[#f3ead4] px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.12em] text-[#8d6929]">Única cuenta</span>
                        @endif
                    </div>
                    <h2 class="mt-5 font-serif text-2xl">{{ $role['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-[#716d65]">{{ $role['summary'] }}</p>
                    <h3 class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-[#a47b31]">Funciones</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach ($role['functions'] as $function)
                            <li class="flex gap-3 text-sm leading-6 text-[#504d46]">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#c99c42]"></span>
                                {{ $function }}
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-6 rounded-xl bg-[#f8f6f0] p-4">
                        <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-rose-700">Restricciones</h3>
                        @foreach ($role['restrictions'] as $restriction)
                            <p class="mt-2 text-xs leading-5 text-[#716d65]">{{ $restriction }}</p>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </section>
    </main>
</x-app-layout>
