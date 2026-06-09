<?php

namespace App\Jobs;

use App\Contracts\RustDeskClientInterface;
use App\Models\User;
use App\Support\EmpresaResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncRustDeskDeviceStatusJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $empresaId = null)
    {
    }

    public function handle(RustDeskClientInterface $rustDeskClient): void
    {
        $query = User::query()
            ->select(['id', 'empresa_id', 'rustdesk_id'])
            ->whereNotNull('rustdesk_id');

        if ($this->empresaId !== null) {
            $query->where('empresa_id', $this->empresaId);
        }

        $query->orderBy('id')->chunkById(100, function ($users) use ($rustDeskClient) {
            foreach ($users as $user) {
                $this->syncUserStatus($rustDeskClient, $user);
            }
        });
    }

    private function syncUserStatus(RustDeskClientInterface $rustDeskClient, User $user): void
    {
        $previousEmpresaId = EmpresaResolver::resolveId();

        try {
            if ($user->empresa_id) {
                EmpresaResolver::setContext((int) $user->empresa_id);
            }

            $result = $rustDeskClient->getDeviceStatus((string) $user->rustdesk_id);
            $cacheKey = $this->cacheKey((int) $user->empresa_id, (int) $user->id);

            Cache::put($cacheKey, [
                'ok' => (bool) ($result['ok'] ?? false),
                'online' => data_get($result, 'data.online'),
                'status' => $result['status'] ?? null,
                'checked_at' => now()->toIso8601String(),
            ], now()->addMinutes((int) config('rustdesk.status_cache_minutes', 5)));

            if (!($result['ok'] ?? false)) {
                Log::channel('rustdesk')->warning('RustDesk sync status failed for user', [
                    'user_id' => $user->id,
                    'empresa_id' => $user->empresa_id,
                    'rustdesk_id' => $user->rustdesk_id,
                    'status' => $result['status'] ?? null,
                    'error' => $result['error'] ?? null,
                ]);
            }
        } finally {
            if ($previousEmpresaId !== null) {
                EmpresaResolver::setContext($previousEmpresaId);
            } else {
                EmpresaResolver::clearCache();
            }
        }
    }

    private function cacheKey(int $empresaId, int $userId): string
    {
        return "rustdesk:status:empresa:{$empresaId}:user:{$userId}";
    }
}

