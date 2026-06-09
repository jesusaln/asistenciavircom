<?php

namespace App\Observers;

use App\Models\Producto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class KitObserver
{
    /**
     * Handle the Producto "created" event (solo para kits).
     */
    public function created(Producto $kit): void
    {
        if (!$kit->esKit()) {
            return;
        }

        Log::channel('audit')->info('Kit creado', [
            'kit_id' => $kit->id,
            'nombre' => $kit->nombre,
            'codigo' => $kit->codigo,
            'precio_venta' => $kit->precio_venta,
            'precio_compra' => $kit->precio_compra,
            'categoria_id' => $kit->categoria_id,
            'componentes_count' => $kit->kitItems()->count(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Producto "updated" event (solo para kits).
     */
    public function updated(Producto $kit): void
    {
        if (!$kit->esKit()) {
            return;
        }

        $changes = $kit->getChanges();
        
        // Solo registrar si hubo cambios significativos
        if (empty($changes) || (count($changes) === 1 && isset($changes['updated_at']))) {
            return;
        }

        Log::channel('audit')->info('Kit actualizado', [
            'kit_id' => $kit->id,
            'nombre' => $kit->nombre,
            'codigo' => $kit->codigo,
            'changes' => $changes,
            'original' => $kit->getOriginal(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Producto "deleted" event (solo para kits).
     */
    public function deleted(Producto $kit): void
    {
        if (!$kit->esKit()) {
            return;
        }

        Log::channel('audit')->warning('Kit eliminado', [
            'kit_id' => $kit->id,
            'nombre' => $kit->nombre,
            'codigo' => $kit->codigo,
            'precio_venta' => $kit->precio_venta,
            'componentes_count' => $kit->kitItems()->withTrashed()->count(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Producto "restored" event (solo para kits).
     */
    public function restored(Producto $kit): void
    {
        if (!$kit->esKit()) {
            return;
        }

        Log::channel('audit')->info('Kit restaurado', [
            'kit_id' => $kit->id,
            'nombre' => $kit->nombre,
            'codigo' => $kit->codigo,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Producto "force deleted" event (solo para kits).
     */
    public function forceDeleted(Producto $kit): void
    {
        if (!$kit->esKit()) {
            return;
        }

        Log::channel('audit')->critical('Kit eliminado permanentemente', [
            'kit_id' => $kit->id,
            'nombre' => $kit->nombre,
            'codigo' => $kit->codigo,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
