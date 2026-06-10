<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;

class MonitoreoController extends Controller
{
    public function index(MonitoringService $monitor)
    {
        $status = $monitor->check();

        return inertia('Admin/Monitoreo', [
            'status' => $status,
            'canRetry' => auth()->user()?->can('admin') ?? false,
        ]);
    }

    public function refresh(MonitoringService $monitor)
    {
        return response()->json($monitor->check());
    }

    public function retryFailed(MonitoringService $monitor)
    {
        try {
            \Artisan::call('queue:flush');
            return response()->json(['ok' => true, 'message' => 'Jobs fallidos eliminados']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
