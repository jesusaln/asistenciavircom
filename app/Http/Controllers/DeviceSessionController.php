<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppDeviceSession;

class DeviceSessionController extends Controller
{
    public function heartbeat(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string',
            'platform' => 'nullable|string',
            'version' => 'nullable|string',
            'os_version' => 'nullable|string',
            'model' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'attributes' => 'nullable|array',
        ]);

        $session = AppDeviceSession::updateOrCreate(
            ['uuid' => $request->uuid],
            [
                'user_id' => $request->user()?->id,
                'platform' => $request->platform,
                'version' => $request->version,
                'os_version' => $request->os_version,
                'model' => $request->model,
                'manufacturer' => $request->manufacturer,
                'last_seen_at' => now(),
                'attributes' => $request->attributes ?? [],
            ]
        );

        return response()->json([
            'success' => true,
            'session' => $session
        ]);
    }

    /**
     * Display a listing of active device sessions.
     */
    public function index()
    {
        $sessions = AppDeviceSession::with('user')
            ->orderBy('last_seen_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }
    /**
     * Display a listing of active device sessions (Inertia View).
     */
    public function webView()
    {
        $sessions = AppDeviceSession::with('user')
            ->orderBy('last_seen_at', 'desc')
            ->get();

        return \Inertia\Inertia::render('Dispositivos/Index', [
            'sessions' => $sessions
        ]);
    }
}
