<?php

namespace App\Services\Compras;

use App\Models\Compra;
use App\Models\CuentasPorPagar;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestión de cuentas por pagar de compras
 */
class CompraCuentasPagarService
{
    /**
     * Crear cuenta por pagar para una compra
     * 
     * @param Compra $compra
     * @param float $total
     * @param \DateTime|string|null $fechaBase Fecha base para calcular vencimiento (ej: fecha del CFDI). Si es null, usa fecha actual.
     */
    public function crearCuentaPorPagar(Compra $compra, float $total, $fechaBase = null): CuentasPorPagar
    {
        // Si viene fecha del CFDI, usarla; si no, usar fecha actual
        $fechaInicio = $fechaBase ? \Carbon\Carbon::parse($fechaBase) : now();
        $fechaVencimiento = $fechaInicio->copy()->addDays(30);

        $cuenta = CuentasPorPagar::create([
            'compra_id' => $compra->id,
            'monto_total' => $total,
            'monto_pagado' => 0,
            'monto_pendiente' => $total,
            'fecha_vencimiento' => $fechaVencimiento,
            'estado' => 'pendiente',
            'notas' => $fechaBase ? 'Cuenta creada desde importación CFDI' : 'Cuenta creada por nueva compra',
        ]);

        Log::info('Cuenta por pagar creada', [
            'cuenta_id' => $cuenta->id,
            'compra_id' => $compra->id,
            'monto_total' => $total,
            'fecha_vencimiento' => $fechaVencimiento->toDateString(),
        ]);

        return $cuenta;
    }

    /**
     * Actualizar cuenta por pagar
     */
    public function actualizarCuentaPorPagar(Compra $compra, float $nuevoTotal): void
    {
        $cuentaPorPagar = CuentasPorPagar::where('compra_id', $compra->id)->first();
        
        if ($cuentaPorPagar) {
            $montoPagado = $cuentaPorPagar->monto_pagado;
            // ✅ FIX: Proteger contra valores negativos
            $nuevoPendiente = max(0, $nuevoTotal - $montoPagado);

            // ✅ FIX: Usar 'pagado' en vez de 'pagada' para consistencia con el modelo
            $cuentaPorPagar->update([
                'monto_total' => $nuevoTotal,
                'monto_pendiente' => $nuevoPendiente,
                'estado' => $nuevoPendiente <= 0 ? 'pagado' : ($montoPagado > 0 ? 'parcial' : 'pendiente'),
            ]);

            Log::info('Cuenta por pagar actualizada', [
                'cuenta_id' => $cuentaPorPagar->id,
                'monto_total' => $nuevoTotal,
                'monto_pendiente' => $nuevoPendiente
            ]);
        } else {
            // Crear si no existe
            CuentasPorPagar::create([
                'compra_id' => $compra->id,
                'monto_total' => $nuevoTotal,
                'monto_pagado' => 0,
                'monto_pendiente' => $nuevoTotal,
                'fecha_vencimiento' => now()->addDays(30),
                'estado' => 'pendiente',
                'notas' => 'Cuenta regenerada por edición de compra',
            ]);

            Log::info('Cuenta por pagar creada', ['monto_total' => $nuevoTotal]);
        }
    }

    /**
     * Cancelar cuenta por pagar asociada a una compra
     */
    public function cancelarCuentaPorPagar(Compra $compra): void
    {
        $cuentaPorPagar = CuentasPorPagar::where('compra_id', $compra->id)->first();
        
        if (!$cuentaPorPagar) {
            return;
        }

        if ($cuentaPorPagar->monto_pagado > 0) {
            // Si hay pagos parciales, marcar como cancelada pero mantener registro
            $cuentaPorPagar->update([
                'estado' => 'cancelada',
                'monto_pendiente' => 0,
                'notas' => ($cuentaPorPagar->notas ? $cuentaPorPagar->notas . " | " : "") . 'Cancelada por cancelación de compra'
            ]);
            
            Log::info('Cuenta por pagar marcada como cancelada', [
                'cuenta_id' => $cuentaPorPagar->id,
                'monto_pagado' => $cuentaPorPagar->monto_pagado
            ]);
        } else {
            // Si no hay pagos, eliminar el registro
            $cuentaPorPagar->delete();
            
            Log::info('Cuenta por pagar eliminada', ['compra_id' => $compra->id]);
        }
    }
}
