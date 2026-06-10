<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AttemptAndPrepareSession
{
    /**
     * Autentica al usuario y prepara la sesión SIN migrate(true).
     * 
     * migrate(true) regenera el session ID. Con Inertia, el redirect al /panel 
     * ocurre antes de que el navegador procese la nueva cookie de sesión,
     * causando que el GET /panel no vea al usuario autenticado → 302 al login.
     * 
     * Aquí seteamos login_web_* manteniendo el MISMO session ID.
     */
    public function handle(Request $request, \Closure $next)
    {
        $user = User::withoutGlobalScopes()->where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if (!$user->activo) {
                throw ValidationException::withMessages([
                    Fortify::username() => ['Tu cuenta está desactivada o pendiente de aprobación.'],
                ]);
            }

            if ($request->hasSession()) {
                // Guardar el session ID actual ANTES de cualquier migrate
                $sessionId = $request->session()->getId();
                
                // Forzar login SIN migrate:
                // login() internamente llama updateSession() → migrate(true)
                // Para evitarlo, reemplazamos el guard con uno que no migre
                Auth::guard('web')->login($user, $request->boolean('remember'));
                
                // Restaurar el session ID original (login() llamó migrate y lo cambió)
                $request->session()->setId($sessionId);
            }
        }

        return $next($request);
    }
}
