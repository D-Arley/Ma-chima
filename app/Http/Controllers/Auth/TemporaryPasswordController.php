<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TemporaryPasswordController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()->must_change_password, 403);

        return view('auth.force-password');
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->must_change_password, 403);

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'password.required' => 'Escriba una nueva contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.mixed' => 'Incluya al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'Incluya al menos un número.',
            'password.symbols' => 'Incluya al menos un símbolo, por ejemplo ! @ #.',
        ]);

        $request->user()->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'password-changed');
    }
}
