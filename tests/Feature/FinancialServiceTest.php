<?php

namespace Tests\Feature;

use App\Services\FinancialService;
use Tests\TestCase;


class FinancialServiceTest extends TestCase
{
    private FinancialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure default config exists
        \App\Services\EmpresaConfiguracionService::inicializarConfiguracionPorDefecto();
        $this->service = new FinancialService();
    }

    public function test_calculate_item_totals()
    {
        $cantidad = 2;
        $precio = 100.00;
        $descuento = 10.0; // 10%

        $result = $this->service->calculateItemTotals($cantidad, $precio, $descuento);

        // Subtotal = 2 * 100 = 200
        // Discount = 200 * 0.10 = 20
        // Final = 180

        // Use 'subtotal' as per service implementation, not 'subtotal_bruto'
        $this->assertEquals(200.00, $result['subtotal']);
        $this->assertEquals(20.00, $result['descuento_monto']);
        $this->assertEquals(180.00, $result['subtotal_final']);
    }

    public function test_calculate_document_totals_basic_sales()
    {
        $items = [
            [
                'cantidad' => 1,
                'precio' => 100,
                'descuento' => 0
            ]
        ];

        $results = $this->service->calculateDocumentTotals($items, 0, null, ['mode' => 'sales']);

        $this->assertEquals(100.00, $results['subtotal']);
        // 16% IVA is standard default
        $this->assertEquals($results['subtotal'] + $results['iva'], $results['total']);
    }

    public function test_calculate_document_totals_manual_retention_purchase()
    {
        // Update DB to enable retentions
        $configDb = \App\Models\EmpresaConfiguracion::first();
        if ($configDb) {
            $configDb->update([
                'enable_retencion_iva' => true,
                'enable_retencion_isr' => true,
                'retencion_iva' => 10.6667,
                'retencion_isr' => 10.0,
            ]);
        }

        $items = [
            [
                'cantidad' => 1,
                'precio' => 1000,
                'descuento' => 0
            ]
        ];

        // Enable retentions in request config
        $config = [
            'mode' => 'purchases',
            'aplicar_retencion_iva' => true,
            'aplicar_retencion_isr' => true
        ];

        $results = $this->service->calculateDocumentTotals($items, 0, null, $config);

        $this->assertGreaterThan(0, $results['retencion_iva'], 'Retencion IVA should be > 0');
        $this->assertGreaterThan(0, $results['retencion_isr'], 'Retencion ISR should be > 0');

        // Validation of total formula
        $expectedTotal = $results['subtotal'] + $results['iva'] - $results['retencion_iva'] - $results['retencion_isr'] - $results['isr'];

        $this->assertEqualsWithDelta($expectedTotal, $results['total'], 0.01);
    }

    public function test_rounding_precision()
    {
        $val = 100.555;
        $rounded = $this->service->round($val);
        $this->assertEquals(100.56, $rounded);
    }
}
