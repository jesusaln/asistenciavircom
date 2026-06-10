<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Helper para convertir datos base64 a archivos en Storage.
 * 
 * Resuelve el problema de rendimiento de guardar imágenes/firmas
 * directamente en la base de datos como longText (BLOBs).
 * 
 * Uso:
 *   $path = Base64ToFile::save($base64String, 'firmas/cliente_123');
 *   // Devuelve: 'firmas/cliente_123_1714000000.png'
 */
class Base64ToFile
{
    /**
     * Guardar un string base64 como archivo en Storage.
     * 
     * @param string|null $base64Data  El string base64 (puede incluir prefijo data:image/...)
     * @param string      $directory   Directorio dentro de storage/app/public/
     * @param string|null $prefix      Prefijo para el nombre del archivo
     * @param string      $disk        Disco de Storage a usar
     * @return string|null             Ruta relativa del archivo guardado, o null si no hay datos
     */
    public static function save(?string $base64Data, string $directory, ?string $prefix = null, string $disk = 'public'): ?string
    {
        if (empty($base64Data)) {
            return null;
        }

        // Si ya es una ruta de archivo (no base64), devolverla tal cual
        if (!str_starts_with($base64Data, 'data:')) {
            // Verificar si parece una ruta válida (no tiene caracteres de base64 extremadamente largos)
            if (strlen($base64Data) < 500 && !str_contains($base64Data, '+') && !str_contains($base64Data, '=')) {
                return $base64Data;
            }
        }

        try {
            // Detectar el tipo MIME y extensión
            $extension = 'png'; // Default
            if (preg_match('#^data:image/(\w+);base64,#i', $base64Data, $matches)) {
                $extension = strtolower($matches[1]);
                if ($extension === 'jpeg') $extension = 'jpg';
                if ($extension === 'svg+xml') $extension = 'svg';
            } elseif (preg_match('#^data:application/pdf;base64,#i', $base64Data)) {
                $extension = 'pdf';
            }

            // Extraer solo la parte base64 (sin el prefijo data:...)
            $base64Clean = preg_replace('#^data:\w+/[\w+.-]+;base64,#i', '', $base64Data);
            $base64Clean = str_replace(' ', '+', $base64Clean);

            $decoded = base64_decode($base64Clean, true);
            if ($decoded === false) {
                Log::warning('Base64ToFile: Failed to decode base64 data', [
                    'directory' => $directory,
                    'data_length' => strlen($base64Data),
                ]);
                return null;
            }

            // Generar nombre único
            $filename = ($prefix ? $prefix . '_' : '') . time() . '_' . Str::random(6) . '.' . $extension;
            $path = rtrim($directory, '/') . '/' . $filename;

            Storage::disk($disk)->put($path, $decoded);

            Log::info('Base64ToFile: Archivo guardado exitosamente', [
                'path' => $path,
                'size_bytes' => strlen($decoded),
                'extension' => $extension,
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('Base64ToFile: Error al guardar archivo', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener la URL pública de un archivo guardado.
     * Maneja tanto rutas de archivo como datos base64 legacy.
     * 
     * @param string|null $value  El valor almacenado (puede ser ruta o base64 legacy)
     * @return string|null        URL pública del archivo
     */
    public static function getUrl(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Si es base64 legacy, devolverlo directamente (para compatibilidad durante la migración)
        if (str_starts_with($value, 'data:')) {
            return $value;
        }

        // Si es una URL externa, devolverla tal cual
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // Es una ruta de archivo - generar URL
        return \App\Helpers\UrlHelper::storageUrl($value);
    }

    /**
     * Verificar si un valor es base64 (datos pesados que necesitan migración).
     */
    public static function isBase64(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        return str_starts_with($value, 'data:') || strlen($value) > 500;
    }
}
