<?php

/**
 * Helper para manejo consistente de monedas y conversión de tipos
 * Resuelve Error #29: Manejo de Moneda Inconsistente
 */

namespace App\Support;

/**
 * Funciones helper para manejo de dinero y monedas
 */
class MoneyHelper
{
    /**
     * Convertir a centavos (entero)
     * Evita problemas de precisión con float
     */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convertir de centavos a pesos (float)
     */
    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }

    /**
     * Formatear como moneda MXN
     */
    public static function formatMXN(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',');
    }

    /**
     * Formatear como moneda genérica
     */
    public static function formatCurrency(float $amount, string $currency = 'MXN'): string
    {
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }

    /**
     * Redondear a 2 decimales de forma consistente
     */
    public static function round(float $value, int $precision = 2): float
    {
        return round($value, $precision, PHP_ROUND_HALF_UP);
    }

    /**
     * Validar que un valor sea positivo
     */
    public static function isPositive(float $value): bool
    {
        return $value >= 0;
    }

    /**
     * Calcular IVA
     */
    public static function calculateIVA(float $subtotal, float $ivaRate = 0.16): float
    {
        return self::round($subtotal * $ivaRate);
    }

    /**
     * Calcular subtotal desde total con IVA
     */
    public static function extractIVA(float $total, float $ivaRate = 0.16): array
    {
        $subtotal = self::round($total / (1 + $ivaRate));
        $iva = self::round($total - $subtotal);

        return [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
        ];
    }

    /**
     * Calcular retención de IVA (16% del IVA = 2/3 partes)
     */
    public static function calculateRetencionIVA(float $ivaAmount): float
    {
        return self::round($ivaAmount * (2 / 3));
    }

    /**
     * Calcular retención de ISR (1.25% del subtotal para personas morales)
     */
    public static function calculateRetencionISR(float $subtotal, float $isrRate = 0.0125): float
    {
        return self::round($subtotal * $isrRate);
    }

    /**
     * Aplicar descuento porcentual
     */
    public static function applyDiscount(float $originalPrice, float $discountPercent): float
    {
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new \InvalidArgumentException('Descuento debe estar entre 0 y 100');
        }
        return self::round($originalPrice * (1 - $discountPercent / 100));
    }

    /**
     * Calcular precio con ganancia
     */
    public static function calculatePriceWithMargin(float $cost, float $marginPercent): float
    {
        if ($marginPercent < 0 || $marginPercent > 100) {
            throw new \InvalidArgumentException('Margen debe estar entre 0 y 100');
        }
        return self::round($cost * (1 + $marginPercent / 100));
    }

    /**
     * Calcular margen de ganancia
     */
    public static function calculateMargin(float $cost, float $sellingPrice): float
    {
        if ($cost <= 0) {
            throw new \InvalidArgumentException('Costo debe ser mayor a 0');
        }
        $margin = (($sellingPrice - $cost) / $cost) * 100;
        return self::round($margin, 1);
    }
}
