<?php

namespace App\Models\Traits;

use App\Services\Folio\FolioService;
use Illuminate\Support\Facades\App;

/**
 * Trait HasFolio
 *
 * Garantiza que los modelos tengan un folio/código único generado automáticamente
 * usando el FolioService centralizado. Esto asegura consistencia entre
 * web, API/Ionic, y cualquier otro cliente.
 *
 * Uso:
 *   protected string $folioType = 'venta'; // Tipo de documento para FolioService
 *   protected string $folioField = 'numero_venta'; // Campo donde se guarda el folio
 *
 * @trait
 */
trait HasFolio
{
    /**
     * Bootstrap the trait.
     */
    public static function bootHasFolio(): void
    {
        static::creating(function ($model) {
            /** @var HasFolio $model */
            if (!$model->folioField || !$model->folioType) {
                return;
            }

            // Solo generar folio si no viene pre-configurado (permite override manual)
            if (empty($model->{$model->folioField})) {
                $folioService = App::make(FolioService::class);
                $model->{$model->folioField} = $folioService->getNextFolio($model->folioType);
            }
        });
    }

    /**
     * Regenerar el folio del modelo (úsalo con precaución).
     *
     * @return string El nuevo folio generado
     */
    public function regenerateFolio(): string
    {
        if (!$this->folioField || !$this->folioType) {
            throw new \RuntimeException('Trait HasFolio no está configurado correctamente en este modelo.');
        }

        $folioService = App::make(FolioService::class);
        $newFolio = $folioService->getNextFolio($this->folioType);

        $this->update([$this->folioField => $newFolio]);

        return $newFolio;
    }

    /**
     * Obtener el tipo de documento para FolioService.
     *
     * @return string|null
     */
    public function getFolioType(): ?string
    {
        return $this->folioType ?? null;
    }

    /**
     * Obtener el campo donde se guarda el folio.
     *
     * @return string|null
     */
    public function getFolioField(): ?string
    {
        return $this->folioField ?? null;
    }
}
