<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Redondea automáticamente submódulo, iva, total, descuentos, retenciones
 * al decemal más cercano (múltiplos de 0.10) en operaciones de lectura
 * (accessors) y al guardar (saving event).
 *
 * Reglas: <= 0.05 -> 0.00, > 0.05 -> 0.10 (sobre la parte decimal).
 *
 * Uso:
 *   use HasDecimalesRedondeados;
 */
trait RedondeaTotales
{
    public static function bootRedondeaTotales(): void
    {
        static::saving(function ($model) {
            $campos = [
                'subtotal',
                'descuento_general',
                'descuento_items',
                'iva',
                'retencion_iva',
                'retencion_isr',
                'isr',
                'total',
                'cfdi_total',
                'total_impuestos_trasladados',
                'total_impuestos_retenidos',
            ];

            foreach ($campos as $campo) {
                if (array_key_exists($campo, $model->getAttributes())) {
                    $model->{$campo} = self::redondearDecemal($model->{$campo});
                }
            }
        });
    }

    /**
     * Redondea un valor a 0.10: <= 0.05 baja a .00, > 0.05 sube a .10.
     */
    public static function redondearDecemal($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }
        $valor = (float) $valor;
        $entero = (int) floor($valor);
        $resto = round($valor - $entero, 2);
        if ($resto <= 0.05) {
            $decimales = 0.00;
        } elseif ($resto <= 0.15) {
            $decimales = 0.10;
        } elseif ($resto <= 0.25) {
            $decimales = 0.20;
        } elseif ($resto <= 0.35) {
            $decimales = 0.30;
        } elseif ($resto <= 0.45) {
            $decimales = 0.40;
        } elseif ($resto <= 0.55) {
            $decimales = 0.50;
        } elseif ($resto <= 0.65) {
            $decimales = 0.60;
        } elseif ($resto <= 0.75) {
            $decimales = 0.70;
        } elseif ($resto <= 0.85) {
            $decimales = 0.80;
        } elseif ($resto <= 0.95) {
            $decimales = 0.90;
        } else {
            $decimales = 0.00;
            $entero += 1;
        }
        return round($entero + $decimales, 2);
    }
}
