<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function ($model) {
            if (!Auth::check()) return;
            if (self::hasColumn($model, 'created_by') && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
            if (self::hasColumn($model, 'updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (!Auth::check()) return;
            if (self::hasColumn($model, 'updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        // Al “eliminar” (SoftDelete): marca quién lo hizo
        static::deleting(function ($model) {
            if (!Auth::check()) return;

            if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive($model))) {
                // Para SoftDeletes, actualizamos deleted_by antes/de inmediato
                if (self::hasColumn($model, 'deleted_by')) {
                    $model->deleted_by = Auth::id();
                    // Guardado silencioso para evitar loops de eventos
                    $model->saveQuietly();
                }
            } else {
                // Para hard delete (sin SoftDeletes), NO quedará registro.
                // Aquí podrías enviar a un log/audit externo si quieres.
            }
        });

        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive(static::class))) {
            // Al restaurar (SoftDeletes): limpiamos deleted_by y marcamos quién restauró
            static::restored(function ($model) {
                if (!Auth::check()) return;
                $updates = [];
                if (self::hasColumn($model, 'deleted_by')) $updates['deleted_by'] = null;
                if (self::hasColumn($model, 'updated_by')) $updates['updated_by'] = Auth::id();

                if ($updates) {
                    $model->forceFill($updates)->saveQuietly();
                }
            });
        }
    }

    protected static function hasColumn($model, string $column): bool
    {
        try {
            return Schema::hasColumn($model->getTable(), $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
