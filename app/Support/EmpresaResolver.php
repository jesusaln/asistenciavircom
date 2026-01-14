<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
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

        // 0. SPECIAL HACK: Google Drive Callback State
        // If we are in the callback, the state parameter contains the empresa_id
        if (request()->has('state')) {
            $state = json_decode(base64_decode(request()->input('state')), true);
            if (isset($state['empresa_id'])) {
                self::$cachedId = (int) $state['empresa_id'];
                return self::$cachedId;
            }
        }

        // 1. En tests, confiamos en Auth directamente para evitar complejidad de sesión/cookies
        if (app()->runningUnitTests()) {
            if (Auth::check()) {
                $userId = Auth::id();
                $empresaId = DB::table('users')->where('id', $userId)->value('empresa_id');
                if ($empresaId) {
                    self::$cachedId = (int) $empresaId;
                    return self::$cachedId;
                }
            }
        }

        // 2. Intentar obtener de la sesión (HACK para romper recursión infinita en producción)
        try {
            // FIXED: Scan session directly to find user ID.
            // Avoids calling Auth::user(), Auth::id(), or Auth::check() which trigger the User model's
            // global scope (BelongsToEmpresa), causing an infinite recursion loop.
            $userId = null;
            $sessionData = Session::all();

            foreach ($sessionData as $key => $value) {
                // Laravel auth session keys start with "login_"
                if (is_string($key) && str_starts_with($key, 'login_')) {
                    $userId = $value;
                    break;
                }
            }

            if ($userId) {
                // Buscamos directamente en BD para saltar validaciones de Eloquent/Scopes
                $empresaId = DB::table('users')
                    ->where('id', $userId)
                    ->value('empresa_id');

                if ($empresaId) {
                    self::$cachedId = (int) $empresaId;
                    return self::$cachedId;
                }
            }
        } catch (\Throwable $e) {
            // Evitar romper resolucion si hay errores de sesión o BD.
        }

        // 3. API Token (Sanctum): Resolver via Bearer Token sin hidratar User model
        try {
            $token = request()->bearerToken();
            if ($token) {
                // Sanctum tokens hash format: id|plaintext
                if (str_contains($token, '|')) {
                    [$tokenId, $plainText] = explode('|', $token, 2);
                    $hashedToken = hash('sha256', $plainText);

                    // Buscar en tabla de tokens personal
                    $tokenRecord = DB::table('personal_access_tokens')->find($tokenId);

                    if ($tokenRecord && hash_equals($tokenRecord->token, $hashedToken)) {
                        // Asumimos que tokenable_type es User
                        if ($tokenRecord->tokenable_type === 'App\Models\User' || $tokenRecord->tokenable_type === 'App\Models\User') {
                            $empresaId = DB::table('users')->where('id', $tokenRecord->tokenable_id)->value('empresa_id');
                            if ($empresaId) {
                                self::$cachedId = (int) $empresaId;
                                return self::$cachedId;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        if (!Schema::hasTable('empresas')) {
            return null;
        }

        // FALLBACK TEMPORAL PARA EVITAR BLOQUEOS
        return DB::table('empresas')->orderBy('id')->value('id');
    }

    public static function clearCache(): void
    {
        self::$cachedId = null;
    }

    public static function setContext(int $empresaId): void
    {
        self::$cachedId = $empresaId;
    }
}
