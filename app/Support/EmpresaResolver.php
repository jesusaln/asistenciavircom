<?php

namespace App\Support;

use App\Models\Empresa;
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

        $empresaId = self::resolveFromRequestContext()
            ?? self::resolveFromState()
            ?? self::resolveFromTestUser()
            ?? self::resolveFromSessionUser()
            ?? self::resolveFromBearerToken();

        if (app()->environment('testing')) {
            \Illuminate\Support\Facades\Log::info('EmpresaResolver::resolveId() resolved:', [
                'resolved_id' => $empresaId,
                'method' => 'various',
                'user_id' => auth()->id(),
                'test_user_id' => app()->runningUnitTests() ? app('auth')->guard()->getUser()?->id : 'N/A',
            ]);
        }

        if ($empresaId) {
            self::$cachedId = (int) $empresaId;
            return self::$cachedId;
        }

        if (!Schema::hasTable('empresas')) {
            return null;
        }

        // Fallback: Si no se puede resolver el contexto (ej. sitio público), 
        // usamos la primera empresa registrada para no romper la visibilidad.
        try {
            $fallbackId = (int) DB::table('empresas')->orderBy('id')->value('id');
            if ($fallbackId) {
                self::$cachedId = $fallbackId;
                return $fallbackId;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public static function resolveUserId(): ?int
    {
        return self::resolveUserIdFromSession()
            ?? self::resolveUserIdFromBearerToken();
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
            $user = app('auth')->guard()->getUser();
            if ($user && $user->empresa_id) {
                return (int) $user->empresa_id;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private static function resolveFromSessionUser(): ?int
    {
        $userId = self::resolveUserIdFromSession();
        if (!$userId) {
            return null;
        }

        try {
            $empresaId = DB::table('users')->where('id', $userId)->value('empresa_id');
            return $empresaId ? (int) $empresaId : null;
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
        $userId = self::resolveUserIdFromBearerToken();
        if (!$userId) {
            return null;
        }

        try {
            $empresaId = DB::table('users')->where('id', $userId)->value('empresa_id');
            return $empresaId ? (int) $empresaId : null;
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
}
