<?php

namespace App\Services;

use App\Services\EmpresaConfiguracionService;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

class FinancialService
{
    /**
     * Calculate totals for a single line item.
     *
     * @return array{subtotal_bruto: float, descuento_porcentaje: float, descuento_monto: float, subtotal_final: float}
     */
    public function calculateItemTotals(float|int $cantidad, float|int $precio, float|int $descuento = 0): array
    {
        $cantidad = (float) $cantidad;
        $precio = (float) $precio;
        $descuento = (float) $descuento;

        $subtotalBruto = $cantidad * $precio;
        $descuentoMonto = $subtotalBruto * ($descuento / 100);
        $subtotalFinal = max(0, $subtotalBruto - $descuentoMonto);

        return [
            'subtotal_bruto' => $this->round($subtotalBruto),
            'descuento_porcentaje' => $this->round($descuento),
            'descuento_monto' => $this->round($descuentoMonto),
            'subtotal_final' => $this->round($subtotalFinal),
        ];
    }

    /**
     * Calculate all totals for a document (Sale, Quote, Purchase, Order)
     *
     * @param array $items Array of items with keys: cantidad, precio, descuento (optional), tipo (optional)
     * @param float $descuentoGeneral General discount amount (flat amount)
     * @param int|null $clienteId Client ID for automatic tax retention logic (Sales/Quotes)
     * @param array $config Configuration overrides [aplicar_retencion_iva, aplicar_retencion_isr, mode, ...]
     * @return array
     */
    public function calculateDocumentTotals(array $items, float $descuentoGeneral = 0.0, ?int $clienteId = null, array $config = []): array
    {
        try {
            Log::debug('FinancialService: Calculating totals', [
                'items_count' => count($items),
                'descuento_general' => $descuentoGeneral,
                'cliente_id' => $clienteId,
                'config' => $config
            ]);

            $subtotal = 0.0;
            $descuentoItems = 0.0;

            // 1. Calculate raw subtotal and individual item discounts
            foreach ($items as $item) {
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precio = (float) ($item['precio'] ?? 0);
                $descuentoPorcentaje = (float) ($item['descuento'] ?? 0);

                $lineaSubtotal = $cantidad * $precio;
                $lineaDescuento = $lineaSubtotal * ($descuentoPorcentaje / 100);
                
                $subtotal += $lineaSubtotal;
                $descuentoItems += $lineaDescuento;
            }

            $subtotalNeto = $subtotal - $descuentoItems;
            $subtotalBase = max(0.0, $subtotalNeto - $descuentoGeneral);

            // 2. Taxes logic
            $totalIva = 0.0;
            $totalRetIva = 0.0;
            $totalRetIsr = 0.0;

            // Get rates from config or service
            $ivaRate = ($config['iva_rate'] ?? EmpresaConfiguracionService::getIvaPorcentaje()) / 100;
            
            $retIvaRate = 0.0;
            if ($config['aplicar_retencion_iva'] ?? false) {
                $retIvaRate = ($config['retencion_iva_rate'] ?? EmpresaConfiguracionService::getRetencionIvaDefault()) / 100;
            }

            $retIsrRate = 0.0;
            if ($config['aplicar_retencion_isr'] ?? false) {
                $retIsrRate = ($config['retencion_isr_rate'] ?? EmpresaConfiguracionService::getRetencionIsrDefault()) / 100;
            } elseif (($config['mode'] ?? 'sales') === 'sales' && EmpresaConfiguracionService::isIsrEnabled() && $clienteId) {
                $cliente = Cliente::find($clienteId);
                if ($cliente && $cliente->tipo_persona === 'moral') {
                    $retIsrRate = ($config['isr_rate'] ?? EmpresaConfiguracionService::getIsrPorcentaje()) / 100;
                }
            }

            // Ratio for general discount distribution (SAT compliance)
            // We apply the general discount proportionally to each line item's base for tax calculation
            $descuentoGeneralRatio = $subtotalNeto > 0 ? $subtotalBase / $subtotalNeto : 0;

            foreach ($items as $item) {
                if ((bool) ($item['iva_exento'] ?? false)) continue;

                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precio = (float) ($item['precio'] ?? 0);
                $descuentoPorcentaje = (float) ($item['descuento'] ?? 0);

                // Subtotal for the line after its own discount
                $lineaSubtotalNeto = ($cantidad * $precio) * (1 - ($descuentoPorcentaje / 100));
                
                // Base for tax calculation (pro-rated general discount)
                $baseGravableLinea = $lineaSubtotalNeto * $descuentoGeneralRatio;

                // Sum up taxes rounded by line item (SAT requirement)
                $totalIva += $this->round($baseGravableLinea * $ivaRate);
                $totalRetIva += $this->round($baseGravableLinea * $retIvaRate);
                $totalRetIsr += $this->round($baseGravableLinea * $retIsrRate);
            }

            $total = $subtotalBase + $totalIva - $totalRetIva - $totalRetIsr;

            return [
                'subtotal' => $this->round($subtotal),
                'descuento_items' => $this->round($descuentoItems),
                'descuento_general' => $this->round($descuentoGeneral),
                'subtotal_base' => $this->round($subtotalBase),
                'iva' => $this->round($totalIva),
                'retencion_iva' => $this->round($totalRetIva),
                'retencion_isr' => $this->round($totalRetIsr),
                'isr' => $this->round($totalRetIsr),
                'total' => $this->round($total),
                'iva_rate' => $ivaRate * 100,
                'ret_iva_rate' => $retIvaRate * 100,
                'ret_isr_rate' => $retIsrRate * 100,
            ];

        } catch (\Throwable $e) {
            Log::error('FinancialService: Exception during calculation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Safe rounding function
     */
    public function round(mixed $value, int $precision = 2): float
    {
        return round((float) $value, $precision);
    }
}
