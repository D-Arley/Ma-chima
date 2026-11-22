<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Nuevo miembro</p>
            <h1 class="mt-1 font-serif text-3xl">Crear cuenta operativa</h1>
        </div>
    </x-slot>

    <main class="mx-auto max-w-3xl px-5 py-10 sm:px-8">
        <div class="rounded-2xl border border-[#e7e0d2] bg-white p-6 sm:p-8">
            <form method="POST" action="{{ route('admin.usuarios.store') }}" class="grid gap-5" x-data="{ password: '' }">
                @csrf
                <div>
                    <x-input-label for="name" value="Nombre completo" />
                    <x-text-input id="name" name="name" class="mt-2 block w-full px-4 py-3" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="email" value="Correo electrónico" />
                    <x-text-input id="email" name="email" type="email" class="mt-2 block w-full px-4 py-3" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" />
                </div>
                <div>
                    <x-input-label for="role" value="Rol asignado" />
                    <select id="role" name="role" class="mt-2 w-full rounded-xl border-[#ded8cc] px-4 py-3" required>
                        <option value="">Seleccione un rol</option>
                        <option value="vendedor" @selected(old('role') === 'vendedor')>Vendedor</option>
                        <option value="bodeguero" @selected(old('role') === 'bodeguero')>Bodeguero</option>
                    </select>
                    <p class="mt-2 text-xs text-[#89847a]">Por seguridad no puede crear ni asignar el rol administrador.</p>
                    <x-input-error :messages="$errors->get('role')" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" value="Contraseña" />
                        <x-text-input id="password" name="password" type="password" x-model="password" class="mt-2 block w-full px-4 py-3" required />
                        <x-input-error :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full px-4 py-3" required />
                    </div>
                </div>
                <div class="rounded-xl bg-[#f8f6f0] p-4">
                    <p class="text-sm font-bold text-[#302d27]">La contraseña debe incluir:</p>
                    <ul class="mt-2 grid gap-2 text-xs sm:grid-cols-2">
                        <li :class="password.length >= 12 ? 'text-emerald-700' : 'text-[#716d65]'"><span x-text="password.length >= 12 ? '✓' : '•'"></span> 12 caracteres o más</li>
                        <li :class="/[a-z]/.test(password) && /[A-Z]/.test(password) ? 'text-emerald-700' : 'text-[#716d65]'"><span x-text="/[a-z]/.test(password) && /[A-Z]/.test(password) ? '✓' : '•'"></span> Mayúscula y minúscula</li>
                        <li :class="/[0-9]/.test(password) ? 'text-emerald-700' : 'text-[#716d65]'"><span x-text="/[0-9]/.test(password) ? '✓' : '•'"></span> Al menos un número</li>
                        <li :class="/[^A-Za-z0-9]/.test(password) ? 'text-emerald-700' : 'text-[#716d65]'"><span x-text="/[^A-Za-z0-9]/.test(password) ? '✓' : '•'"></span> Al menos un símbolo (! @ #)</li>
                    </ul>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('admin.usuarios.index') }}" class="rounded-full border border-[#d8cfbe] px-5 py-3 text-sm font-semibold">Cancelar</a>
                    <button class="rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Crear usuario</button>
                </div>
            </form>
        </div>
    </main>
</x-app-layout>
