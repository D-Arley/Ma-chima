<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Seguridad de la cuenta</p>
            <h1 class="mt-1 font-serif text-3xl">Crea tu contraseña personal</h1>
        </div>
    </x-slot>

    <main class="mx-auto max-w-2xl px-5 py-10 sm:px-8" x-data="{ password: '' }">
        <section class="rounded-3xl border border-[#e7e0d2] bg-white p-6 shadow-sm sm:p-9">
            <div class="rounded-2xl border border-[#e7d8b5] bg-[#fbf6e9] p-4 text-sm leading-6 text-[#66532e]">
                El administrador te entregó una clave temporal. Cámbiala ahora por una contraseña personal antes de continuar.
            </div>

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.force-change.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="password" class="text-sm font-semibold">Nueva contraseña</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        x-model="password"
                        required
                        class="mt-2 block w-full rounded-xl border-[#ded8cc] px-4 py-3.5"
                    >
                </div>

                <div class="rounded-2xl bg-[#f8f6f0] p-5">
                    <h2 class="text-sm font-bold text-[#302d27]">Requisitos de la contraseña</h2>
                    <ul class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        <li :class="password.length >= 12 ? 'text-emerald-700' : 'text-[#716d65]'">
                            <span x-text="password.length >= 12 ? '✓' : '•'"></span> Al menos 12 caracteres
                        </li>
                        <li :class="/[a-z]/.test(password) && /[A-Z]/.test(password) ? 'text-emerald-700' : 'text-[#716d65]'">
                            <span x-text="/[a-z]/.test(password) && /[A-Z]/.test(password) ? '✓' : '•'"></span> Una mayúscula y una minúscula
                        </li>
                        <li :class="/[0-9]/.test(password) ? 'text-emerald-700' : 'text-[#716d65]'">
                            <span x-text="/[0-9]/.test(password) ? '✓' : '•'"></span> Al menos un número
                        </li>
                        <li :class="/[^A-Za-z0-9]/.test(password) ? 'text-emerald-700' : 'text-[#716d65]'">
                            <span x-text="/[^A-Za-z0-9]/.test(password) ? '✓' : '•'"></span> Al menos un símbolo, por ejemplo ! @ #
                        </li>
                    </ul>
                </div>

                <div>
                    <label for="password_confirmation" class="text-sm font-semibold">Confirma la nueva contraseña</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 block w-full rounded-xl border-[#ded8cc] px-4 py-3.5"
                    >
                </div>

                <button class="w-full rounded-full bg-[#171511] px-5 py-3.5 text-sm font-bold text-white">
                    Guardar y continuar
                </button>
            </form>
        </section>
    </main>
</x-app-layout>
