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

    /**
     * Validate that series are not duplicated in the request and database
     */
    public function validateSeriesUniqueness(array $productos): array
    {
        $errors = [];
        $seriesUsadas = [];

        foreach ($productos as $producto) {
            if (isset($producto['series'])) {
                foreach ($producto['series'] as $serie) {
                    // Validar duplicados en request
                    if (in_array($serie, $seriesUsadas)) {
                        $errors[] = "La serie {$serie} está duplicada en la solicitud";
                        continue;
                    }

                    // Validar que no exista en BD para otro producto
                    $productId = $producto['id'] ?? $producto['producto_id'] ?? 0;
                    $serieExistente = ProductoSerie::where('numero_serie', $serie)
                        ->where('producto_id', '!=', $productId)
                        ->whereNull('deleted_at')
                        ->first();

                    if ($serieExistente) {
                        $productoAsociado = Producto::find($serieExistente->producto_id);
                        $errors[] = "La serie {$serie} ya está registrada para el producto '" . ($productoAsociado->nombre ?? 'Desconocido') . "'";
                        continue;
                    }

                    $seriesUsadas[] = $serie;
                }
            }

            // También validar para componentes de kits
            if (isset($producto['componentes_series'])) {
                foreach ($producto['componentes_series'] as $compId => $seriesComp) {
                    foreach ($seriesComp as $serie) {
                        if (in_array($serie, $seriesUsadas)) {
                            $errors[] = "La serie de componente {$serie} está duplicada en la solicitud";
                            continue;
                        }

                        $serieExistente = ProductoSerie::where('numero_serie', $serie)
                            ->where('producto_id', '!=', $compId)
                            ->whereNull('deleted_at')
                            ->first();

                        if ($serieExistente) {
                            $productoAsociado = Producto::find($serieExistente->producto_id);
                            $errors[] = "La serie de componente {$serie} ya está registrada para otro producto '" . ($productoAsociado->nombre ?? 'Desconocido') . "'";
                            continue;
                        }

                        $seriesUsadas[] = $serie;
                    }
                }
            }
        }

        return $errors;
    }
}
