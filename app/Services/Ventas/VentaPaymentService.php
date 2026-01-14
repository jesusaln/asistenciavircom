<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\VentaAuditLog;
use App\Services\PaymentService;
use App\Services\EntregaDineroService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Enums\MetodoPago;

class VentaPaymentService
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {
    }

    /**
     * Mark a sale as paid and process financial records.
     *
     * @param Venta $venta
     * @param array $data Validated data containing metodo_pago, cuenta_bancaria_id, notas_pago
     * @return Venta
     * @throws \Exception
     */
    public function markAsPaid(Venta $venta, array $data): Venta
    {
        return DB::transaction(function () use ($venta, $data) {
            $metodoPagoInput = $data['metodo_pago'] ?? $venta->metodo_pago;

            // Normalize payment method
            $metodoPagoEnum = MetodoPago::tryFrom($metodoPagoInput);
            if (!$metodoPagoEnum) {
                foreach (MetodoPago::cases() as $case) {
                    if (strcasecmp($case->value, $metodoPagoInput) === 0) {
                        $metodoPagoEnum = $case;
                        break;
                    }
                }
            }
            $metodoPagoString = $metodoPagoEnum ? $metodoPagoEnum->value : $metodoPagoInput;

            // Validate bank account dependency
            $metodosBancarios = ['transferencia', 'cheque', 'tarjeta'];
            $cuentaBancariaId = $data['cuenta_bancaria_id'] ?? null;

            if (in_array(strtolower($metodoPagoString), $metodosBancarios) && empty($cuentaBancariaId)) {
                throw new \Exception('El campo cuenta bancaria es obligatorio cuando el método de pago es ' . $metodoPagoString);
            }

            // 1. Update Venta basic payment info
            $venta->update([
                'fecha_pago' => now(),
                'pagado_por' => Auth::id(),
                'metodo_pago' => $metodoPagoString,
                'cuenta_bancaria_id' => $cuentaBancariaId,
                'notas_pago' => $data['notas_pago'] ?? null,
                'pagado' => true, // Assuming this marks it as fully paid
            ]);

            // 2. Process financial payment via PaymentService
            if ($venta->cuentaPorCobrar) {
                $this->paymentService->registrarPago(
                    $venta->cuentaPorCobrar,
                    (float) $venta->total,
                    $metodoPagoString,
                    $data['notas_pago'] ?? 'Pago registrado manualmente',
                    Auth::id(),
                    $cuentaBancariaId
                );
            } else {
                // Rare case: sale without CxC (possibly old migration)
                Log::warning("Venta #{$venta->id} marked as paid without CxC. Creating automated EntregaDinero.");
                EntregaDineroService::crearAutoPorMetodo(
                    'venta',
                    $venta->id,
                    (float) $venta->total,
                    $metodoPagoString,
                    now()->format('Y-m-d'),
                    Auth::id(),
                    $data['notas_pago'] ?? 'Pago directo (sin CxC)',
                    null,
                    $cuentaBancariaId
                );
            }

            // 3. Audit log
            VentaAuditLog::logAction(
                $venta->id,
                'paid',
                $venta->getOriginal('estado'),
                $venta->estado,
                [
                    'monto' => $venta->total,
                    'metodo_pago' => $metodoPagoString,
                    'cuenta_bancaria_id' => $cuentaBancariaId
                ],
                'Venta marked as paid via VentaPaymentService'
            );

            return $venta->fresh();
        });
    }
}
