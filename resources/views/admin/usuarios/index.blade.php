<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Administración</p>
                <h1 class="mt-1 font-serif text-3xl">Gestión de usuarios</h1>
            </div>
            <a href="{{ route('admin.usuarios.create') }}" class="rounded-full bg-[#171511] px-5 py-3 text-sm font-semibold text-white">Crear usuario</a>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        @if (session('temporary_password'))
            <section class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900">
                <p class="text-xs font-bold uppercase tracking-[0.14em]">Clave temporal · {{ session('temporary_password_user') }}</p>
                <p class="mt-2 break-all rounded-lg bg-white px-4 py-3 font-mono text-lg font-bold">{{ session('temporary_password') }}</p>
                <p class="mt-2 text-sm">Compártela de forma segura. Solo se muestra en esta pantalla; al iniciar sesión, el usuario deberá cambiarla.</p>
            </section>
        @endif

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-[#8c8679]">Usuarios totales</p>
                <p class="mt-2 font-serif text-3xl">{{ $usuarios->count() }}</p>
            </div>
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-[#8c8679]">Administradores</p>
                <p class="mt-2 font-serif text-3xl">{{ $adminCount }}</p>
            </div>
            <div class="rounded-2xl border border-[#e7e0d2] bg-white p-5">
                <p class="text-xs uppercase tracking-[0.16em] text-[#8c8679]">Equipos operativos</p>
                <p class="mt-2 font-serif text-3xl">{{ $usuarios->whereIn('role', ['vendedor', 'bodeguero'])->count() }}</p>
            </div>
        </section>

        <section class="mt-8 overflow-hidden rounded-2xl border border-[#e7e0d2] bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#f8f6f0] text-xs uppercase tracking-[0.12em] text-[#777167]">
                        <tr>
                            <th class="px-6 py-4">Usuario</th>
                            <th class="px-6 py-4">Correo</th>
                            <th class="px-6 py-4">Rol</th>
                            <th class="px-6 py-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eee9df]">
                        @foreach ($usuarios as $usuario)
                            <tr>
                                <td class="px-6 py-5 font-semibold">{{ $usuario->name }}</td>
                                <td class="px-6 py-5 text-[#716d65]">{{ $usuario->email }}</td>
                                <td class="px-6 py-5">
                                    <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $usuario->role === 'admin' ? 'bg-[#171511] text-[#e4c577]' : 'bg-[#f2ead8] text-[#8d6929]' }}">
                                        {{ ucfirst($usuario->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    @if ($usuario->isAdmin())
                                        <span class="text-xs text-[#9a958a]">Administrador protegido</span>
                                    @else
                                        <div class="flex flex-wrap items-center gap-2">
                                            <form method="POST" action="{{ route('admin.usuarios.update-role', $usuario) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="rounded-lg border-[#ded8cc] py-2 text-sm">
                                                    <option value="vendedor" @selected($usuario->role === 'vendedor')>Vendedor</option>
                                                    <option value="bodeguero" @selected($usuario->role === 'bodeguero')>Bodeguero</option>
                                                </select>
                                                <button class="rounded-lg border border-[#d8cfbe] px-3 py-2 text-xs font-semibold hover:bg-[#f8f6f0]">Guardar rol</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.usuarios.temporary-password', $usuario) }}" onsubmit="return confirm('Se generará una clave temporal nueva y deberá compartirse con este usuario. ¿Continuar?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded-lg border border-[#d8cfbe] px-3 py-2 text-xs font-semibold text-[#8d6929] hover:bg-[#f8f6f0]">Clave temporal</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section id="roles" class="mt-10 scroll-mt-24">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a47b31]">Referencia de permisos</p>
                <h2 class="mt-1 font-serif text-3xl">Roles y responsabilidades</h2>
                <p class="mt-2 text-sm text-[#716d65]">Consulta aquí las funciones y restricciones de cada perfil.</p>
            </div>
            <div class="mt-5 grid gap-5 lg:grid-cols-3">
                @foreach ($roles as $key => $role)
                    <article class="rounded-2xl border border-[#e7e0d2] bg-white p-6 {{ $key === 'admin' ? 'ring-1 ring-[#c99c42]/40' : '' }}">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $key === 'admin' ? 'bg-[#171511] text-[#e4c577]' : 'bg-[#f2ead8] text-[#a47b31]' }}">{{ strtoupper(substr($key, 0, 1)) }}</span>
                            @if ($key === 'admin')
                                <span class="rounded-full bg-[#f3ead4] px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.12em] text-[#8d6929]">Cuenta única</span>
                            @endif
                        </div>
                        <h3 class="mt-4 font-serif text-2xl">{{ $role['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-[#716d65]">{{ $role['summary'] }}</p>
                        <h4 class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-[#a47b31]">Funciones</h4>
                        <ul class="mt-2 space-y-2 text-sm leading-5 text-[#504d46]">
                            @foreach ($role['functions'] as $function)
                                <li class="flex gap-2"><span class="text-[#c99c42]">•</span><span>{{ $function }}</span></li>
                            @endforeach
                        </ul>
                        <h4 class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-rose-700">Restricciones</h4>
                        @foreach ($role['restrictions'] as $restriction)
                            <p class="mt-2 text-sm leading-5 text-[#716d65]">{{ $restriction }}</p>
                        @endforeach
                    </article>
                @endforeach
            </div>
        </section>
    </main>
</x-app-layout>
