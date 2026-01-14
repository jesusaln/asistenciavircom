<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserNotificationController extends Controller
{
    public function __construct()
    {
        // Las rutas ya están protegidas por middleware en routes/web.php
        // No necesitamos middleware adicional aquí
    }

    public function index(Request $request)
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            Log::info('UserNotificationController@index called', [
                'user_id' => $userId,
                'request_params' => $request->all()
            ]);

            $notifications = UserNotification::forUser($userId)
                ->when($request->boolean('only_unread'), fn ($q) => $q->unread())
                ->when($request->filled('type'), fn ($q) => $q->byType($request->type))
                ->latest('created_at')
                ->paginate($request->integer('per_page', 20));

            Log::info('Notifications query executed', [
                'total_notifications' => $notifications->total(),
                'current_page' => $notifications->currentPage()
            ]);

            return response()->json([
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page'    => $notifications->lastPage(),
                    'per_page'     => $notifications->perPage(),
                    'total'        => $notifications->total(),
                ],
                'unread_count' => UserNotification::forUser($userId)->unread()->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en UserNotificationController@index: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function unreadCount()
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $count = UserNotification::forUser($userId)->unread()->count();

            return response()->json([
                'unread_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en unreadCount: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer','distinct'],
        ]);

        $userId = Auth::id();

        // Solo las notificaciones del usuario
        $updated = UserNotification::forUser($userId)
            ->whereIn('id', $validated['ids'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message'      => 'Notificaciones marcadas como leídas',
            'updated_count'=> $updated,
            'unread_count' => UserNotification::forUser($userId)->unread()->count(),
        ]);
    }

    public function markAllAsRead()
    {
        $userId = Auth::id();

        $updated = UserNotification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message'      => 'Todas las notificaciones marcadas como leídas',
            'updated_count'=> $updated,
            'unread_count' => 0,
        ]);
    }

    public function destroy($id)
    {
        $userId = Auth::id();

        $notification = UserNotification::forUser($userId)->findOrFail($id);
        $notification->delete();

        return response()->json([
            'message'      => 'Notificación eliminada',
            'unread_count' => UserNotification::forUser($userId)->unread()->count(),
        ]);
    }

    public function destroyMultiple(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer','distinct'],
        ]);

        $userId = Auth::id();

        $deleted = UserNotification::forUser($userId)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return response()->json([
            'message'       => 'Notificaciones eliminadas',
            'deleted_count' => $deleted,
            'unread_count'  => UserNotification::forUser($userId)->unread()->count(),
        ]);
    }

    // Método de prueba para crear notificaciones
    public function createTest(Request $request)
    {
        $userId = Auth::id();

        $notification = UserNotification::createForUser(
            $userId,
            'test',
            'Notificación de Prueba',
            'Esta es una notificación de prueba creada desde el frontend',
            ['test' => true, 'timestamp' => now()->toISOString()],
            '/panel',
            'fas fa-bell'
        );

        return response()->json([
            'success' => true,
            'notification' => $notification,
            'message' => 'Notificación de prueba creada'
        ]);
    }
}
