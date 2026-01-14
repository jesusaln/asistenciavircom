<?php

namespace App\Services;

use App\Services\EmpresaConfiguracionService;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

class FinancialService
{
    /**
     * Calculate all totals for a document (Sale, Quote, Purchase, Order)
     *
     * @param array $items Array of items with keys: cantidad, precio, descuento (optional), tipo (optional)
     * @param float $descuentoGeneral General discount amount (flat) or percentage depending on implementation logic. 
     *                                Usually in this system it seems to be an amount or percentage calculated before?
     *                                Checking previous code: $descuentoGeneral is often passed as a percentage in requests but stored/calculated differently.
     *                                VentaCreationService logic: $descuentoGeneral parameter is amount.
     *                                CompraCalculosService logic: $descuentoGeneral parameter is amount.
     *                                Let's standardize on Amount for the input here to be safe, or clarify.
     *                                Looking at VentaCreationService: $descuentoGeneral = $data['descuento_general'] ?? 0; (It's an amount).
     * @param int|null $clienteId Client ID for automatic tax retention logic (Sales/Quotes)
     * @param array $config Configuration overrides [aplicar_retencion_iva, aplicar_retencion_isr, ...]
     * @return array
     */
    public function calculateDocumentTotals(array $items, float $descuentoGeneral = 0.0, ?int $clienteId = null, array $config = []): array
    {
        $subtotal = 0.0;
        $descuentoItems = 0.0;
        $itemsCalculated = [];

        // 1. Calculate items subtotal and distribute general discount proportionally
        $rawSubtotal = 0.0;
        foreach ($items as $item) {
            $rawSubtotal += (float) ($item['cantidad'] ?? 0) * (float) ($item['precio'] ?? 0);
        }

        $totalItemsDescuento = 0.0;
        foreach ($items as $item) {
            $subItem = (float) ($item['cantidad'] ?? 0) * (float) ($item['precio'] ?? 0);
            $descuentoPorcentaje = (float) ($item['descuento'] ?? 0);
            $totalItemsDescuento += $subItem * ($descuentoPorcentaje / 100);
        }

        $subtotalDespuesDescuentosItems = $rawSubtotal - $totalItemsDescuento;

        // Ratio para prorratear el descuento general sobre el subtotal neto de items
        $descuentoGeneralRatio = $subtotalDespuesDescuentosItems > 0
            ? ($subtotalDespuesDescuentosItems - $descuentoGeneral) / $subtotalDespuesDescuentosItems
            : 0;

        $totalIva = 0.0;
        $totalRetIva = 0.0;
        $totalRetIsr = 0.0;
        $ivaRate = EmpresaConfiguracionService::getIvaPorcentaje() / 100;

        // Determinar tasas de retención
        $retIvaRate = 0.0;
        if (($config['aplicar_retencion_iva'] ?? false) && EmpresaConfiguracionService::isRetencionIvaEnabled()) {
            $retIvaRate = EmpresaConfiguracionService::getRetencionIvaDefault() / 100;
        }

        $retIsrRate = 0.0;
        if (($config['aplicar_retencion_isr'] ?? false) && EmpresaConfiguracionService::isRetencionIsrEnabled()) {
            $retIsrRate = EmpresaConfiguracionService::getRetencionIsrDefault() / 100;
        } elseif (($config['mode'] ?? 'sales') === 'sales' && EmpresaConfiguracionService::isIsrEnabled() && $clienteId) {
            $cliente = Cliente::find($clienteId);
            if ($cliente && $cliente->tipo_persona === 'moral') {
                $retIsrRate = EmpresaConfiguracionService::getIsrPorcentaje() / 100;
            }
        }

        foreach ($items as $item) {
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);
            $descuentoPorcentaje = (float) ($item['descuento'] ?? 0);

            $calculoItem = $this->calculateItemTotals($cantidad, $precio, $descuentoPorcentaje);

            // Base gravable del item (después de su descuento y parte proporcional del descuento general)
            $baseItem = $calculoItem['subtotal_final'] * $descuentoGeneralRatio;

            // Cálculo y redondeo por partida (SAT Compliance)
            $ivaItem = $this->round($baseItem * $ivaRate);
            $retIvaItem = $this->round($baseItem * $retIvaRate);
            $retIsrItem = $this->round($baseItem * $retIsrRate);

            $totalIva += $ivaItem;
            $totalRetIva += $retIvaItem;
            $totalRetIsr += $retIsrItem;

            $subtotal += $calculoItem['subtotal'];
            $descuentoItems += $calculoItem['descuento_monto'];
        }

        $subtotalBase = max(0.0, $subtotalDespuesDescuentosItems - $descuentoGeneral);

        // 5. Final Total
        $total = $subtotalBase + $totalIva - $totalRetIva - $totalRetIsr;

        return [
            'subtotal' => $this->round($subtotal),
            'descuento_items' => $this->round($descuentoItems),
            'descuento_general' => $this->round($descuentoGeneral),
            'subtotal_base' => $this->round($subtotalBase),
            'iva' => $this->round($totalIva),
            'retencion_iva' => $this->round($totalRetIva),
            'retencion_isr' => $this->round($totalRetIsr),
            'isr' => 0.0,
            'total' => $this->round($total),
        ];
    }

    /**
     * Calculate totals for a single item
     */
    public function calculateItemTotals(float $cantidad, float $precio, float $descuentoPorcentaje): array
    {
        $subtotal = $cantidad * $precio;
        $descuentoMonto = $subtotal * ($descuentoPorcentaje / 100);
        $subtotalFinal = $subtotal - $descuentoMonto;

        return [
            'cantidad' => $cantidad,
            'precio' => $precio, // Raw price
            'subtotal' => $subtotal, // Raw subtotal (qty * price)
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_monto' => $descuentoMonto,
            'subtotal_final' => $subtotalFinal
        ];
    }

    /**
     * Safe rounding function
     */
    public function round(mixed $value, int $precision = 2): float
    {
        return round((float) $value, $precision);
    }
}
