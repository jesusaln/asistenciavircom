<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
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

        $user = User::where('email', $request->email)->first();

        // LOG DE DIAGNÓSTICO
        Log::info('Login attempt', [
            'email' => $request->email,
            'user_found' => $user ? 'yes' : 'no',
            'user_id' => $user ? $user->id : null,
            'password_match' => ($user && Hash::check($request->password, $user->password)) ? 'yes' : 'no'
        ]);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Verificar que el usuario esté activo
        if (!$user->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo. Contacte al administrador.'
            ], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                    'activo' => $user->activo,
                    'es_empleado' => $user->es_empleado,
                    'roles' => $roles,
                    'permissions' => $permissions,
                    'puesto' => $user->puesto,
                    'departamento' => $user->departamento,
                    'almacen_venta_id' => $user->almacen_venta_id,
                    'almacen_compra_id' => $user->almacen_compra_id,
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Logout de usuario (invalidar token actual)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Eliminar el token actual
        $request->user()->currentAccessToken()->delete();

        Log::info('Usuario cerró sesión vía API', [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
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

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'activo' => $user->activo,
                'es_empleado' => $user->es_empleado,
                'roles' => $roles,
                'permissions' => $permissions,
                'puesto' => $user->puesto,
                'departamento' => $user->departamento,
                'almacen_venta_id' => $user->almacen_venta_id,
                'almacen_compra_id' => $user->almacen_compra_id,
            ]
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

        return response()->json([
            'success' => true,
            'message' => 'Token renovado',
            'data' => [
                'token' => $token
            ]
        ]);
    }
}
