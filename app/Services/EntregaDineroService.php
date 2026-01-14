<?php

namespace App\Services;

use App\Models\EntregaDinero;
use Carbon\Carbon;

class EntregaDineroService
{
    /**
     * Decide estado por método según configuración.
     */
    public static function estadoPorMetodo(string $metodoPago): string
    {
        $auto = config('entregas.auto_recibido_metodos', ['transferencia']);
        return in_array($metodoPago, $auto, true) ? 'recibido' : 'pendiente';
    }

    /**
     * Crear entrega aplicando política de estado por método (parametrizable).
     */
    public static function crearAutoPorMetodo(
        string $tipoOrigen,
        int $idOrigen,
        float $monto,
        string $metodoPago,
        string $fechaEntregaYmd,
        int $userId,
        ?string $notas = null,
        ?int $recibidoPor = null,
        ?int $cuentaBancariaId = null // ✅ Nuevo parámetro opcional
    ): EntregaDinero {
        $estado = self::estadoPorMetodo($metodoPago);
        return self::crearDesdeOrigen(
            $tipoOrigen,
            $idOrigen,
            $monto,
            $metodoPago,
            $fechaEntregaYmd,
            $userId,
            $estado,
            $recibidoPor,
            $notas,
            $cuentaBancariaId // ✅ Pasar el ID
        );
    }

    /**
     * Crear una Entrega de Dinero desde un registro de origen unificado.
     * - Mapea el método de pago a montos (efectivo/cheques/tarjetas).
     * - Permite crear en estado 'pendiente' o 'recibido'.
     * - ✅ Si se especifica cuentaBancariaId y está recibido, crea movimiento bancario.
     * - ✅ PROTECCIÓN ANTI-DUPLICADOS: Verifica si ya existe una entrega para el mismo origen.
     */
    public static function crearDesdeOrigen(
        string $tipoOrigen,
        int $idOrigen,
        float $monto,
        string $metodoPago,
        string $fechaEntregaYmd,
        int $userId,
        string $estado = 'pendiente',
        ?int $recibidoPor = null,
        ?string $notas = null,
        ?int $cuentaBancariaId = null // ✅ Nuevo parámetro
    ): EntregaDinero {
        // ✅ PROTECCIÓN ANTI-DUPLICADOS: Verificar si ya existe entrega para este origen
        $entregaExistente = EntregaDinero::where('tipo_origen', $tipoOrigen)
            ->where('id_origen', $idOrigen)
            ->whereIn('estado', ['pendiente', 'recibido'])
            ->first();

        if ($entregaExistente) {
            // Si ya existe una entrega, retornarla en lugar de crear duplicado
            \Log::warning("EntregaDineroService: Evitando duplicado para {$tipoOrigen} #{$idOrigen}. Entrega existente ID: {$entregaExistente->id}");
            return $entregaExistente;
        }

        $montoEfectivo = 0.0;
        $montoTransferencia = 0.0;
        $montoCheques = 0.0;
        $montoTarjetas = 0.0;
        $montoOtros = 0.0;

        switch ($metodoPago) {
            case 'efectivo':
                $montoEfectivo = $monto;
                break;
            case 'transferencia':
                $montoTransferencia = $monto;
                break;
            case 'cheque':
                $montoCheques = $monto;
                break;
            case 'tarjeta':
            case 'tarjeta_credito':
            case 'tarjeta_debito':
                $montoTarjetas = $monto;
                break;
            case 'otros':
                $montoOtros = $monto;
                break;
            default:
                $montoEfectivo = $monto;
                break;
        }

        $data = [
            'user_id' => $userId,
            'fecha_entrega' => Carbon::parse($fechaEntregaYmd)->format('Y-m-d'),
            'monto_efectivo' => $montoEfectivo,
            'monto_transferencia' => $montoTransferencia,
            'monto_cheques' => $montoCheques,
            'monto_tarjetas' => $montoTarjetas,
            'monto_otros' => $montoOtros,
            'total' => $monto,
            'estado' => $estado,
            'notas' => $notas,
            'tipo_origen' => $tipoOrigen,
            'id_origen' => $idOrigen,
            'cuenta_bancaria_id' => $cuentaBancariaId, // ✅ Guardar relación
        ];

        if ($estado === 'recibido') {
            $data['recibido_por'] = $recibidoPor ?: $userId;
            $data['fecha_recibido'] = Carbon::now();
        }

        $entrega = EntregaDinero::create($data);

        // ✅ Lógica de Integridad Financiera: Registrar en Banco si aplica
        if ($estado === 'recibido') {
            if ($cuentaBancariaId) {
                $cuenta = \App\Models\CuentaBancaria::find($cuentaBancariaId);
                if ($cuenta) {
                    // ✅ FIX: Usar Folio de Venta (numero_venta) en lugar de ID interno
                    $referencia = "#{$idOrigen}";
                    if ($tipoOrigen === 'venta') {
                        $venta = \App\Models\Venta::find($idOrigen);
                        if ($venta && $venta->numero_venta) {
                            $referencia = $venta->numero_venta;
                        }
                    }

                    $cuenta->registrarMovimiento(
                        'deposito',
                        $monto,
                        "Cobro por {$tipoOrigen} {$referencia} ({$metodoPago})"
                    );
                }
            } elseif (in_array($metodoPago, ['transferencia', 'tarjeta', 'cheque'])) {
                // ⚠️ CRITICAL ALERT: Received payment via bank method but no bank account ID provided
                \Log::error("EntregaDineroService: Pago RECIBIDO via {$metodoPago} sin cuentaBancariaId para {$tipoOrigen} #{$idOrigen}. El saldo bancario NO se actualizó.");
            }
        }

        return $entrega;
    }
}
