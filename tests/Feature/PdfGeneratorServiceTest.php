<?php

namespace Tests\Feature;

use App\Services\PdfGeneratorService;
use Tests\TestCase;
use Barryvdh\DomPDF\PDF;
use Mockery;

class PdfGeneratorServiceTest extends TestCase
{
    public function test_load_view_returns_pdf_instance()
    {
        // We can't easily mock static method calls like Pdf::loadView without Facade mocking or advanced techniques.
        // However, we can check basic execution if the view exists, or use a basic simple view.

        // Ensure default config so service doesn't crash on DB
        \App\Services\EmpresaConfiguracionService::inicializarConfiguracionPorDefecto();

        $service = new PdfGeneratorService();

        // Create a temporary view or use a simple existing one. 
        // We probably shouldn't depend on actual complex views like 'cotizacion_pdf' which might need data.
        // Let's rely on the fact that if we pass an empty view name it might fail, OR we mock the Facade.

        \Illuminate\Support\Facades\View::addLocation(resource_path('views'));
        // Let's mock the Pdf facade
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn(Mockery::mock(PDF::class, function ($mock) {
                $mock->shouldReceive('setPaper')->once();
                $mock->shouldReceive('setOptions')->once();
            }));

        $pdf = $service->loadView('emails.simple_test_view', []);
        // Note: The view name passed to loadView mock must match or be ignored if we use generic args.
        // But the service calls loadView, so the string doesn't matter much if mocked.

        $this->assertNotNull($pdf);
    }

    public function test_ticket_options_applied()
    {
        \App\Services\EmpresaConfiguracionService::inicializarConfiguracionPorDefecto();

        $service = new PdfGeneratorService();

        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn(Mockery::mock(PDF::class, function ($mock) {
                $mock->shouldReceive('setPaper')->with([0, 0, 226.77, 1000], 'portrait')->once();
                $mock->shouldReceive('setOptions')->with(Mockery::on(function ($options) {
                    return isset($options['defaultFont']) && $options['defaultFont'] === 'monospace';
                }))->once();
            }));

        $service->loadView('tickets.view', [], [0, 0, 226.77, 1000]);
    }
}
