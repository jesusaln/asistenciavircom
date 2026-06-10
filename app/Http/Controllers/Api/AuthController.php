<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Traits\ApiResponse;
use App\Services\Mantenimiento\MantenimientoStatsService;
use App\Models\Mantenimiento;

class AuthController extends Controller
{
    use ApiResponse;
    /**
     * Login de usuario
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::withoutGlobalScope('empresa')->whereRaw('LOWER(email) = ?', [strtolower($request->email)])->first();

        // LOG DE DIAGNÓSTICO
        Log::info('Login attempt', [
            'email' => $request->email,
            'user_found' => $user ? 'yes' : 'no',
            'user_id' => $user ? $user->id : null,
            'password_match' => ($user && Hash::check($request->password, $user->password)) ? 'yes' : 'no'
        ]);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->unauthorized('Credenciales incorrectas');
        }

        // Verificar que el usuario esté activo
        if (!$user->activo) {
            return $this->forbidden('Usuario inactivo. Contacte al administrador.');
        }

        // Obtener nombre del dispositivo o usar default
        $deviceName = $request->input('device_name', 'mobile-app');

        // Crear token
        $token = $user->createToken($deviceName)->plainTextToken;

        // Obtener roles y permisos
        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');

        Log::info('Usuario autenticado vía API', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'activo' => $user->activo,
                'es_empleado' => $user->es_empleado,
                'es_tecnico' => (bool) $user->es_tecnico,
                'es_vendedor' => (bool) $user->es_vendedor,
                'roles' => $roles,
                'permissions' => $permissions,
                'puesto' => $user->puesto,
                'departamento' => $user->departamento,
                'almacen_venta_id' => $user->almacen_venta_id,
                'almacen_compra_id' => $user->almacen_compra_id,
                'carro' => $user->carro ? $user->carro->load(['mantenimientos' => function($q) {
                    // Cargar el último completado y el próximo pendiente
                    $q->orderBy('fecha', 'desc')->limit(5);
                }]) : null,
                'carro_salud' => $this->getSaludVehiculo($user),
            ],
            'token' => $token
        ], 'Login exitoso');
    }

    /**
     * Logout de usuario (invalidar token actual)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            if ($request->boolean('all_devices')) {
                // Revocar todos los tokens (Cerrar sesión en todos los dispositivos)
                $user->tokens()->delete();
                Log::info('Usuario cerró sesión en TODOS los dispositivos vía API', ['user_id' => $user->id]);
            } else {
                // Eliminar solo el token actual de Sanctum (si es un token real, no TransientToken)
                $token = $user->currentAccessToken();
                if ($token && method_exists($token, 'delete')) {
                    $token->delete();
                }
                Log::info('Usuario cerró sesión vía API', ['user_id' => $user->id]);
            }

            // Limpiar el token de FCM
            $user->fcm_token = null;
            $user->save();
        } catch (\Exception $e) {
            Log::warning('Error en base de datos durante logout, procediendo con respuesta exitosa', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        return $this->success(null, 'Logout exitoso');
    }

    /**
     * Obtener usuario autenticado actual
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Obtener roles y permisos
        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_url' => $user->profile_photo_url,
            'activo' => $user->activo,
            'es_empleado' => $user->es_empleado,
            'es_tecnico' => (bool) $user->es_tecnico,
            'es_vendedor' => (bool) $user->es_vendedor,
            'roles' => $roles,
            'permissions' => $permissions,
            'puesto' => $user->puesto,
            'departamento' => $user->departamento,
            'almacen_venta_id' => $user->almacen_venta_id,
            'almacen_compra_id' => $user->almacen_compra_id,
            'carro' => $user->carro ? $user->carro->load(['mantenimientos' => function($q) {
                // Cargar el último completado y el próximo pendiente
                $q->orderBy('fecha', 'desc')->limit(5);
            }]) : null,
            'carro_salud' => $this->getSaludVehiculo($user),
        ]);
    }

    /**
     * Renovar token (opcional, para extender sesión)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        // Eliminar token actual
        $request->user()->currentAccessToken()->delete();

        // Crear nuevo token
        $token = $request->user()->createToken('mobile-app')->plainTextToken;

        return $this->success([
            'token' => $token
        ], 'Token renovado');
    }

    /**
     * Actualizar el token de FCM para notificaciones push
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        Log::info('FCM Token actualizado para usuario: ' . $user->id);

        return $this->success(null, 'Token de notificaciones actualizado');
    }

    /**
     * Obtener la salud del vehículo asignado al usuario
     */
    private function getSaludVehiculo(User $user): ?array
    {
        if (!$user->carro_id) return null;

        $statsService = app(MantenimientoStatsService::class);
        $proximo = Mantenimiento::where('carro_id', $user->carro_id)
            ->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO)
            ->orderBy('proximo_mantenimiento', 'asc')
            ->first();

        if (!$proximo) {
            // Verificar si alguna vez ha tenido mantenimiento
            $tieneHistorial = Mantenimiento::where('carro_id', $user->carro_id)->exists();

            if (!$tieneHistorial) {
                return [
                    'estado' => 'vencido',
                    'descripcion' => 'Requiere registro de servicio',
                    'clase' => 'text-red-700 bg-red-100',
                    'nivel' => 'danger'
                ];
            }

            return [
                'estado' => 'al_dia',
                'descripcion' => 'Vehículo al día',
                'clase' => 'text-green-700 bg-green-100',
                'nivel' => 'good'
            ];
        }

        $metadata = $statsService->getEstadoMetadata($proximo);
        
        // Mapear el estado a un nivel para la App
        $nivel = 'good';
        if ($metadata['estado'] === 'vencido') $nivel = 'danger';
        elseif ($metadata['estado'] === 'por_vencer') $nivel = 'warning';

        return array_merge($metadata, ['nivel' => $nivel]);
    }
}
