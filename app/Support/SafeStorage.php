<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class SafeStorage
{
    public static function deletePublic(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        // Evitar path traversal o rutas absolutas
        if (str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return false;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            return false;
        }

        return (bool) $disk->delete($path);
    }
}
