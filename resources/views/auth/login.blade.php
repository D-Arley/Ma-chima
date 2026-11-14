<x-guest-layout>
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-2 block w-full px-4 py-3.5 text-sm"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="nombre@machima.test"
            />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div x-data="{ visible: false }">
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#9b742f] transition hover:text-[#171511]">
                        ¿La olvidaste?
                    </a>
                @endif
            </div>
            <div class="relative mt-2">
                <x-text-input
                    id="password"
                    name="password"
                    x-bind:type="visible ? 'text' : 'password'"
                    class="block w-full px-4 py-3.5 pr-20 text-sm"
                    required
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-[#8b8477] transition hover:text-[#9b742f]"
                    x-on:click="visible = !visible"
                    x-text="visible ? 'Ocultar' : 'Mostrar'"
                    x-bind:aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                ></button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-3 pt-1 text-sm text-[#716d65]">
            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-[#d7d0c3] text-[#a47b31] focus:ring-[#c99c42]">
            <span>Recordar mi sesión en este equipo</span>
        </label>

        <x-primary-button class="w-full py-3.5">
            Entrar a mi cuenta
            <span class="ml-2" aria-hidden="true">→</span>
        </x-primary-button>
    </form>

    <div class="mt-6 flex items-center gap-3 text-xs text-[#8b8477]">
        <span class="h-px flex-1 bg-[#eee9df]"></span>
        <span>Acceso seguro para el equipo</span>
        <span class="h-px flex-1 bg-[#eee9df]"></span>
    </div>
</x-guest-layout>
