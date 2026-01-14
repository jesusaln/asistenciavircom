<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\VentaCreationService;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * ✅ SAFE TESTS: Uses DatabaseTransactions to rollback all changes
 * NO data will be persisted to the database
 */
class VentaCreationServiceTest extends TestCase
{
    use DatabaseTransactions; // ✅ AUTO ROLLBACK - No data deleted!

    protected VentaCreationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VentaCreationService::class);
    }

    /**
     * @test
     * Test that totals are calculated correctly
     */
    public function calcula_totales_correctamente()
    {
        $data = [
            'productos' => [
                [
                    'id' => 1,
                    'cantidad' => 2,
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
            'servicios' => [],
            'descuento_general' => 0,
        ];

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateTotals');
        $method->setAccessible(true);

        $totals = $method->invoke($this->service, $data);

        // Verify calculations
        $this->assertEquals(200, $totals['subtotal']); // 2 x 100
        $this->assertEquals(0, $totals['descuento_general']);
        $this->assertGreaterThan(0, $totals['iva']); // Should have IVA
        $this->assertGreaterThan(200, $totals['total']); // Should be > subtotal
    }

    /**
     * @test
     * Test totals with discount
     */
    public function calcula_totales_con_descuento()
    {
        $data = [
            'productos' => [
                [
                    'id' => 1,
                    'cantidad' => 1,
                    'precio' => 100,
                    'descuento' => 10, // 10% discount
                ],
            ],
            'servicios' => [],
            'descuento_general' => 0,
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateTotals');
        $method->setAccessible(true);

        $totals = $method->invoke($this->service, $data);

        $this->assertEquals(100, $totals['subtotal']);
        $this->assertEquals(10, $totals['descuento_items']); // 10% of 100
        $this->assertGreaterThan(0, $totals['total']);
    }

    /**
     * @test
     * Test totals with general discount
     */
    public function calcula_totales_con_descuento_general()
    {
        $data = [
            'productos' => [
                [
                    'id' => 1,
                    'cantidad' => 1,
                    'precio' => 100,
                    'descuento' => 0,
                ],
            ],
            'servicios' => [],
            'descuento_general' => 20, // $20 general discount
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateTotals');
        $method->setAccessible(true);

        $totals = $method->invoke($this->service, $data);

        $this->assertEquals(100, $totals['subtotal']);
        $this->assertEquals(20, $totals['descuento_general']);
        $this->assertEquals(0, $totals['descuento_items']);
    }

    /**
     * @test
     * Test that multiple products sum correctly
     */
    public function suma_multiples_productos_correctamente()
    {
        $data = [
            'productos' => [
                [
                    'id' => 1,
                    'cantidad' => 2,
                    'precio' => 100,
                    'descuento' => 0,
                ],
                [
                    'id' => 2,
                    'cantidad' => 1,
                    'precio' => 50,
                    'descuento' => 0,
                ],
            ],
            'servicios' => [],
            'descuento_general' => 0,
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateTotals');
        $method->setAccessible(true);

        $totals = $method->invoke($this->service, $data);

        $this->assertEquals(250, $totals['subtotal']); // (2*100) + (1*50)
    }

    /**
     * @test
     * Test services are included in totals
     */
    public function incluye_servicios_en_totales()
    {
        $data = [
            'productos' => [],
            'servicios' => [
                [
                    'id' => 1,
                    'cantidad' => 1,
                    'precio' => 150,
                    'descuento' => 0,
                ],
            ],
            'descuento_general' => 0,
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateTotals');
        $method->setAccessible(true);

        $totals = $method->invoke($this->service, $data);

        $this->assertEquals(150, $totals['subtotal']);
        $this->assertGreaterThan(0, $totals['total']);
    }
}
