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
use Illuminate\Auth\Events\Login;

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

        // Setear contexto de empresa después del login exitoso
        $this->app['events']->listen(Login::class, function (Login $event) {
            $user = $event->user;
            if ($user && $user->empresa_id) {
                \App\Support\EmpresaResolver::setContext($user->empresa_id);
            }
        });

        // Personalizar la autenticación para verificar si el usuario está activo y saltar el scope de empresa
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::withoutGlobalScopes()->where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                if (!$user->activo) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['Tu cuenta está desactivada o pendiente de aprobación.'],
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
                    // Si solo hay 1 usuario en el sistema, es el creador principal
                    if (\App\Models\User::count() <= 1) {
                        return \Inertia\Inertia::location(route('empresas.index'));
                    }

                    // Cerrar sesión inmediatamente después del registro automático de Fortify para otros usuarios
                    auth()->logout();

                    return redirect()->route('login')->with('status', 'Registro exitoso. Tu cuenta está pendiente de aprobación por un administrador.');
                }
            };
        });

        // Reemplazar AttemptToAuthenticate + PrepareAuthenticatedSession 
        // con una sola acción que NO hace migrate() para evitar perder la sesión en Redis
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                config('fortify.limiters.login') ? null : \Laravel\Fortify\Actions\EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? \Laravel\Fortify\Actions\CanonicalizeUsername::class : null,
                \Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::twoFactorAuthentication()) 
                    ? \Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable::class : null,
                \App\Actions\Fortify\AttemptAndPrepareSession::class,
            ]);
        });

        // Rate limiting desactivado temporalmente para desarrollo local (evitar 429)
        RateLimiter::for('login', function (Request $request) {
            return Limit::none();
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}

