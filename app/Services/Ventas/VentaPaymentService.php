<?php

namespace App\Services\Ventas;

use App\Models\User;
use App\Models\Venta;
use App\Models\VentaAuditLog;
use App\Services\PaymentService;
use App\Services\EntregaDineroService;
use App\Support\EmpresaResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
            $data['notas_pago'] = $data['notas_pago'] ?? $data['notas'] ?? null;

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

            $pagadoPorId = $this->resolvePagadoPorUserId($venta, $data);

            // 1. Process financial payment via PaymentService
            if ($venta->cuentaPorCobrar) {
                $montoAPagar = round($venta->cuentaPorCobrar->calcularPendiente(), 2);
                
                if ($montoAPagar > 0) {
                    $this->paymentService->registrarPago(
                        $venta->cuentaPorCobrar,
                        $montoAPagar,
                        $metodoPagoString,
                        $data['notas_pago'] ?? 'Pago registrado manualmente',
                        $pagadoPorId,
                        $cuentaBancariaId
                    );
                }
                
                // Refresh CxC to get accurate pending amount
                $venta->cuentaPorCobrar->refresh();
            } else {
                // Rare case: sale without CxC (possibly old migration)
                Log::warning("Venta #{$venta->id} marked as paid without CxC. Creating automated EntregaDinero.");
                EntregaDineroService::crearAutoPorMetodo(
                    'venta',
                    $venta->id,
                    (float) $venta->total,
                    $metodoPagoString,
                    now()->format('Y-m-d'),
                    $pagadoPorId,
                    $data['notas_pago'] ?? 'Pago directo (sin CxC)',
                    null,
                    $cuentaBancariaId
                );
            }

            // 2. Update Venta basic payment info
            // ✅ FIX (A-01): Determine 'pagado' based on actual pending balance
            $isFullyPaid = $venta->cuentaPorCobrar ? $venta->cuentaPorCobrar->calcularPendiente() <= 0.01 : true;

            Venta::withoutEvents(function () use ($venta, $pagadoPorId, $metodoPagoString, $cuentaBancariaId, $data, $isFullyPaid) {
                $venta->update([
                    'fecha_pago' => now(),
                    'pagado_por' => $pagadoPorId,
                    'metodo_pago' => $metodoPagoString,
                    'cuenta_bancaria_id' => $cuentaBancariaId,
                    'notas_pago' => $data['notas_pago'] ?? null,
                    'pagado' => $isFullyPaid,
                ]);
            });

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

    /**
     * Quién figurará como receptor del cobro (corte / entregas). Por defecto el usuario autenticado.
     */
    private function resolvePagadoPorUserId(Venta $venta, array $data): int
    {
        $authId = (int) Auth::id();
        if (empty($data['pagado_por_user_id'])) {
            return $authId;
        }

        $uid = (int) $data['pagado_por_user_id'];
        $empresaId = (int) ($venta->empresa_id ?? EmpresaResolver::resolveId() ?? 0);
        $user = User::withoutGlobalScopes()->find($uid);
        if (! $user || (int) ($user->empresa_id ?? 0) !== $empresaId) {
            throw ValidationException::withMessages([
                'pagado_por_user_id' => ['El usuario seleccionado no puede registrar este cobro para esta empresa.'],
            ]);
        }

        return $uid;
    }
}
