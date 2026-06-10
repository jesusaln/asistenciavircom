<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrepareSessionWithoutMigration
{
    /**
     * Autentica al usuario SIN llamar migrate(true).
     * 
     * $this->guard->login() internamente llama updateSession() → migrate(true)
     * lo cual regenera el session ID. Con Inertia, el redirect ocurre antes de 
     * que el navegador procese la nueva cookie → el GET /panel pierde la sesión.
     * 
     * Aquí seteamos login_web_* y el usuario manualmente, sin migrate.
     */
    public function handle(Request $request, \Closure $next)
    {
        $user = Auth::guard('web')->user();
        
        if ($user && $request->hasSession()) {
            // Setear login_web_* manualmente SIN migrate()
            $request->session()->put(
                'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d',
                $user->getAuthIdentifier()
            );
            
            $request->session()->regenerateToken();
            
            Auth::guard('sanctum')->setUser($user);
        }

        return $next($request);
    }
}
