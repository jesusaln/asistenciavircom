<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\Servicio;
use App\Models\Almacen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Folio\FolioService;
use App\Services\EmpresaConfiguracionService;

class VentaFromTicketService
{
    protected $folioService;

    public function __construct(FolioService $folioService)
    {
        $this->folioService = $folioService;
    }

    /**
     * Creates a Venta from a Ticket.
     *
     * @param \App\Models\Ticket $ticket
     * @return \App\Models\Venta|null
     */
    public function createFromTicket(Ticket $ticket): ?Venta
    {
        if ($ticket->tipo_servicio !== 'costo' || $ticket->venta_id || !$ticket->cliente_id) {
            Log::warning("No se puede generar venta para el ticket #{$ticket->folio}. Condiciones no cumplidas.", [
                'tipo_servicio' => $ticket->tipo_servicio,
                'venta_id' => $ticket->venta_id,
                'cliente_id' => $ticket->cliente_id,
            ]);
            return null;
        }

        DB::beginTransaction();
        try {
            $user = auth()->user() ?? $ticket->asignado ?? User::find(1); // Fallback
            $almacenId = $user->almacen_venta_id ?? Almacen::first()->id;

            $numeroVenta = $this->folioService->getNextFolio('venta');

            $servicioSoporte = Servicio::firstOrCreate(
                ['nombre' => 'Servicio de Soporte Técnico'],
                [
                    'descripcion' => 'Servicio técnico de soporte generado desde tickets',
                    'codigo' => 'SOPORTE-TKT',
                    'categoria_id' => Servicio::whereNotNull('categoria_id')->value('categoria_id') ?? 1,
                    'precio' => 650,
                    'duracion' => 60,
                    'estado' => 'activo',
                    'margen_ganancia' => 100,
                    'comision_vendedor' => 0,
                ]
            );

            $notasVenta = "Ticket #{$ticket->folio}: {$ticket->titulo}\n\nDetalles del servicio:\n{$ticket->descripcion}";
            
            $subtotalBase = $servicioSoporte->precio;
            $ivaPorcentaje = EmpresaConfiguracionService::getIvaPorcentaje() / 100;
            $ivaMonto = $subtotalBase * $ivaPorcentaje;
            $totalMonto = $subtotalBase + $ivaMonto;

            $venta = Venta::create([
                'cliente_id' => $ticket->cliente_id,
                'almacen_id' => $almacenId,
                'numero_venta' => $numeroVenta,
                'fecha' => now(),
                'estado' => 'pendiente',
                'subtotal' => $subtotalBase,
                'iva' => $ivaMonto,
                'total' => $totalMonto,
                'notas' => $notasVenta,
                'vendedor_type' => get_class($user),
                'vendedor_id' => $user->id,
            ]);

            VentaItem::create([
                'venta_id' => $venta->id,
                'ventable_type' => Servicio::class,
                'ventable_id' => $servicioSoporte->id,
                'cantidad' => 1,
                'precio' => $subtotalBase,
                'descuento' => 0,
                'subtotal' => $subtotalBase,
            ]);

            $ticket->update(['venta_id' => $venta->id]);

            DB::commit();

            Log::info("Venta #{$venta->numero_venta} creada desde Ticket #{$ticket->folio}.");

            return $venta;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generando venta desde ticket #{$ticket->folio}: " . $e->getMessage());
            return null;
        }
    }
}
