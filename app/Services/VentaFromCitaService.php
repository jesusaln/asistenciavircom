<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\CuentasPorCobrar;
use App\Models\Almacen;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Folio\FolioService;

class VentaFromCitaService
{
    protected $folioService;

    public function __construct(FolioService $folioService)
    {
        $this->folioService = $folioService;
    }

    /**
     * Creates a Venta and related records from a completed Cita.
     *
     * @param \App\Models\Cita $cita The completed appointment.
     * @return \App\Models\Venta|null The created Venta instance or null on failure.
     */
    public function createFromCita(Cita $cita): ?Venta
    {
        // Pre-conditions check
        if ($cita->estado !== Cita::ESTADO_COMPLETADO || $cita->items()->count() === 0) {
            return null;
        }
        
        if (Venta::where('cita_id', $cita->id)->exists()) {
            Log::info("La venta para la cita #{$cita->id} ya existe. No se creará una nueva.");
            return null;
        }

        $user = auth()->user() ?? $cita->tecnico; // Fallback to technician if no authenticated user
        if (!$user) {
            Log::error("No se pudo determinar un usuario/vendedor para la venta de la cita #{$cita->id}.");
            return null;
        }

        // Find a suitable warehouse
        $almacenId = $user->almacen_venta_id
            ?? Almacen::where('nombre', 'like', '%principal%')->first()?->id
            ?? Almacen::first()?->id;

        if (!$almacenId) {
            Log::warning("No se pudo generar venta para cita #{$cita->id}: No se encontró un almacén.");
            return null;
        }

        DB::beginTransaction();
        try {
            // Generate Folio using the service
            $numeroVenta = $this->folioService->getNextFolio('venta');

            // Create Venta
            $venta = Venta::create([
                'numero_venta' => $numeroVenta,
                'empresa_id' => $cita->empresa_id,
                'cliente_id' => $cita->cliente_id,
                'cita_id' => $cita->id,
                'fecha' => now(),
                'estado' => 'pendiente',
                'subtotal' => $cita->subtotal,
                'iva' => $cita->iva,
                'total' => $cita->total,
                'almacen_id' => $almacenId,
                'vendedor_type' => get_class($user),
                'vendedor_id' => $user->id,
                'notas' => 'Generada automáticamente desde Cita #' . $cita->id,
                'pagado' => false,
            ]);

            // Create VentaItems
            foreach ($cita->items as $cItem) {
                VentaItem::create([
                    'empresa_id' => $venta->empresa_id,
                    'venta_id' => $venta->id,
                    'ventable_type' => $cItem->citable_type,
                    'ventable_id' => $cItem->citable_id,
                    'cantidad' => $cItem->cantidad,
                    'precio' => $cItem->precio,
                    'descuento' => $cItem->descuento,
                    'subtotal' => $cItem->subtotal,
                    'costo_unitario' => 0, // TODO: This should be calculated if it's a product
                ]);
                
                // TODO: Stock decrement logic should be handled here, ideally via an InventoryService
            }

            // Create CuentasPorCobrar
            CuentasPorCobrar::create([
                'empresa_id' => $venta->empresa_id,
                'cliente_id' => $venta->cliente_id,
                'cobrable_type' => Venta::class,
                'cobrable_id' => $venta->id,
                'folio' => $this->folioService->getNextFolio('cxc') ?? 'CXC-' . $venta->id,
                'monto_total' => $venta->total,
                'monto_pendiente' => $venta->total,
                'fecha_emision' => now(),
                'fecha_vencimiento' => now()->addDays($cita->cliente->dias_credito ?? 15),
                'estado' => 'pendiente',
                'concepto' => 'Cargo por servicio y/o productos de Cita #' . $cita->id,
            ]);

            DB::commit();

            Log::info("Venta #{$venta->numero_venta} y CXC generadas exitosamente para la cita #{$cita->id}.");

            return $venta;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico al generar venta para cita #{$cita->id}: " . $e->getMessage());
            return null;
        }
    }
}
