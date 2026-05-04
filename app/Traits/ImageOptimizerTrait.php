<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

trait ImageOptimizerTrait
{
    /**
     * Optimiza y guarda una imagen en formato WebP.
     *
     * @param UploadedFile $file El archivo subido.
     * @param string $path El directorio de destino dentro del disco.
     * @param string $disk El disco de almacenamiento (por defecto 'public').
     * @param int $quality Calidad de la imagen (1-100).
     * @param int|null $maxEdgePx Si se indica (p. ej. 1600), la imagen se escala manteniendo proporción para que el lado mayor no supere este valor.
     * @return string|null La ruta relativa del archivo guardado o null en caso de error.
     */
    public function saveImageAsWebP(UploadedFile $file, string $path = 'uploads', string $disk = 'public', int $quality = 80, ?int $maxEdgePx = null): ?string
    {
        // Aumentar temporalmente el límite de memoria para imágenes grandes de alta resolución
        @ini_set('memory_limit', '512M');

        try {
            // Verificar si la extensión GD está instalada
            if (!extension_loaded('gd')) {
                Log::warning('Extensión GD no instalada. Guardando imagen original.');
                return $file->store($path, $disk);
            }

            // Verificar si la función imagewebp está disponible
            if (!function_exists('imagewebp')) {
                Log::warning('Función imagewebp no disponible. Guardando imagen original.');
                return $file->store($path, $disk);
            }

            $imageString = file_get_contents($file->getRealPath());
            $image = @imagecreatefromstring($imageString);

            if (!$image) {
                Log::error('No se pudo crear el recurso de imagen desde el archivo subido.');
                return $file->store($path, $disk);
            }

            if ($maxEdgePx !== null && $maxEdgePx > 0 && function_exists('imagesx') && function_exists('imagescale')) {
                $w = imagesx($image);
                $h = imagesy($image);
                if ($w > $maxEdgePx || $h > $maxEdgePx) {
                    if ($w >= $h) {
                        $newW = $maxEdgePx;
                        $newH = (int) max(1, round($h * ($maxEdgePx / $w)));
                    } else {
                        $newH = $maxEdgePx;
                        $newW = (int) max(1, round($w * ($maxEdgePx / $h)));
                    }
                    $scaled = @imagescale($image, $newW, $newH, IMG_BICUBIC);
                    if ($scaled !== false) {
                        imagedestroy($image);
                        $image = $scaled;
                    }
                }
            }

            // Mantener transparencia si es PNG
            imagealphablending($image, true);
            imagesavealpha($image, true);

            // Generar nombre de archivo único
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . uniqid() . '.webp';
            $relativeFullPath = "{$path}/{$filename}";

            // Asegurar que el directorio existe
            Storage::disk($disk)->makeDirectory($path);
            $absPath = Storage::disk($disk)->path($relativeFullPath);

            // Convertir y guardar como WebP
            if (imagewebp($image, $absPath, $quality)) {
                imagedestroy($image);
                Log::info("Imagen convertida a WebP exitosamente: {$relativeFullPath}");
                return $relativeFullPath;
            }

            imagedestroy($image);
            Log::warning('Fallo al guardar como WebP. Guardando imagen original.');
            return $file->store($path, $disk);

        } catch (\Throwable $e) {
            Log::error('Error en ImageOptimizerTrait@saveImageAsWebP: ' . $e->getMessage());
            try {
                return $file->store($path, $disk);
            } catch (\Throwable $storeError) {
                Log::error('Error al guardar formato original por fallback: ' . $storeError->getMessage());
                return null;
            }
        }
    }
}
