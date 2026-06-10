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
    protected $ventaCreationService;

    public function __construct(FolioService $folioService, \App\Services\Ventas\VentaCreationService $ventaCreationService)
    {
        $this->folioService = $folioService;
        $this->ventaCreationService = $ventaCreationService;
    }

    /**
     * Creates a Venta and related records from a completed Cita.
     *
     * @param \App\Models\Cita $cita The completed appointment.
     * @param array $paymentData Optional payment data (pago_recibido, metodo_pago, cuenta_id)
     * @return \App\Models\Venta|null The created Venta instance or null on failure.
     */
    public function createFromCita(Cita $cita, array $paymentData = []): ?Venta
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

        try {
            // Prepare data for VentaCreationService
            $productos = [];
            $servicios = [];

            foreach ($cita->items as $item) {
                if ($item->citable_type === \App\Models\Producto::class) {
                    $productos[] = [
                        'id' => $item->citable_id,
                        'cantidad' => $item->cantidad,
                        'precio' => $item->precio, // Usar precio pactado en cita
                        'descuento' => $item->descuento,
                        'series' => $item->series ?? [], // Pass series captured by technician
                        'price_list_id' => null, // Precio manual (el de la cita)
                    ];
                } elseif ($item->citable_type === \App\Models\Servicio::class) {
                    $servicios[] = [
                        'id' => $item->citable_id,
                        'cantidad' => $item->cantidad,
                        'precio' => $item->precio,
                        'descuento' => $item->descuento,
                    ];
                }
            }

            $pagoRecibido = ($paymentData['pago_recibido'] ?? 'no') === 'si';
            $metodoPago = $paymentData['metodo_pago'] ?? 'credito';
            
            // Si no se recibió pago, el método de la VENTA es 'credito' (CXC)
            // Si se recibió pago, el método puede ser 'efectivo', 'transferencia', etc.
            $metodoVenta = $pagoRecibido ? $metodoPago : 'credito';

            $ventaData = [
                'cliente_id' => $cita->cliente_id,
                'almacen_id' => $almacenId,
                'metodo_pago' => $metodoVenta,
                'forma_pago_sat' => $pagoRecibido ? $this->getFormaPagoSat($metodoPago) : '99',
                'metodo_pago_sat' => $pagoRecibido ? 'PUE' : 'PPD',
                'descuento_general' => 0,
                'notas' => 'Generada automáticamente desde Cita #' . $cita->id,
                'productos' => $productos,
                'servicios' => $servicios,
                'cita_id' => $cita->id,
                'cuenta_bancaria_id' => $pagoRecibido ? ($paymentData['cuenta_id'] ?? null) : null,
            ];

            // Delegate to robust creation service
            // true flag para usar precios fijos (los de la cita) y no recalcular
            $venta = $this->ventaCreationService->createVenta($ventaData, true);

            Log::info("Venta #{$venta->numero_venta} generada exitosamente desde cita #{$cita->id} usando VentaCreationService.");

            return $venta;

        } catch (\Exception $e) {
            Log::error("Error al generar venta para cita #{$cita->id}: " . $e->getMessage());
            // No need to rollback manually as createVenta handles its own transaction, 
            // but if we were wrapping more logic, we might need to.
            return null;
        }
    }
    /**
     * Map internal payment methods to SAT codes.
     */
    protected function getFormaPagoSat(string $metodo): string
    {
        return match ($metodo) {
            'efectivo' => '01',
            'transferencia' => '03',
            'tarjeta', 'terminal' => '04', // Tarjeta de crédito/débito simplificado
            'deposito' => '01',
            default => '99',
        };
    }
}
