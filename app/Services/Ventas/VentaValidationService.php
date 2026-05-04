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
     * Validate that series are not duplicated in the request and database,
     * and that they are available for the specific product and warehouse.
     */
    public function validateSeriesUniqueness(array $productos, ?int $almacenId = null): array
    {
        $errors = [];
        $seriesUsadasEnRequest = [];

        foreach ($productos as $producto) {
            $productId = $producto['id'] ?? $producto['producto_id'] ?? 0;
            $productoModel = \App\Models\Producto::find($productId);
            
            if (!$productoModel || !($productoModel->requiere_serie ?? false)) {
                continue;
            }

            if (isset($producto['series']) && is_array($producto['series'])) {
                foreach ($producto['series'] as $numeroSerie) {
                    // 1. Check for duplicates within the request
                    if (in_array($numeroSerie, $seriesUsadasEnRequest)) {
                        $errors[] = "La serie '{$numeroSerie}' está duplicada en su solicitud.";
                        continue;
                    }
                    $seriesUsadasEnRequest[] = $numeroSerie;

                    // 2. Comprehensive DB check
                    $serieExistente = \App\Models\ProductoSerie::where('numero_serie', $numeroSerie)
                        ->whereNull('deleted_at')
                        ->first();

                    if (!$serieExistente) {
                        $errors[] = "La serie '{$numeroSerie}' no está registrada en el sistema.";
                        continue;
                    }

                    // 3. Belonging check
                    if ($serieExistente->producto_id != $productId) {
                        $errors[] = "La serie '{$numeroSerie}' pertenece al producto '" . ($serieExistente->producto->nombre ?? 'otro') . "', no a '{$productoModel->nombre}'.";
                        continue;
                    }

                    // 4. Status check
                    if ($serieExistente->estado !== 'en_stock') {
                        $errors[] = "La serie '{$numeroSerie}' no está disponible (Estado: " . ucfirst($serieExistente->estado) . ").";
                        continue;
                    }

                    // 5. Warehouse check
                    if ($almacenId && $serieExistente->almacen_id != $almacenId) {
                        $errors[] = "La serie '{$numeroSerie}' se encuentra en el almacén '" . ($serieExistente->almacen->nombre ?? 'otro') . "', pero la venta se está realizando desde el almacén actual.";
                        continue;
                    }
                }
            }

            // También validar para componentes de kits
            if (isset($producto['componentes_series']) && is_array($producto['componentes_series'])) {
                foreach ($producto['componentes_series'] as $compId => $seriesComp) {
                    $compModel = \App\Models\Producto::find($compId);
                    foreach ($seriesComp as $numeroSerie) {
                        if (in_array($numeroSerie, $seriesUsadasEnRequest)) {
                            $errors[] = "La serie de componente '{$numeroSerie}' está duplicada en su solicitud.";
                            continue;
                        }
                        $seriesUsadasEnRequest[] = $numeroSerie;

                        $serieExistente = \App\Models\ProductoSerie::where('numero_serie', $numeroSerie)
                            ->whereNull('deleted_at')
                            ->first();

                        if (!$serieExistente) {
                            $errors[] = "La serie de componente '{$numeroSerie}' no está registrada.";
                            continue;
                        }

                        if ($serieExistente->producto_id != $compId) {
                            $errors[] = "La serie '{$numeroSerie}' no pertenece al componente '" . ($compModel->nombre ?? $compId) . "'.";
                            continue;
                        }

                        if ($serieExistente->estado !== 'en_stock') {
                            $errors[] = "La serie de componente '{$numeroSerie}' no está disponible.";
                            continue;
                        }

                        if ($almacenId && $serieExistente->almacen_id != $almacenId) {
                            $errors[] = "La serie '{$numeroSerie}' no está en el almacén seleccionado.";
                            continue;
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
