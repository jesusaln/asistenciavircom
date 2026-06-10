<?php

namespace App\Observers;

use App\Services\Ventas\VentaQueryService;
use Illuminate\Database\Eloquent\Model;

class GeneralCacheObserver
{
    public function saved(Model $model): void
    {
        $this->clearVentasCache($model);
    }

    public function deleted(Model $model): void
    {
        $this->clearVentasCache($model);
    }

    public function restored(Model $model): void
    {
        $this->clearVentasCache($model);
    }

    private function clearVentasCache(Model $model): void
    {
        try {
            app(VentaQueryService::class)->clearCreateDataCache();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error clearing ventas cache in GeneralCacheObserver: ' . $e->getMessage());
        }
    }
}
