<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $notifications = Notification::forUser($userId)
            ->latest()
            ->paginate(20);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => Notification::forUser($userId)->unread()->count()
        ]);
    }

    public function unreadCount()
    {
        $userId = Auth::id();

        return response()->json([
            'unread_count' => Notification::forUser($userId)->unread()->count()
        ]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $userId = Auth::id();

        $updated = Notification::forUser($userId)
            ->whereIn('id', $request->ids)
            ->update([
                'read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas',
            'updated_count' => $updated,
            'unread_count' => Notification::forUser($userId)->unread()->count()
        ]);
    }


    public function destroy(Request $request, $id)
    {
        $userId = Auth::id();

        $notification = Notification::forUser($userId)->findOrFail($id);
        $notification->delete();

        return response()->json([
            'message' => 'Notificación eliminada',
            'unread_count' => Notification::forUser($userId)->unread()->count()
        ]);
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $userId = Auth::id();

        $deleted = Notification::forUser($userId)
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json([
            'message' => 'Notificaciones eliminadas',
            'deleted_count' => $deleted,
            'unread_count' => Notification::forUser($userId)->unread()->count()
        ]);
    }

    // En NotificationController.php
    public function markAllAsRead(Request $request)
    {
        try {
            // Marcar todas las notificaciones del usuario como leídas
            $userId = Auth::id();

            $updated = Notification::forUser($userId)
                ->unread()
                ->update([
                    'read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Todas las notificaciones han sido marcadas como leídas',
                'updated_count' => $updated,
                'unread_count' => Notification::forUser($userId)->unread()->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar las notificaciones como leídas'
            ], 500);
        }
    }
}
