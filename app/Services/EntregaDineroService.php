<?php

namespace App\Services;

use App\Models\EntregaDinero;
use App\Models\User;
use App\Services\Cobros\MiCorteCobrosCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EntregaDineroService
{
    public const TIPO_DECLARACION_MI_CORTE = 'declaracion_mi_corte';

    /**
     * Calcular total y normalizar montos.
     */
    public static function normalizeMontos(array $data): array
    {
        $montoEfectivo = (float) ($data['monto_efectivo'] ?? 0);
        $montoTransferencia = (float) ($data['monto_transferencia'] ?? 0);
        $montoCheques = (float) ($data['monto_cheques'] ?? 0);
        $montoTarjetas = (float) ($data['monto_tarjetas'] ?? 0);

        $total = $montoEfectivo + $montoTransferencia + $montoCheques + $montoTarjetas;

        return [
            'monto_efectivo' => $montoEfectivo,
            'monto_transferencia' => $montoTransferencia,
            'monto_cheques' => $montoCheques,
            'monto_tarjetas' => $montoTarjetas,
            'total' => $total,
        ];
    }

    /**
     * Crear entrega manual.
     */
    public static function crearManual(array $data, int $userId): EntregaDinero
    {
        $montos = self::normalizeMontos($data);
        if ($montos['total'] <= 0) {
            throw new \InvalidArgumentException('El total debe ser mayor a cero');
        }

        return EntregaDinero::create([
            'user_id' => $userId,
            'fecha_entrega' => $data['fecha_entrega'],
            'monto_efectivo' => $montos['monto_efectivo'],
            'monto_transferencia' => $montos['monto_transferencia'],
            'monto_cheques' => $montos['monto_cheques'],
            'monto_tarjetas' => $montos['monto_tarjetas'],
            'total' => $montos['total'],
            'notas' => $data['notas'] ?? null,
        ]);
    }

    /**
     * Crear entrega desde corte (recibido inmediatamente).
     */
    public static function crearDesdeCorte(array $data, int $userId): EntregaDinero
    {
        return EntregaDinero::create([
            'user_id' => $userId,
            'fecha_entrega' => $data['fecha'],
            'monto_efectivo' => $data['monto'],
            'monto_cheques' => 0,
            'monto_tarjetas' => 0,
            'total' => $data['monto'],
            'notas' => $data['notas'] ?? null,
            'estado' => 'recibido',
            'recibido_por' => $userId,
            'fecha_recibido' => now(),
        ]);
    }

    /**
     * Actualizar entrega manual y sincronizar movimientos bancarios si aplica.
     */
    public static function actualizarManual(EntregaDinero $entrega, array $data): void
    {
        $montos = self::normalizeMontos($data);
        if ($montos['total'] <= 0) {
            throw new \InvalidArgumentException('El total debe ser mayor a cero');
        }

        $totalAnterior = $entrega->total;
        $diferencia = $montos['total'] - $totalAnterior;

        $entrega->update([
            'fecha_entrega' => $data['fecha_entrega'],
            'monto_efectivo' => $montos['monto_efectivo'],
            'monto_transferencia' => $montos['monto_transferencia'],
            'monto_cheques' => $montos['monto_cheques'],
            'monto_tarjetas' => $montos['monto_tarjetas'],
            'total' => $montos['total'],
            'notas' => $data['notas'] ?? null,
        ]);

        if ($entrega->estado === 'recibido' && $diferencia != 0) {
            $movimiento = \App\Models\MovimientoBancario::where('conciliable_type', \App\Models\EntregaDinero::class)
                ->where('conciliable_id', $entrega->id)
                ->first();

            if ($movimiento) {
                $movimiento->monto = $montos['total'];
                $movimiento->save();

                if ($movimiento->cuentaBancaria) {
                    $movimiento->cuentaBancaria->saldo_actual += $diferencia;
                    $movimiento->cuentaBancaria->save();
                }
            } elseif ($entrega->cuenta_bancaria_id) {
                $cuenta = \App\Models\CuentaBancaria::find($entrega->cuenta_bancaria_id);
                if ($cuenta) {
                    $cuenta->saldo_actual += $diferencia;
                    $cuenta->save();
                }
            }
        }
    }

    /**
     * Marcar entrega como recibida y, opcionalmente, crear movimiento bancario (depósito).
     * Si $registrarMovimientoBancario es false o no hay cuenta, solo queda la confirmación física (tesorería).
     */
    public static function marcarComoRecibido(EntregaDinero $entrega, int $userId, ?int $cuentaBancariaId, ?string $notas, bool $registrarMovimientoBancario = true, ?string $fechaHora = null): void
    {
        $fechaReal = $fechaHora ? \Carbon\Carbon::parse($fechaHora) : now();

        $entrega->update([
            'estado' => 'recibido',
            'recibido_por' => $userId,
            'fecha_recibido' => $fechaReal,
            'notas_recibido' => $notas,
            'cuenta_bancaria_id' => $cuentaBancariaId,
        ]);

        if (in_array($entrega->tipo_origen, ['lote', 'declaracion_mi_corte'], true)) {
            foreach ($entrega->children as $child) {
                // Deshabilitar movimiento bancario para hijos para evitar duplicados en el banco
                self::marcarComoRecibido($child, $userId, $cuentaBancariaId, "Recibido vía Lote #{$entrega->id}", false, $fechaHora);
            }
        }

        if (! $registrarMovimientoBancario || ! $cuentaBancariaId) {
            return;
        }

        $cuenta = \App\Models\CuentaBancaria::find($cuentaBancariaId);
        if ($cuenta) {
            $movimiento = $cuenta->registrarMovimiento(
                'deposito',
                $entrega->total,
                "Entrega de dinero #{$entrega->id} - " . ($notas ?? 'Sin notas'),
                'cobro',
                $entrega,
                $fechaHora
            );

            $movimiento->update([
                'conciliado_por' => $userId,
                'conciliado_at' => $fechaReal,
            ]);
        }
    }

    /**
     * Declaración desde la app «Mi corte»: registro pendiente de efectivo que un tesorero debe confirmar.
     *
     * @throws \InvalidArgumentException
     */
    public static function declararEntregaMiCortePendiente(
        int $userId,
        string $fechaDesdeYmd,
        string $fechaHastaYmd,
        float $montoEfectivo,
        ?string $notasUsuario = null
    ): EntregaDinero {
        $tz = config('app.timezone');
        $desde = Carbon::createFromFormat('Y-m-d', $fechaDesdeYmd, $tz)->startOfDay();
        $hasta = Carbon::createFromFormat('Y-m-d', $fechaHastaYmd, $tz)->endOfDay();

        $calc = app(MiCorteCobrosCalculator::class);
        $resumen = $calc->resumenParaUsuario($userId, $desde, $hasta);
        $maxEfectivo = (float) ($resumen['efectivo_a_entregar'] ?? 0);

        if ($montoEfectivo <= 0) {
            throw new \InvalidArgumentException('El monto a declarar debe ser mayor a cero.');
        }

        $tag = 'PERIODO:'.$fechaDesdeYmd.'|'.$fechaHastaYmd;
        $yaDeclarado = (float) EntregaDinero::query()
            ->where('user_id', $userId)
            ->where('tipo_origen', self::TIPO_DECLARACION_MI_CORTE)
            ->where('notas', 'like', '%'.$tag.'%')
            ->whereIn('estado', ['pendiente', 'recibido'])
            ->sum('total');

        $disponible = round($maxEfectivo - $yaDeclarado, 2);
        if ($montoEfectivo > $disponible + 0.02) {
            throw new \InvalidArgumentException(
                'El monto declarado ($'.number_format($montoEfectivo, 2).') supera el efectivo disponible del periodo ($'.number_format($disponible, 2).').'
            );
        }

        $bloqueNotas = $tag."\n".($notasUsuario ? trim($notasUsuario)."\n" : '');
        $empresaId = User::find($userId)->empresa_id;

        return DB::transaction(function () use ($userId, $hasta, $montoEfectivo, $bloqueNotas, $resumen, $empresaId) {
            // 1. Crear el registro Maestro (La Declaración del Usuario)
            $lote = EntregaDinero::create([
                'user_id' => $userId,
                'fecha_entrega' => $hasta->toDateString(),
                'monto_efectivo' => $montoEfectivo,
                'monto_transferencia' => 0,
                'monto_cheques' => 0,
                'monto_tarjetas' => 0,
                'monto_otros' => 0,
                'total' => $montoEfectivo,
                'estado' => 'pendiente',
                'notas' => 'Declaración app Mi corte'."\n".$bloqueNotas,
                'tipo_origen' => self::TIPO_DECLARACION_MI_CORTE,
                'id_origen' => null,
                'empresa_id' => $empresaId,
            ]);

            // 2. Vincular todas las VENTAS que entraron en este cálculo
            if (isset($resumen['ventas'])) {
                foreach ($resumen['ventas'] as $v) {
                    // Solo si es efectivo (que es lo que se está entregando)
                    if ($v->metodo_pago === 'efectivo' || $v->metodo_pago === 'Efectivo') {
                        EntregaDinero::create([
                            'user_id' => $userId,
                            'parent_id' => $lote->id,
                            'fecha_entrega' => $hasta->toDateString(),
                            'monto_efectivo' => $v->total,
                            'total' => $v->total,
                            'estado' => 'pendiente',
                            'tipo_origen' => 'venta',
                            'id_origen' => $v->id,
                            'empresa_id' => $empresaId,
                        ]);
                    }
                }
            }

            // 3. Vincular todos los GASTOS que entraron en este cálculo
            if (isset($resumen['gastos_detalle'])) {
                foreach ($resumen['gastos_detalle'] as $g) {
                    EntregaDinero::create([
                        'user_id' => $userId,
                        'parent_id' => $lote->id,
                        'fecha_entrega' => $hasta->toDateString(),
                        'monto_efectivo' => -$g->total, // Los gastos restan
                        'total' => -$g->total,
                        'estado' => 'pendiente',
                        'tipo_origen' => 'gasto',
                        'id_origen' => $g->id,
                        'empresa_id' => $empresaId,
                    ]);
                }
            }

            return $lote;
        });
    }
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
    ): ?EntregaDinero {
        $estado = self::estadoPorMetodo($metodoPago);

        // ✅ Si es efectivo y no se dirige a una cuenta bancaria específica (ej. depósito directo),
        // NO creamos el registro de entrega automáticamente. Esto obliga a usar el flujo de "Mi Corte" (Lote)
        // para dar trazabilidad al dinero físico que el técnico trae en mano.
        if (strtolower($metodoPago) === 'efectivo' && !$cuentaBancariaId) {
            return null;
        }

        // ✅ Si se dirige explícitamente a un banco (ej. depósito en efectivo), se considera ingresado/recibido inmediatamente
        if ($cuentaBancariaId) {
            $estado = 'recibido';
        }

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

        $metodoLower = strtolower($metodoPago);

        switch ($metodoLower) {
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
            case 'tarjeta de crédito':
            case 'tarjeta de débito':
                $montoTarjetas = $monto;
                break;
            case 'otros':
            case 'otro':
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
                        "Cobro por {$tipoOrigen} {$referencia} ({$metodoPago})",
                        'cobro',
                        $entrega
                    );
                }
            } elseif (in_array($metodoLower, ['transferencia', 'tarjeta', 'cheque', 'tarjeta_credito', 'tarjeta_debito', 'tarjeta de crédito', 'tarjeta de débito'])) {
                // ⚠️ CRITICAL ALERT: Received payment via bank method but no bank account ID provided
                \Log::error("EntregaDineroService: Pago RECIBIDO via {$metodoPago} sin cuentaBancariaId para {$tipoOrigen} #{$idOrigen}. El saldo bancario NO se actualizó.");
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cuenta_bancaria_id' => "Se requiere seleccionar una cuenta bancaria para registrar un pago electrónico ({$metodoPago})."
                ]);
            }
        }

        return $entrega;
    }
}
