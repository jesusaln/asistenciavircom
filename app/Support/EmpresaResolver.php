<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmpresaResolver
{
    private static ?int $cachedId = null;

    public static function resolveId(): ?int
    {
        if (self::$cachedId !== null) {
            return self::$cachedId;
        }

        // EARLY EXIT: If we are running a migration or the database isn't ready, don't try to resolve.
        if (app()->runningInConsole() && (str_contains(implode(' ', $_SERVER['argv'] ?? []), 'migrate') || str_contains(implode(' ', $_SERVER['argv'] ?? []), 'db:seed'))) {
            return null;
        }

        $empresaId = self::resolveFromRequestContext()
            ?? self::resolveFromState()
            ?? self::resolveFromDomain()
            ?? self::resolveFromTestUser()
            ?? self::resolveFromSessionUser()
            ?? self::resolveFromBearerToken()
            ?? self::resolveFromFallback();

        if (app()->environment('testing')) {
            \Illuminate\Support\Facades\Log::info('EmpresaResolver::resolveId() resolved:', [
                'resolved_id' => $empresaId,
                'method' => 'various',
                'user_id' => auth()->id(),
                'test_user_id' => app()->runningUnitTests() ? (app()->bound('auth') ? app('auth')->guard()->getUser()?->id : null) : 'N/A',
            ]);
        }

        if ($empresaId) {
            self::$cachedId = (int) $empresaId;
            return self::$cachedId;
        }

        // FALLBACK FOR LOCAL DEVELOPMENT: If no empresa can be resolved (e.g. localhost), 
        // return the first one instead of null to avoid hiding all records.
        if (app()->environment('local')) {
            try {
                // Only try if the table exists to avoid transaction aborts in Postgres
                if (Schema::hasTable('empresas')) {
                    $firstId = DB::table('empresas')->value('id');
                    if ($firstId) {
                        self::$cachedId = (int) $firstId;
                        return self::$cachedId;
                    }
                }
            } catch (\Throwable $e) {
                // Database might not be ready
            }
        }

        return null;
    }

    public static function resolveUserId(): ?int
    {
        return self::resolveUserIdFromSession()
            ?? self::resolveUserIdFromBearerToken();
    }

    /**
     * Verifica si el usuario actual es súper-administrador de forma segura
     * (sin disparar el booteo de modelos Eloquent que cause recursión infinita).
     */
    public static function isSuperAdmin(): bool
    {
        $userId = self::resolveUserId();
        if (!$userId) return false;

        try {
            return DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', $userId)
                ->where('model_has_roles.model_type', 'App\Models\User')
                ->where('roles.name', 'super-admin')
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function clearCache(): void
    {
        self::$cachedId = null;
    }

    public static function setContext(int $empresaId): void
    {
        self::$cachedId = $empresaId;
    }

    private static function resolveFromRequestContext(): ?int
    {
        try {
            $request = request();
            $value = $request->attributes->get('empresa_id');
            if (is_numeric($value)) {
                return (int) $value;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private static function resolveFromState(): ?int
    {
        try {
            if (request()->has('state')) {
                $state = json_decode(base64_decode(request()->input('state')), true);
                if (isset($state['empresa_id'])) {
                    return (int) $state['empresa_id'];
                }
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private static function resolveFromTestUser(): ?int
    {
        if (!app()->runningUnitTests()) {
            return null;
        }

        try {
            $user = null;
            if (app('auth')->guard()->hasUser()) {
                $user = app('auth')->guard()->getUser();
            } elseif (app('auth')->guard('sanctum')->hasUser()) {
                $user = app('auth')->guard('sanctum')->user();
            }
            if ($user && $user->empresa_id) {
                return (int) $user->empresa_id;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private static function resolveFromSessionUser(): ?int
    {
        // Avoid calling auth()->user() directly here as it can trigger model booting 
        // which calls this resolver back, creating an infinite loop.
        // We use resolveUserIdFromSession() which uses the session store directly.

        $userId = self::resolveUserIdFromSession();
        if (!$userId) {
            return null;
        }

        try {
            return (int) DB::table('users')->where('id', $userId)->value('empresa_id');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function resolveUserIdFromSession(): ?int
    {
        try {
            if (!app()->bound('session.store')) {
                return null;
            }

            $session = app('session.store');
            if (method_exists($session, 'isStarted') && !$session->isStarted()) {
                return null;
            }

            $guard = app('auth')->guard();
            $sessionKey = $guard->getName();
            $userId = $session->get($sessionKey);

            return is_numeric($userId) ? (int) $userId : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function resolveFromBearerToken(): ?int
    {
        // Avoid calling auth('sanctum')->user() directly for the same reason (boot recursion)

        $userId = self::resolveUserIdFromBearerToken();
        if (!$userId) {
            return null;
        }

        try {
            return (int) DB::table('users')->where('id', $userId)->value('empresa_id');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function resolveUserIdFromBearerToken(): ?int
    {
        try {
            $token = request()->bearerToken();
            if (!$token || !str_contains($token, '|')) {
                return null;
            }

            [$tokenId, $plainText] = explode('|', $token, 2);
            $hashedToken = hash('sha256', $plainText);
            $tokenRecord = DB::table('personal_access_tokens')->find($tokenId);

            if (!$tokenRecord || !hash_equals($tokenRecord->token, $hashedToken)) {
                return null;
            }

            if ($tokenRecord->tokenable_type !== 'App\Models\User') {
                return null;
            }

            return (int) $tokenRecord->tokenable_id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function resolveFromDomain(): ?int
    {
        try {
            $host = request()->getHost();
            
            // Cache physical query for 1 hour. If table/columns don't exist, it fails gracefully.
            return (int) \Illuminate\Support\Facades\Cache::remember("empresa_domain_v2_{$host}", 3600, function () use ($host) {
                try {
                    if (!Schema::hasTable('empresa_configuracion') || !Schema::hasColumn('empresa_configuracion', 'dominio_principal')) {
                        return null;
                    }
                    return DB::table('empresa_configuracion')
                        ->where('dominio_principal', $host)
                        ->orWhere('dominio_secundario', $host)
                        ->value('id');
                } catch (\Throwable $e) {
                    return null;
                }
            }) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function resolveFromFallback(): ?int
    {
        // If we still haven't resolved an ID and the user is a guest (public route),
        // we return the first available company to avoid the "1=0" global scope filter.
        if (app()->runningInConsole()) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Cache::remember('empresa_fallback_id', 86400, function () {
                return (int) DB::table('empresas')->orderBy('id')->value('id');
            }) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
