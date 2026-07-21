<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Servicio de almacenamiento híbrido: local (primario) + R2 (respaldo).
 * 
 * - Escritura: guarda en local Y en R2 simultáneamente
 * - Lectura: usa local (más rápido), con fallback a R2
 * - URL pública: genera URL de R2 si está configurado
 * - Eliminación: borra de ambos discos
 */
class HybridStorageService
{
    protected Filesystem $local;
    protected ?Filesystem $cloud = null;
    protected bool $cloudEnabled = false;

    public function __construct()
    {
        $this->local = Storage::disk('public');
        
        if (config('filesystems.disks.r2.key')) {
            try {
                $this->cloud = Storage::disk('r2');
                $this->cloudEnabled = true;
            } catch (\Exception $e) {
                report($e);
            }
        }
    }

    public function put(string $path, $contents, $visibility = 'public'): bool
    {
        $ok = $this->local->put($path, $contents, $visibility);

        if ($this->cloudEnabled) {
            try {
                $this->cloud->put($path, $contents, $visibility);
            } catch (\Exception $e) {
                report($e);
            }
        }

        return $ok;
    }

    public function delete($paths): bool
    {
        $ok = $this->local->delete($paths);

        if ($this->cloudEnabled) {
            try {
                $this->cloud->delete($paths);
            } catch (\Exception $e) {
                report($e);
            }
        }

        return $ok;
    }

    public function exists(string $path): bool
    {
        return $this->local->exists($path);
    }

    public function get(string $path): ?string
    {
        if ($this->local->exists($path)) {
            return $this->local->get($path);
        }

        if ($this->cloudEnabled) {
            try {
                return $this->cloud->get($path);
            } catch (\Exception $e) {
                report($e);
            }
        }

        return null;
    }

    public function url(string $path): string
    {
        if ($this->cloudEnabled) {
            try {
                return $this->cloud->url($path);
            } catch (\Exception $e) {
                report($e);
            }
        }

        return $this->local->url($path);
    }

    public function path(string $path): string
    {
        return $this->local->path($path);
    }

    public function size(string $path): int
    {
        if ($this->local->exists($path)) {
            return $this->local->size($path);
        }
        return 0;
    }

    public function mimeType(string $path): ?string
    {
        if ($this->local->exists($path)) {
            return $this->local->mimeType($path);
        }
        return null;
    }

    public function files(string $directory): array
    {
        return $this->local->files($directory);
    }

    public function isCloudEnabled(): bool
    {
        return $this->cloudEnabled;
    }
}
