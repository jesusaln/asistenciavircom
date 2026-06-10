<?php

namespace App\Services\Ventas;

use App\Models\Producto;
use App\Models\ProductoSerie;
use Illuminate\Support\Facades\Log;

class VentaValidationService
{
    /**
     * Sanitize product data to prevent injection attacks and normalize series
     */
    public function sanitizeProductData(array $productos): array
    {
        $sanitized = [];

        foreach ($productos as $producto) {
            $productoSanitized = $producto;

            // Sanitize series if they exist
            if (isset($producto['series']) && is_array($producto['series'])) {
                $seriesSanitizadas = [];
                foreach ($producto['series'] as $serie) {
                    // Remove potentially dangerous characters and trim
                    $serieLimpia = trim(preg_replace('/[^\w\-_@]/', '', $serie));

                    // Validate series format (alphanumeric, hyphens, underscores)
                    if (preg_match('/^[a-zA-Z0-9\-_@]+$/', $serieLimpia) && strlen($serieLimpia) <= 50) {
                        $seriesSanitizadas[] = $serieLimpia;
                    } else {
                        throw new \Exception("Formato de serie inválido: {$serie}");
                    }
                }
                $productoSanitized['series'] = $seriesSanitizadas;
            }

            // Sanitizar series de componentes para kits
            if (isset($producto['componentes_series']) && is_array($producto['componentes_series'])) {
                $componentesSanitizados = [];
                foreach ($producto['componentes_series'] as $compId => $seriesComp) {
                    if (!is_array($seriesComp)) {
                        continue;
                    }
                    $seriesSanitizadas = [];
                    foreach ($seriesComp as $serie) {
                        $serieLimpia = trim(preg_replace('/[^\w\-_@]/', '', $serie));
                        if (preg_match('/^[a-zA-Z0-9\-_@]+$/', $serieLimpia) && strlen($serieLimpia) <= 50) {
                            $seriesSanitizadas[] = $serieLimpia;
                        } else {
                            throw new \Exception("Formato de serie de componente inválido: {$serie}");
                        }
                    }
                    $componentesSanitizados[$compId] = $seriesSanitizadas;
                }
                $productoSanitized['componentes_series'] = $componentesSanitizados;
            }

            $sanitized[] = $productoSanitized;
        }

        return $sanitized;
    }

    // B-01: validateSeriesUniqueness removed (dead code — validation is handled
    // by StoreVentaRequest::validateSeriesUniqueness which is the active path).
}
