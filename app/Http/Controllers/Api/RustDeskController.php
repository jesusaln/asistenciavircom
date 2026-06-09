<?php

namespace App\Http\Controllers\Api;

use App\Contracts\RustDeskClientInterface;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\PolizaServicio;
use App\Models\RemoteSupportSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RustDeskController extends Controller
{
    public function __construct(private readonly RustDeskClientInterface $rustDeskClient)
    {
    }

    public function status(Request $request, string $rustdeskId): JsonResponse
    {
        if ($response = $this->authorizeAction($request->user(), 'remote_support.start', [
            'view soporte',
            'manage soporte',
            'view tickets',
        ])) {
            return $response;
        }

        $result = $this->rustDeskClient->getDeviceStatus($rustdeskId);
        $httpStatus = $this->statusCodeForResult($result);

        return response()->json([
            'success' => $result['ok'],
            'data' => $result['data'],
            'error' => $result['error'],
        ], $httpStatus);
    }

    public function devices(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAction($request->user(), 'remote_support.audit', [
            'view soporte',
            'manage soporte',
            'view tickets',
        ])) {
            return $response;
        }

        $search = $request->string('search')->trim()->value() ?: null;
        $result = $this->rustDeskClient->listDevices($search);
        $httpStatus = $this->statusCodeForResult($result);

        return response()->json([
            'success' => $result['ok'],
            'data' => $result['data'],
            'error' => $result['error'],
        ], $httpStatus);
    }

    /**
     * Endpoint para login unificado desde la app RustDesk.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string', // Se espera el email aquí
            'password' => 'required|string',
            'id' => 'nullable|string',
            'uuid' => 'nullable|string',
        ]);

        // En este sistema, el email es el identificador principal
        if (auth()->attempt(['email' => $validated['username'], 'password' => $validated['password']])) {
            
            $user = auth()->user();
            $token = $user->createToken('rustdesk-login')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'type' => 'bearer',
                'user' => [
                    'name' => $user->name,
                    'username' => $user->email,
                    'email' => $user->email,
                ],
            ]);
        }

        return response()->json([
            'error' => 'Credenciales inválidas para acceso remoto.',
        ], 401);
    }

    public function syncAlias(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAction($request->user(), 'remote_support.start', [
            'manage soporte',
            'create tickets',
        ])) {
            return $response;
        }

        $validated = $request->validate([
            'rustdesk_id' => 'required|string|max:30',
            'alias' => 'required|string|max:100',
            'user_id' => 'nullable|exists:users,id',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        $ok = $this->rustDeskClient->syncAlias(
            $validated['rustdesk_id'],
            $validated['alias']
        );

        if ($ok) {
            if (!empty($validated['user_id'])) {
                User::whereKey($validated['user_id'])
                    ->where('rustdesk_id', $validated['rustdesk_id'])
                    ->update(['rustdesk_alias' => $validated['alias']]);
            }

            if (!empty($validated['cliente_id'])) {
                Cliente::whereKey($validated['cliente_id'])
                    ->where('rustdesk_id', $validated['rustdesk_id'])
                    ->update(['rustdesk_alias' => $validated['alias']]);
            }
        }

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Alias sincronizado correctamente.' : 'No fue posible sincronizar el alias.',
        ], $ok ? 200 : 502);
    }

    public function startSession(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAction($request->user(), 'remote_support.start', [
            'view soporte',
            'manage soporte',
            'create tickets',
        ])) {
            return $response;
        }

        $validated = $request->validate([
            'rustdesk_id' => 'required|string|max:30',
            'rustdesk_alias' => 'nullable|string|max:100',
            'cliente_id' => 'nullable|exists:clientes,id',
            'source' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $session = RemoteSupportSession::create([
            'empresa_id' => $user?->empresa_id,
            'user_id' => $user?->id,
            'cliente_id' => $validated['cliente_id'] ?? null,
            'rustdesk_id' => $validated['rustdesk_id'],
            'rustdesk_alias' => $validated['rustdesk_alias'] ?? null,
            'started_at' => now(),
            'status' => 'started',
            'source' => $validated['source'] ?? 'web',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'started_at' => $session->started_at,
            ],
        ]);
    }

    public function completeSession(Request $request, RemoteSupportSession $session): JsonResponse
    {
        if ($response = $this->authorizeAction($request->user(), 'remote_support.audit', [
            'view soporte',
            'manage soporte',
        ])) {
            return $response;
        }

        $user = $request->user();
        $isOwner = (int) $session->user_id === (int) $user?->id;
        $isAdmin = $user?->hasAnyRole(['admin', 'super-admin']);
        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes finalizar sesiones de otro técnico.',
            ], 403);
        }

        if ($session->ended_at) {
            return response()->json([
                'success' => true,
                'message' => 'La sesión ya estaba finalizada.',
            ]);
        }

        $endedAt = Carbon::now();
        $startedAt = $session->started_at ?? $endedAt;
        $duration = max(0, $startedAt->diffInMinutes($endedAt));

        $session->update([
            'ended_at' => $endedAt,
            'duration_minutes' => $duration,
            'status' => 'completed',
        ]);

        $billing = $this->consumePolizaHoursForSession($session, $duration);

        return response()->json([
            'success' => true,
            'data' => [
                'duration_minutes' => $duration,
                'billing' => $billing,
            ],
        ]);
    }

    private function consumePolizaHoursForSession(RemoteSupportSession $session, int $durationMinutes): array
    {
        if ($durationMinutes <= 0 || !$session->cliente_id) {
            return ['applied' => false, 'reason' => 'No hay minutos facturables o cliente asociado.'];
        }

        // Buscar póliza activa para el cliente
        $poliza = PolizaServicio::query()
            ->where('cliente_id', $session->cliente_id)
            ->whereIn('estado', [PolizaServicio::ESTADO_ACTIVA, PolizaServicio::ESTADO_VENCIDA_EN_GRACIA])
            ->orderByDesc('fecha_inicio')
            ->first();

        if (!$poliza || !$poliza->puedeUsarServicios()) {
            return ['applied' => false, 'reason' => 'Cliente sin póliza activa para consumo automático.'];
        }

        $hours = round($durationMinutes / 60, 2);
        if ($hours <= 0) {
            return ['applied' => false, 'reason' => 'Duración insuficiente para consumo de horas.'];
        }

        $result = $poliza->consumirHoras($hours, null, $session);

        return [
            'applied' => (bool) ($result['consumido'] ?? false),
            'hours' => $hours,
            'poliza_id' => $poliza->id,
            'message' => $result['mensaje'] ?? null,
        ];
    }

    private function authorizeAction(?User $user, string $permission, array $fallbackPermissions): ?JsonResponse
    {
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return null;
        }

        $permissionExists = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->exists();

        if ($permissionExists) {
            if ($user->can($permission)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Sin permisos para operación de RustDesk.',
            ], 403);
        }

        foreach ($fallbackPermissions as $fallbackPermission) {
            if ($user->can($fallbackPermission)) {
                return null;
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Sin permisos para operación de RustDesk.',
        ], 403);
    }

    private function statusCodeForResult(array $result): int
    {
        if (($result['ok'] ?? false) === true) {
            return 200;
        }

        $externalStatus = (int) ($result['status'] ?? 0);
        if (in_array($externalStatus, [401, 403], true)) {
            return 502;
        }

        return 503;
    }
}
