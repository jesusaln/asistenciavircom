<?php

namespace App\Traits;

use App\Models\EmpresaConfiguracion;
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
     * @return string|null La ruta relativa del archivo guardado o null en caso de error.
     */
    public function saveImageAsWebP(UploadedFile $file, string $path = 'uploads', string $disk = 'public', ?int $quality = null): ?string
    {
        try {
            [$webpEnabled, $webpQuality] = $this->resolveImageOptimizationSettings($quality);

            if (!$webpEnabled) {
                return $file->store($path, $disk);
            }

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
            if (imagewebp($image, $absPath, $webpQuality)) {
                imagedestroy($image);
                Log::info("Imagen convertida a WebP exitosamente: {$relativeFullPath}");
                return $relativeFullPath;
            }

            imagedestroy($image);
            Log::warning('Fallo al guardar como WebP. Guardando imagen original.');
            return $file->store($path, $disk);

        } catch (\Exception $e) {
            Log::error('Error en ImageOptimizerTrait@saveImageAsWebP: ' . $e->getMessage());
            return $file->store($path, $disk);
        }
    }

    /**
     * Guarda cualquier archivo y convierte automáticamente a WebP cuando es imagen raster.
     *
     * @param UploadedFile $file Archivo subido.
     * @param string $path Directorio destino.
     * @param string $disk Disco de almacenamiento.
     * @param string|null $filename Nombre de archivo opcional para no-imágenes.
     * @param int $quality Calidad WebP (1-100).
     * @return string Ruta relativa almacenada.
     */
    public function storeFileOptimized(
        UploadedFile $file,
        string $path = 'uploads',
        string $disk = 'public',
        ?string $filename = null,
        ?int $quality = null
    ): string {
        $mimeType = (string) $file->getMimeType();
        $isRasterImage = str_starts_with($mimeType, 'image/') && !str_contains($mimeType, 'svg');

        if ($isRasterImage) {
            return $this->saveImageAsWebP($file, $path, $disk, $quality) ?? $file->store($path, $disk);
        }

        if ($filename) {
            return $file->storeAs($path, $filename, $disk);
        }

        return $file->store($path, $disk);
    }

    /**
     * Obtiene configuración de conversión WebP por empresa.
     *
     * @return array{0: bool, 1: int}
     */
    private function resolveImageOptimizationSettings(?int $quality = null): array
    {
        $enabled = true;
        $resolvedQuality = $quality ?? 80;

        try {
            $config = EmpresaConfiguracion::getConfig();
            if ($config) {
                $enabled = (bool) ($config->images_webp_enabled ?? true);
                if ($quality === null) {
                    $resolvedQuality = (int) ($config->images_webp_quality ?? 80);
                }
            }
        } catch (\Throwable $e) {
            Log::debug('No se pudo cargar configuración de imágenes para WebP.', [
                'error' => $e->getMessage(),
            ]);
        }

        $resolvedQuality = max(10, min(100, (int) $resolvedQuality));

        return [$enabled, $resolvedQuality];
    }
}
