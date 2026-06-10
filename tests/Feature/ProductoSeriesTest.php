<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Services\StockValidationService;

use Tests\TestCase;

class ProductoSeriesTest extends TestCase
{
    

    public function test_validate_stock_requires_series_and_ignores_soft_deleted()
    {
        $almacen = Almacen::factory()->create(['estado' => 'activo']);

        $producto = Producto::factory()->create([
            'requiere_serie' => true,
            'almacen_id' => $almacen->id,
            'stock' => 0,
            'reservado' => 0,
        ]);

        ProductoSerie::create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'numero_serie' => 'SERIE-001',
            'estado' => 'en_stock',
        ]);

        $serieSoftDeleted = ProductoSerie::create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'numero_serie' => 'SERIE-002',
            'estado' => 'en_stock',
        ]);
        $serieSoftDeleted->delete(); // No debe contarse como disponible

        $service = app(StockValidationService::class);

        // Sin series: debe fallar
        $resultadoSinSeries = $service->validateStockForSale([
            [
                'id' => $producto->id,
                'cantidad' => 1,
                'series' => [],
            ],
        ], $almacen->id);

        $this->assertFalse($resultadoSinSeries['valid']);

        // Con serie valida: debe pasar
        $resultadoConSerie = $service->validateStockForSale([
            [
                'id' => $producto->id,
                'cantidad' => 1,
                'series' => ['SERIE-001'],
            ],
        ], $almacen->id);

        $this->assertTrue($resultadoConSerie['valid']);
        $this->assertEmpty($resultadoConSerie['errors']);

        // Intentar vender 2 usando una serie eliminada: debe fallar
        $resultadoSerieEliminada = $service->validateStockForSale([
            [
                'id' => $producto->id,
                'cantidad' => 2,
                'series' => ['SERIE-001', 'SERIE-002'],
            ],
        ], $almacen->id);

        $this->assertFalse($resultadoSerieEliminada['valid']);
    }
}
