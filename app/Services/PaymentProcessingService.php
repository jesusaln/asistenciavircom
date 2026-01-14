<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\CuentasPorPagar;
use App\Models\Cfdi;
use App\Models\CuentasPorCobrar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentProcessingService
{
    /**
     * Procesar contenido XML de pago y buscar compras relacionadas.
     */
    public function processPaymentXmlContent(string $xmlContent)
    {
        try {
            $paymentData = $this->parsePaymentXml($xmlContent);

            if (!$paymentData['success']) {
                return [
                    'success' => false,
                    'message' => $paymentData['message'] ?? 'Error al parsear el XML de pago',
                ];
            }

            // Buscar compras relacionadas por UUID
            $matches = [];
            $montoTotalXml = $paymentData['monto_total'];
            $montoAplicadoSugerido = 0;
            $proveedorId = null;

            foreach ($paymentData['documentos_relacionados'] as $docRel) {
                $compra = null;
                $cuenta = null;
                $docUuid = strtoupper($docRel['uuid']); // Normalizar a mayúsculas

                // Primero buscar en CFDIs table (facturas recibidas importadas)
                $cfdi = Cfdi::where('uuid', 'ilike', $docUuid)
                    ->orWhere('uuid', 'ilike', strtolower($docUuid))
                    ->first();

                if ($cfdi && $cfdi->compra) {
                    $compra = $cfdi->compra;
                }

                // Si no encontró, buscar directamente en Compras por cfdi_uuid
                if (!$compra) {
                    $compra = Compra::where('cfdi_uuid', 'ilike', $docUuid)
                        ->orWhere('cfdi_uuid', 'ilike', strtolower($docUuid))
                        ->first();
                }

                if ($compra) {
                    // Buscar la cuenta por pagar asociada
                    $cuenta = CuentasPorPagar::where('compra_id', $compra->id)
                        ->with('compra.proveedor')
                        ->first();

                    if ($cuenta) {
                        $proveedorId = $cuenta->proveedor_id;
                    }
                }

                $montoDocumentoXml = (float) $docRel['imp_pagado'];
                $montoSugerido = $cuenta ? min($montoDocumentoXml, (float) $cuenta->monto_pendiente) : 0;
                $montoAplicadoSugerido += $montoSugerido;

                $matches[] = [
                    'uuid' => $docRel['uuid'],
                    'serie_folio' => ($docRel['serie'] ?? '') . ($docRel['folio'] ?? ''),
                    'imp_saldo_ant' => $docRel['imp_saldo_ant'],
                    'imp_pagado' => $montoSugerido, // Sugerimos lo que se puede pagar
                    'imp_pagado_xml' => $montoDocumentoXml,
                    'imp_saldo_insoluto' => $docRel['imp_saldo_insoluto'],
                    'found' => $cuenta !== null,
                    'cuenta_id' => $cuenta?->id,
                    'cuenta_estado' => $cuenta?->estado,
                    'monto_pendiente' => $cuenta?->monto_pendiente,
                    'proveedor_nombre' => $cuenta?->compra?->proveedor?->nombre_razon_social ?? ($compra?->proveedor?->nombre_razon_social ?? 'N/A'),
                    'numero_compra' => $compra?->numero_compra ?? 'N/A',
                ];
            }

            $excedente = $montoTotalXml - $montoAplicadoSugerido;
            $otrasCuentas = [];

            if ($proveedorId) {
                // Buscar otras cuentas pendientes del mismo proveedor que no estén en los matches
                $matchedIds = collect($matches)->pluck('cuenta_id')->filter()->toArray();
                $otrasCuentas = CuentasPorPagar::where('proveedor_id', $proveedorId)
                    ->whereNotIn('id', $matchedIds)
                    ->whereIn('estado', ['pendiente', 'parcial', 'vencido'])
                    ->with('compra')
                    ->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'numero_compra' => $c->compra?->numero_compra ?? "ID-{$c->id}",
                        'monto_total' => $c->monto_total,
                        'monto_pendiente' => $c->monto_pendiente,
                        'fecha_vencimiento' => $c->fecha_vencimiento?->format('d/m/Y'),
                    ]);
            }

            return [
                'success' => true,
                'payment_info' => [
                    'uuid' => $paymentData['uuid'],
                    'fecha_pago' => $paymentData['fecha_pago'],
                    'forma_pago' => $paymentData['forma_pago'],
                    'monto_total' => $paymentData['monto_total'],
                    'moneda' => $paymentData['moneda'],
                    'emisor' => $paymentData['emisor'],
                    'receptor' => $paymentData['receptor'],
                ],
                'metodo_pago_sistema' => $paymentData['metodo_pago_sistema'],
                'matches' => $matches,
                'otras_cuentas' => $otrasCuentas,
                'excedente' => $excedente >= 1.00 ? $excedente : 0,
                'total_documentos' => count($matches),
                'documentos_encontrados' => count(array_filter($matches, fn($m) => $m['found'])),
            ];

        } catch (\Exception $e) {
            Log::error('Error procesando XML de pago CXP', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al procesar el XML: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Intentar aplicar pagos automáticamente basado en el análisis
     */
    public function processAndApplyAuto(string $xmlContent)
    {
        $analysis = $this->processPaymentXmlContent($xmlContent);

        if (!$analysis['success']) {
            return $analysis;
        }

        $appliedCount = 0;
        $errors = [];

        foreach ($analysis['matches'] as $match) {
            // Permitir si hay monto a pagar O si la cuenta ya está pagada (para actualizar flag REP)
            $isPaid = ($match['cuenta_estado'] ?? '') === 'pagado';

            if ($match['found'] && $match['cuenta_id'] && ($match['imp_pagado'] > 0 || $isPaid)) {
                try {
                    $this->applySinglePayment(
                        $match['cuenta_id'],
                        $match['imp_pagado'] > 0 ? $match['imp_pagado'] : 0.01, // Hack mínimo para entrar, o manejamos 0
                        $analysis['metodo_pago_sistema'],
                        [
                            'notas' => "Vinculación automática XML REP: " . ($analysis['payment_info']['uuid'] ?? ''),
                            'fecha_pago' => $analysis['payment_info']['fecha_pago'] ?? now(),
                            'pagado_con_rep' => true
                        ]
                    );
                    $appliedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error en cuenta {$match['cuenta_id']}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => true,
            'applied_count' => $appliedCount,
            'errors' => $errors,
            'analysis' => $analysis
        ];
    }


    /**
     * Aplicar un solo pago a una cuenta
     */
    public function applySinglePayment($cuentaId, $monto, $metodoPago, $options = [])
    {
        return DB::transaction(function () use ($cuentaId, $monto, $metodoPago, $options) {
            $cuenta = CuentasPorPagar::lockForUpdate()->findOrFail($cuentaId);

            if (in_array($cuenta->estado, ['pagado', 'cancelada'])) {
                // Si ya está pagada, solo actualizamos el flag pagado_con_rep si es el caso
                if (($options['pagado_con_rep'] ?? false) && !$cuenta->pagado_con_rep) {
                    $cuenta->update(['pagado_con_rep' => true]);
                }
                return $cuenta;
            }

            $montoAplicar = min($monto, $cuenta->monto_pendiente);

            // Si el monto es 0 pero queremos marcar como REP en una cuenta ya pagada/saldada
            if ($montoAplicar <= 0) {
                // Si ya está saldada o es un update de flag
                if ($cuenta->monto_pendiente <= 0.01 || $cuenta->estado === 'pagado') {
                    // Update flag logic
                    if (($options['pagado_con_rep'] ?? false) && !$cuenta->pagado_con_rep) {
                        $cuenta->update(['pagado_con_rep' => true]);
                        // También asegurar que pue_pagado sea false si es REP
                        if (isset($options['pue_pagado']) && $options['pue_pagado'] === false) {
                            $cuenta->update(['pue_pagado' => false]);
                        }
                    }
                }
                return $cuenta;
            }

            // Registrar el pago
            $cuenta->registrarPago(
                $montoAplicar,
                $options['notas'] ?? 'Pago aplicado',
                $options['cuenta_bancaria_id'] ?? null,
                $options['pagado_con_rep'] ?? false
            );

            // Si se completa el pago, marcar como pagado
            if ($cuenta->fresh()->monto_pendiente <= 0) {
                $cuenta->marcarPagado(
                    $metodoPago,
                    $options['cuenta_bancaria_id'] ?? null,
                    $options['notas'] ?? 'Pago completo',
                    $options['pue_pagado'] ?? false,
                    $options['pagado_con_rep'] ?? ($options['pagado_con_rep'] ?? false)
                );
            }

            return $cuenta;
        });
    }

    private function parsePaymentXml(string $xmlContent): array
    {
        try {
            $parser = app(\App\Services\CfdiXmlParserService::class);
            $data = $parser->parseCfdiXml($xmlContent);

            if ($data['tipo_comprobante'] !== 'P') {
                return ['success' => false, 'message' => 'El XML no es un complemento de pago (tipo P)'];
            }

            if (empty($data['complementos']['pagos'])) {
                return ['success' => false, 'message' => 'No se encontraron complementos de pago en el XML'];
            }

            // Consolidar documentos de todos los nodos de pago
            $documentosRelacionados = [];
            $montoTotal = 0;
            $fechaPago = null;
            $formaPago = null;
            $moneda = 'MXN';

            foreach ($data['complementos']['pagos'] as $pago) {
                $montoTotal += ($pago['monto'] ?? 0);
                $fechaPago = $pago['fecha_pago'] ?? $fechaPago;
                $formaPago = $pago['forma_pago'] ?? $formaPago;
                $moneda = $pago['moneda'] ?? $moneda;

                if (!empty($pago['doctos_relacionados'])) {
                    foreach ($pago['doctos_relacionados'] as $doc) {
                        $documentosRelacionados[] = [
                            'uuid' => $doc['id_documento'],
                            'serie' => $doc['serie'] ?? '',
                            'folio' => $doc['folio'] ?? '',
                            'moneda' => $doc['moneda_dr'] ?? 'MXN',
                            'num_parcialidad' => $doc['num_parcialidad'] ?? 1,
                            'imp_saldo_ant' => $doc['imp_saldo_ant'] ?? 0,
                            'imp_pagado' => $doc['imp_pagado'] ?? 0,
                            'imp_saldo_insoluto' => $doc['imp_saldo_insoluto'] ?? 0,
                        ];
                    }
                }
            }

            if (empty($documentosRelacionados)) {
                return ['success' => false, 'message' => 'No se encontraron documentos relacionados en el XML de pago'];
            }

            return [
                'success' => true,
                'uuid' => $data['uuid'],
                'fecha_pago' => $fechaPago,
                'forma_pago' => $formaPago,
                'metodo_pago_sistema' => $this->mapSatFormaPagoToSistema($formaPago),
                'monto_total' => $montoTotal,
                'moneda' => $moneda,
                'emisor' => $data['emisor'],
                'receptor' => $data['receptor'],
                'documentos_relacionados' => $documentosRelacionados,
            ];

        } catch (\Exception $e) {
            Log::error('Error parseando XML de pago CXP', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error al parsear: ' . $e->getMessage()];
        }
    }

    /**
     * Map SAT payment method codes to system values
     */
    private function mapSatFormaPagoToSistema(?string $satCode): string
    {
        if (!$satCode)
            return 'otros';

        $mapping = [
            '01' => 'efectivo',
            '02' => 'cheque',
            '03' => 'transferencia',
            '04' => 'tarjeta', // Tarjeta de Crédito
            '28' => 'tarjeta', // Tarjeta de Débito
            '05' => 'tarjeta', // Monedero Electrónico
            '06' => 'tarjeta', // Dinero Electrónico
            '08' => 'otros',    // Vales de Despensa
            '12' => 'otros',    // Dación en pago
            '17' => 'otros',    // Compensación
            '30' => 'otros',    // Aplicación de anticipos
            '99' => 'otros',    // Por definir
        ];

        return $mapping[$satCode] ?? 'otros';
    }
}
