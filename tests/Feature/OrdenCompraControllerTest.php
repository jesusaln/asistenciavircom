<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Folio\FolioService;

class OrdenCompraControllerTest extends TestCase
{
    public function test_obtener_siguiente_numero_usa_folio_service()
    {
        $this->withoutMiddleware();

        $this->mock(FolioService::class, function ($mock) {
            $mock->shouldReceive('previewNextFolio')
                ->once()
                ->with('orden_compra')
                ->andReturn('OC-123');
        });

        $this->get(route('ordenescompra.siguiente-numero'))
            ->assertStatus(200)
            ->assertJson(['siguiente_numero' => 'OC-123']);
    }
}
