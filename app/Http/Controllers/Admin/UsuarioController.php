<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $usuarios = User::query()->orderBy('role')->orderBy('name')->get();
        $adminCount = User::where('role', 'admin')->count();
        $roles = $this->roleDescriptions();

        return view('admin.usuarios.index', compact('usuarios', 'adminCount', 'roles'));
    }

    public function create(): View
    {
        return view('admin.usuarios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role' => ['required', Rule::in(['vendedor', 'bodeguero'])],
        ], [
            'role.in' => 'Solo puede crear cuentas de vendedor o bodeguero.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.mixed' => 'Incluya al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'Incluya al menos un número.',
            'password.symbols' => 'Incluya al menos un símbolo, por ejemplo ! @ #.',
        ]);

        $usuario = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);
        $usuario->role = $validated['role'];
        $usuario->save();

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function updateRole(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['vendedor', 'bodeguero'])],
        ]);

        if ($usuario->isAdmin()) {
            return back()->withErrors(['role' => 'No puede cambiar ni reemplazar la cuenta administradora existente.']);
        }

        $usuario->update(['role' => $validated['role']]);

        return back()->with('success', 'El rol fue actualizado correctamente.');
    }

    public function temporaryPassword(User $usuario): RedirectResponse
    {
        if ($usuario->isAdmin()) {
            throw ValidationException::withMessages([
                'password' => 'La cuenta administradora no se puede restablecer desde la lista de usuarios.',
            ]);
        }

        $temporaryPassword = Str::password(16);

        $usuario->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();

        return redirect()->route('admin.usuarios.index')
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_password_user', $usuario->name);
    }

    public function roles(): RedirectResponse
    {
        return redirect()->to(route('admin.usuarios.index').'#roles');
    }

    /**
     * @return array<string, array{title: string, summary: string, functions: list<string>, restrictions: list<string>}>
     */
    private function roleDescriptions(): array
    {
        return [
            'admin' => [
                'title' => 'Administrador',
                'summary' => 'Propietario o responsable general de la tienda.',
                'functions' => [
                    'Gestionar usuarios vendedores y bodegueros.',
                    'Acceder a inventario, ventas, caja y movimientos.',
                    'Crear, editar y ajustar productos, precios y costos.',
                    'Consultar reportes, ventas y transferencias.',
                    'Configurar el monto inicial y abrir o cerrar caja.',
                ],
                'restrictions' => ['Solo puede existir una cuenta administradora. No se puede crear ni asignar otra cuenta admin.'],
            ],
            'vendedor' => [
                'title' => 'Vendedor',
                'summary' => 'Responsable de la atención en caja y registro de ventas.',
                'functions' => [
                    'Consultar el catálogo disponible.',
                    'Crear ventas y registrar efectivo o transferencia.',
                    'Agregar artículos al carrito y generar comprobantes.',
                    'Hacer conteos de existencias.',
                    'Recibir mercancía entregada por el bodeguero.',
                    'Abrir caja y transferir excedente al banco respetando los 200.000 COP mínimos.',
                ],
                'restrictions' => ['No puede entrar al inventario ni modificar costos, precios, existencias o imágenes.'],
            ],
            'bodeguero' => [
                'title' => 'Bodeguero',
                'summary' => 'Responsable de logística interna y control físico de mercancía.',
                'functions' => [
                    'Ingresar mercancía y crear productos.',
                    'Modificar nombres, imágenes, tallas, precios y costos.',
                    'Ajustar existencias con un motivo obligatorio.',
                    'Hacer conteos físicos.',
                    'Entregar mercancía al vendedor.',
                    'Consultar movimientos relacionados con el inventario.',
                ],
                'restrictions' => ['No puede registrar ventas ni administrar caja, usuarios o la cuenta bancaria.'],
            ],
        ];
    }
}
