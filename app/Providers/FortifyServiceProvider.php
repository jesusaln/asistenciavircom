<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\EmpresaConfiguracion;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\RegisterResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Personalizar la autenticación para verificar si el usuario está activo
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                if (!$user->activo) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['Tu cuenta está pendiente de aprobación por un administrador.'],
                    ]);
                }
                return $user;
            }
            return null;
        });

        // Redirigir después del registro a una página de espera
        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse {
                public function toResponse($request)
                {
                    // Cerrar sesión inmediatamente después del registro automático de Fortify
                    auth()->logout();

                    return redirect()->route('login')->with('status', 'Registro exitoso. Tu cuenta está pendiente de aprobación por un administrador.');
                }
            };
        });

        // Rate limiting configurable desde EmpresaConfiguracion
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            // Obtener configuración de la empresa
            try {
                $config = EmpresaConfiguracion::getConfig();
                $maxAttempts = $config->intentos_login ?? 5;
                $decayMinutes = $config->tiempo_bloqueo ?? 15;
            } catch (\Throwable $e) {
                // Fallback en caso de error de BD
                $maxAttempts = 5;
                $decayMinutes = 15;
            }

            return Limit::perMinutes($decayMinutes, $maxAttempts)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}

