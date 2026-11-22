<x-guest-layout>
    <div class="rounded-2xl border border-[#e7d8b5] bg-[#fbf6e9] p-5">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#8d6929]">Recuperación de acceso</p>
        <h1 class="mt-2 font-serif text-2xl text-[#171511]">Solicita una clave temporal</h1>
        <p class="mt-3 text-sm leading-6 text-[#66532e]">
            Las cuentas de Ma'chima usan correos de demostración y no reciben enlaces. Comunícate con el administrador para que genere una clave temporal desde la sección Usuarios.
        </p>
        <p class="mt-3 text-sm leading-6 text-[#66532e]">
            Al entrar con esa clave, el sistema te pedirá crear una contraseña personal antes de abrir cualquier módulo.
        </p>
    </div>

    <a href="{{ route('login') }}" class="mt-6 inline-flex w-full justify-center rounded-full bg-[#171511] px-5 py-3.5 text-sm font-semibold text-white">
        Volver al inicio de sesión
    </a>
</x-guest-layout>
