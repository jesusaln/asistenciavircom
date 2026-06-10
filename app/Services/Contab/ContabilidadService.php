<?php

namespace App\Services\Contab;

use App\Models\Contab\AsientoContable;
use App\Models\Contab\CuentaContable;
use App\Models\Contab\PolizaContable;
use App\Models\Contab\RfcMapping;
use App\Models\Empresa;
use App\Services\CfdiXmlParserService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContabilidadService
{
    protected CfdiXmlParserService $parser;
    protected \App\Services\SatConsultaDirectaService $satService;
    protected static array $detalleCache = [];

    public function __construct(
        CfdiXmlParserService $parser,
        \App\Services\SatConsultaDirectaService $satService
    ) {
        $this->parser = $parser;
        $this->satService = $satService;
    }

    /**
     * Calcula la diferencia en días hábiles entre dos fechas
     */
    public function diasHabilesDiferencia(string $fechaInicio, string $fechaFin): int
    {
        $inicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
        $fin = \Carbon\Carbon::parse($fechaFin)->startOfDay();
        
        if ($inicio->gt($fin)) return 0;
        
        return (int) $inicio->diffInDaysFiltered(function(\Carbon\Carbon $date) {
            return !$date->isWeekend();
        }, $fin);
    }

    /**
     * Verifica si un REP es extemporáneo (más de 5 días hábiles)
     */
    public function verificarExtemporaneo(array $data): ?string
    {
        if (($data['tipo_comprobante'] ?? '') !== 'P' || empty($data['complementos']['pagos'])) {
            return null;
        }

        $fechaEmision = substr($data['fecha'] ?? '', 0, 10);
        $alertas = [];

        foreach ($data['complementos']['pagos'] as $p) {
            $fechaPago = substr($p['fecha_pago'] ?? '', 0, 10);
            if ($fechaPago) {
                $dias = $this->diasHabilesDiferencia($fechaPago, $fechaEmision);
                if ($dias > 5) {
                    $alertas[] = "Pago del {$fechaPago} timbrado {$dias} días hábiles después";
                }
            }
        }

        return !empty($alertas) ? "[EXTEMPORÁNEO] " . implode(', ', $alertas) : null;
    }

    public function generarPolizaDesdeXml(string $xmlContent, int $empresaId, int $userId, ?int $bancoId = null, ?string $clasificacion = null): PolizaContable
    {
        $data = $this->parser->parseCfdiXml($xmlContent);
        
        // Evitar duplicados por UUID
        $existe = PolizaContable::where('empresa_id', $empresaId)
            ->where(function($q) use ($data) {
                $q->where('cfdi_uuid', $data['uuid'])
                  ->orWhereJsonContains('cfdi_uuids', $data['uuid']);
            })
            ->first();

        if ($existe) {
            // Si es una póliza de Pago (REP), podemos permitir varias
            $esSoloEnPago = ($data['tipo_comprobante'] ?? '') === 'P' 
                && ($existe->tipo === 'ingreso' || $existe->tipo === 'egreso')
                && in_array($data['tipo_comprobante'] ?? '', ['I', 'E', 'N']);
            if (!$esSoloEnPago) {
                throw new Exception("Ya existe una póliza registrada con el UUID: {$data['uuid']} (Póliza #{$existe->numero})");
            }
        }

        return DB::transaction(function () use ($data, $empresaId, $userId, $bancoId, $xmlContent, $clasificacion) {
            $empresa = Empresa::findOrFail($empresaId);
            $miRfc = strtoupper($empresa->rfc);
            
            $emisorRfc = strtoupper($data['emisor']['rfc'] ?? '');
            $receptorRfc = strtoupper($data['receptor']['rfc'] ?? '');
            
            $esVenta = ($emisorRfc === $miRfc);
            $esCompra = ($receptorRfc === $miRfc);
            
            if (!$esVenta && !$esCompra) {
                throw new Exception("El RFC del emisor o receptor no coincide con el de la empresa ({$miRfc}).");
            }
            
            // 1. Crear Cabecera de Póliza
            $esPago = ($data['tipo_comprobante'] ?? '') === 'P';
            $metodoPago = $data['metodo_pago'] ?? ($esPago ? 'PAGO' : 'PUE');
            $esNomina = ($data['tipo_comprobante'] ?? '') === 'N';
            $tipo = $esVenta ? 'ingreso' : 'egreso';
            if ($esNomina) $tipo = 'egreso';
            if ($metodoPago === 'PPD' && !$bancoId) {
                $tipo = 'diario';
            }

            // Si hay bancoId, forzamos a usar la cuenta bancaria (no PPD), pero respetamos ingreso/egreso
            if ($bancoId && $metodoPago === 'PPD') {
                $tipo = $esVenta ? 'ingreso' : 'egreso';
            }
            
            if ($esPago) {
                $modoPago = ' [REP]';
            } else {
                $modoPago = ($metodoPago === 'PPD') ? ' [PPD]' : ' [PUE]';
            }
            $periodoIndividual = '';
            if (!empty($data['complementos']['nomina'])) {
                $nom = $data['complementos']['nomina'];
                if (!empty($nom['fecha_inicial_pago']) && !empty($nom['fecha_final_pago'])) {
                    $periodoIndividual = " Período: " . \Carbon\Carbon::parse($nom['fecha_inicial_pago'])->format('d/m') . ' al ' . \Carbon\Carbon::parse($nom['fecha_final_pago'])->format('d/m/Y');
                }
            }

            $label = $esVenta ? 'Venta' : 'Compra/Gasto';
            if ($esNomina) $label = 'Nómina';
            if ($data['tipo_comprobante'] === 'P') {
                $label = 'Pago';
                // Recalcular total real desde el complemento
                $totalP = 0;
                if (!empty($data['complementos']['pagos'])) {
                    foreach ($data['complementos']['pagos'] as $p) $totalP += (float)($p['monto'] ?? 0);
                }
                $data['total'] = $totalP;
            }

            $fechaPoliza = $data['fecha'] ? substr($data['fecha'], 0, 10) : now()->toDateString();
            if ($data['tipo_comprobante'] === 'P' && !empty($data['complementos']['pagos'])) {
                // Usar la fecha del pago para la póliza (Flujo de Efectivo)
                $fechaPoliza = substr($data['complementos']['pagos'][0]['fecha_pago'] ?? $fechaPoliza, 0, 10);
            }

            $alertaExt = $this->verificarExtemporaneo($data);
            $conceptoPrefix = $alertaExt ? $alertaExt . " - " : "";

            $poliza = PolizaContable::create([
                'empresa_id' => $empresaId,
                'tipo' => $tipo,
                'fecha' => $fechaPoliza,
                'numero' => $this->generarSiguienteNumero($empresaId, $tipo, substr($fechaPoliza, 0, 4)),
                'concepto' => $conceptoPrefix . $label . $modoPago . " - " . ($esVenta ? $data['receptor']['nombre'] : $data['emisor']['nombre']) . $periodoIndividual,
                'cfdi_uuid' => $data['uuid'],
                'cfdi_uuids' => [$data['uuid']],
                'total' => $data['total'] ?: 0, // Inicia en 0 si es necesario
                'estado' => 'borrador',
                'created_by' => $userId,
                'xml_content' => $xmlContent,
            ]);
            
            // 2. Generar Asientos (Partidas)
            $this->generarAsientosDesdeCfdi($poliza, $data, $esVenta, $bancoId, $esNomina, $clasificacion);

            // 3. Actualizar el total real basado en los asientos generados
            $totalReal = round((float) $poliza->asientos->sum('debe'), 2);
            if ($totalReal > 0) {
                $poliza->update(['total' => $totalReal]);
            }
            
            // 3. Conciliación automática (Marcar como PAGADO)
            if ($data['tipo_comprobante'] === 'P' || ($data['metodo_pago'] ?? 'PUE') === 'PUE') {
                if ($data['tipo_comprobante'] === 'P' && !empty($data['complementos']['pagos'])) {
                    // Si es un complemento de pago, marcar los documentos relacionados
                    foreach ($data['complementos']['pagos'] as $pago) {
                        if (!empty($pago['doctos_relacionados'])) {
                            foreach ($pago['doctos_relacionados'] as $doc) {
                                if (!empty($doc['id_documento'])) {
                                    \App\Models\Cfdi::where('uuid', $doc['id_documento'])->update(['estado' => 'pagado']);
                                }
                            }
                        }
                    }
                } else {
                    // Si es PUE o Nómina, marcarse a sí mismo como pagado
                    \App\Models\Cfdi::where('uuid', $data['uuid'])->update(['estado' => 'pagado']);
                }
            }

            return $poliza;
        });
    }

    /**
     * Lógica para generar los cargos y abonos
     */
    protected function generarAsientosDesdeCfdi(PolizaContable $poliza, array $data, bool $esVenta, ?int $bancoId = null, bool $esNomina = false, ?string $clasificacion = null): void
    {
        $empresaId = $poliza->empresa_id;
        $total = (float) $data['total'];
        $subtotal = (float) $data['subtotal'];
        $iva = (float) ($data['impuestos']['total_impuestos_trasladados'] ?? 0);
        $retenciones = (float) ($data['impuestos']['total_impuestos_retenidos'] ?? 0);
        $listaRetenciones = $data['impuestos']['retenciones'] ?? [];
        $map = $this->getAccountMap();
        $esEgreso = ($data['tipo_comprobante'] === 'E');
        
        if ($esNomina) {
            $cuentaSueldos = $this->obtenerCuentaGenerica($empresaId, '601.01', 'Sueldos y Salarios Operativos');
            $poliza->asientos()->create(['cuenta_id' => $cuentaSueldos->id, 'debe' => $total, 'haber' => 0, 'referencia' => 'Nómina']);
            $cuentaNomina = $this->obtenerCuentaPorRfc($empresaId, $data['receptor']['rfc'] ?? 'XAXX010101000', 'Acreedores', $data['receptor']['nombre'] ?? '');
            $poliza->asientos()->create(['cuenta_id' => $cuentaNomina->id, 'debe' => 0, 'haber' => $total, 'referencia' => 'Nómina', 'auxiliar' => $data['receptor']['nombre'] ?? 'Empleado']);
            return;
        }

        if ($data['tipo_comprobante'] === 'P') {
            // Validar saldo suficiente en la cuenta del cliente/proveedor
            $rfcContraparte = $esVenta ? ($data['receptor']['rfc'] ?? '') : ($data['emisor']['rfc'] ?? '');
            $cuentaContraparte = $esVenta 
                ? $this->obtenerCuentaPorRfc($empresaId, $rfcContraparte, 'Clientes', $data['receptor']['nombre'] ?? '')
                : $this->obtenerCuentaPorRfc($empresaId, $rfcContraparte, 'Proveedores', $data['emisor']['nombre'] ?? '');
            
            // Calcular saldo actual de la cuenta
            $saldoActual = (float) AsientoContable::where('cuenta_id', $cuentaContraparte->id)
                ->whereHas('poliza', fn($q) => $q->where('estado', '!=', 'anulada'))
                ->selectRaw('COALESCE(SUM(debe), 0) - COALESCE(SUM(haber), 0) as saldo')
                ->value('saldo');

            $tipoCuenta = $esVenta ? 'Cliente' : 'Proveedor';
            $nombreCuenta = $esVenta ? ($data['receptor']['nombre'] ?? '') : ($data['emisor']['nombre'] ?? '');
            if ($total > $saldoActual) {
                \Illuminate\Support\Facades\Log::warning("Saldo insuficiente en {$tipoCuenta} {$nombreCuenta} ({$rfcContraparte}): saldo \$" . number_format($saldoActual, 2) . ", intentando " . ($esVenta ? 'cobrar' : 'pagar') . " \$" . number_format($total, 2) . ". La cuenta quedará en negativo.");
            }

            $cuentaBanco = $bancoId 
                ? \App\Models\CuentaBancaria::find($bancoId)
                : \App\Models\CuentaBancaria::where('empresa_id', $empresaId)->where('activa', true)->first();

            $cuentaBancoCont = $this->obtenerCuentaBancoContable($empresaId, $bancoId);

            if ($esVenta) {
                // Ingreso: Cargo Banco, Abono Cliente
                $poliza->asientos()->create(['cuenta_id' => $cuentaBancoCont->id, 'debe' => $total, 'haber' => 0, 'referencia' => 'Cobro Factura']);
                $poliza->asientos()->create(['cuenta_id' => $cuentaContraparte->id, 'debe' => 0, 'haber' => $total, 'referencia' => 'Abono Cliente']);
                if ($cuentaBanco && $bancoId) $cuentaBanco->registrarMovimiento('deposito', $total, "Cobro XML: " . $poliza->concepto, 'venta');
            } else {
                // Egreso: Cargo Proveedor, Abono Banco
                $poliza->asientos()->create(['cuenta_id' => $cuentaContraparte->id, 'debe' => $total, 'haber' => 0, 'referencia' => 'Pago Factura']);
                $poliza->asientos()->create(['cuenta_id' => $cuentaBancoCont->id, 'debe' => 0, 'haber' => $total, 'referencia' => 'Retiro Banco']);
                if ($cuentaBanco && $bancoId) $cuentaBanco->registrarMovimiento('retiro', $total, "Pago XML: " . $poliza->concepto, 'gasto');
            }

            // Marcar facturas relacionadas como pagadas
            if (!empty($data['doctos_relacionados_uuids'])) {
                foreach ($data['doctos_relacionados_uuids'] as $relUuid) {
                    $relUuid = strtolower($relUuid);
                    if ($esVenta) {
                        $venta = \App\Models\Venta::whereHas('cfdis', function($q) use ($relUuid) {
                            $q->where('uuid', $relUuid);
                        })->first();
                        if ($venta) {
                            $venta->update(['pagado' => true, 'fecha_pago' => now(), 'estado' => 'pagado']);
                            if ($venta->cuentaPorCobrar) $venta->cuentaPorCobrar->actualizarEstado();
                        }
                    } else {
                        $compra = \App\Models\Compra::whereHas('cfdi', function($q) use ($relUuid) {
                            $q->where('uuid', $relUuid);
                        })->first();
                        if ($compra) {
                            if ($compra->cuentasPorPagar) {
                                $compra->cuentasPorPagar->marcarPagado($data['metodo_pago'] ?? 'PUE', $bancoId, "Liquidación automática vía integración contable");
                            }
                        }
                    }
                }
            }
            // --- IVA e ISR Flip (For RESICO compliance) ---
            if (!empty($data['complementos']['pagos'])) {
                foreach ($data['complementos']['pagos'] as $pago) {
                    $impuestosP = $pago['impuestos_p'] ?? [];
                    
                    // Traslados (IVA)
                    foreach (($impuestosP['traslados'] ?? []) as $traslado) {
                        if (($traslado['impuesto'] ?? '') === '002' || ($traslado['impuesto'] ?? '') === 'IVA') {
                            $montoIva = (float)($traslado['importe'] ?? 0);
                            if ($montoIva > 0) {
                                if ($esVenta) {
                                    // Cobro: De Pendiente a Por Pagar (Cobrado)
                                    $poliza->asientos()->create(['cuenta_id' => $this->obtenerCuentaGenerica($empresaId, '210', 'IVA Pendiente de Cobro')->id, 'debe' => $montoIva, 'haber' => 0, 'referencia' => 'Ajuste IVA Cobrado']);
                                    $poliza->asientos()->create(['cuenta_id' => $this->obtenerCuentaGenerica($empresaId, $map['iva_trasladado'], 'IVA Trasladado Cobrado')->id, 'debe' => 0, 'haber' => $montoIva, 'referencia' => 'IVA Trasladado Cobrado']);
                                } else {
                                    // Pago: De Pendiente a Acreditable Pagado
                                    $poliza->asientos()->create(['cuenta_id' => $this->obtenerCuentaGenerica($empresaId, $map['iva_acreditable'], 'IVA Acreditable Pagado')->id, 'debe' => $montoIva, 'haber' => 0, 'referencia' => 'IVA Acreditable Pagado']);
                                    $poliza->asientos()->create(['cuenta_id' => $this->obtenerCuentaGenerica($empresaId, '119', 'IVA Pendiente de Pago')->id, 'debe' => 0, 'haber' => $montoIva, 'referencia' => 'Ajuste IVA Pagado']);
                                }
                            }
                        }
                    }

                    // Retenciones (ISR Retenido Flip)
                    foreach (($impuestosP['retenciones'] ?? []) as $retencion) {
                        if (($retencion['impuesto'] ?? '') === '001' || ($retencion['impuesto'] ?? '') === 'ISR') {
                            $montoIsr = (float)($retencion['importe'] ?? 0);
                            if ($montoIsr > 0) {
                                if ($esVenta) {
                                    // De Pendiente (113.03) a Cobrado (113.02 / 113-002)
                                    $poliza->asientos()->create(['cuenta_id' => $this->obtenerCuentaGenerica($empresaId, '113.03', 'ISR Retenido Pendiente de Cobro')->id, 'debe' => 0, 'haber' => $montoIsr, 'referencia' => 'Ajuste ISR Retenido Cobrado']);
                                    $poliza->asientos()->create(['cuenta_id' => $this->obtenerCuentaGenerica($empresaId, $map['isr_retenido_favor'], 'ISR Retenido a Favor Cobrado')->id, 'debe' => $montoIsr, 'haber' => 0, 'referencia' => 'ISR Retenido Cobrado (REP)']);
                                }
                            }
                        }
                    }
                }
            }
            return;
        }

        if ($esVenta) {
            // VENTA (Ingreso):
            $metodoPagoVenta = $data['metodo_pago'] ?? 'PUE';
            $esPUE = ($metodoPagoVenta === 'PUE');

            if ($esPUE) {
                // PUE: Pago de contado → Cargo directo a Bancos
                $cuentaBancoCont = $this->obtenerCuentaBancoContable($empresaId, $bancoId);
                if ($esEgreso) {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaBancoCont->id, 'debe' => 0, 'haber' => $total, 'referencia' => 'Devolución de contado (PUE)']);
                } else {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaBancoCont->id, 'debe' => $total, 'haber' => 0, 'referencia' => 'Cobro de contado (PUE)']);
                }
                // Registrar movimiento bancario si se seleccionó cuenta
                if ($bancoId && !$esEgreso) {
                    $cuentaBanco = \App\Models\CuentaBancaria::find($bancoId);
                    if ($cuentaBanco) {
                        $mov = $cuentaBanco->registrarMovimiento('deposito', $total, "Cobro XML: " . $poliza->concepto, 'venta', null);
                        // Vincular con la póliza en el nuevo módulo de bancos
                        $bancoCuenta = $cuentaBanco->getBancoCuentaCorrespondiente();
                        if ($bancoCuenta) {
                            \App\Models\Bancos\BancoMovimiento::where('cuenta_bancaria_id', $bancoCuenta->id)
                                ->where('fecha', $poliza->fecha)
                                ->where('monto', $total)
                                ->latest()
                                ->first()?->update(['poliza_id' => $poliza->id]);
                        }
                    }
                }
            } else {
                // PPD: A crédito → Cargo a Clientes (el REP moverá de Clientes a Bancos)
                $cuentaCliente = $this->obtenerCuentaPorRfc($empresaId, $data['receptor']['rfc'], 'Clientes', $data['receptor']['nombre'] ?? '');
                if ($esEgreso) {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaCliente->id, 'debe' => 0, 'haber' => $total, 'referencia' => 'Crédito/Devolución Cliente (PPD)']);
                } else {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaCliente->id, 'debe' => $total, 'haber' => 0, 'referencia' => 'Cuenta por cobrar (PPD)']);
                }
            }
            
            // Retenciones a Favor (Cargo)
            foreach ($listaRetenciones as $ret) {
                if ($ret['impuesto'] === '001') {
                    // ISR Retenido
                    $codigoRet = $esPUE ? $map['isr_retenido_favor'] : '113.03';
                    $nombreRet = $esPUE ? 'ISR Retenido a Favor' : 'ISR Retenido Pendiente de Cobro';
                } else {
                    // IVA Retenido
                    $codigoRet = '113.01';
                    $nombreRet = 'IVA Retenido a Favor';
                }
                $cuentaRet = $this->obtenerCuentaGenerica($empresaId, $codigoRet, $nombreRet);
                if ($esEgreso) {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaRet->id, 'debe' => 0, 'haber' => $ret['importe'], 'referencia' => 'Devolución Retención ' . $nombreRet]);
                } else {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaRet->id, 'debe' => $ret['importe'], 'haber' => 0, 'referencia' => 'Retención ' . $nombreRet]);
                }
            }

            $cuentaVentas = $this->obtenerCuentaGenerica($empresaId, '401', 'Ventas');
            
            $descuento = (float)($data['descuento'] ?? 0);
            $subtotalNeto = $subtotal - $descuento;

            if ($esEgreso) {
                $poliza->asientos()->create(['cuenta_id' => $cuentaVentas->id, 'debe' => $subtotalNeto, 'haber' => 0, 'referencia' => 'Devolución/Descuento S/Ventas']);
            } else {
                $poliza->asientos()->create(['cuenta_id' => $cuentaVentas->id, 'debe' => 0, 'haber' => $subtotalNeto, 'referencia' => 'Subtotal Neto (Sub:' . $subtotal . ' - Desc:' . $descuento . ')']);
            }
            
            if ($iva > 0) {
                // Si es PUE, va directo a IVA por Pagar (Cobrado). Si es PPD, va a IVA Pendiente de Cobro.
                $codigoIva = $esPUE ? $map['iva_trasladado'] : '210';
                $nombreIva = $esPUE ? 'IVA Trasladado Cobrado' : 'IVA Pendiente de Cobro';
                $cuentaIva = $this->obtenerCuentaGenerica($empresaId, $codigoIva, $nombreIva);
                if ($esEgreso) {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaIva->id, 'debe' => $iva, 'haber' => 0, 'referencia' => 'IVA 16% (Devolución)']);
                } else {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaIva->id, 'debe' => 0, 'haber' => $iva, 'referencia' => 'IVA 16%']);
                }
            }
        } else {
            // COMPRA (Egreso):
            // ... logic assumed continue below
            // 1. Gasto/Compra (Cargo)
            $emisorNombre = strtoupper($data['emisor']['nombre'] ?? '');
            
            $codigoGasto = null;
            $nombreGasto = 'Costo de Ventas';

            if ($clasificacion === 'costo') {
                $codigoGasto = $this->getAccountMap()['costos'];
                $nombreGasto = 'Costo de Ventas';
            } elseif ($clasificacion === 'activo') {
                $codigoGasto = '154.01'; // Default Equipo de Cómputo or Generic
                $nombreGasto = 'Equipo de Cómputo / Activo';
            } elseif ($clasificacion === 'gasto') {
                $codigoGasto = '601.01';
                $nombreGasto = 'Gastos Administrativos';
            }

            if (!$codigoGasto) {
                $cuentaGasto = $this->obtenerCuentaGastoSugerida($data, $empresaId);
            } else {
                $cuentaGasto = $this->obtenerCuentaGenerica($empresaId, $codigoGasto, $nombreGasto);
            }
            
            // Ajustar subtotal con descuento para el asiento de gasto
            $descuento = (float)($data['descuento'] ?? 0);
            $subtotalNeto = $subtotal - $descuento;

            $poliza->asientos()->create([
                'cuenta_id' => $cuentaGasto->id,
                'debe' => $esEgreso ? 0 : $subtotalNeto,
                'haber' => $esEgreso ? $subtotalNeto : 0,
                'referencia' => ($esEgreso ? 'Devolución/Descuento S/Gasto ' : 'Subtotal Neto ') . '(Sub:' . $subtotal . ' - Desc:' . $descuento . ')',
            ]);
            
            if ($iva > 0) {
                // Si es PUE, va directo a IVA Acreditable Pagado. Si es PPD, va a IVA Pendiente de Pago.
                $esPUE = (($data['metodo_pago'] ?? 'PUE') === 'PUE');
                $codigoIva = $esPUE ? $map['iva_acreditable'] : '119';
                $nombreIva = $esPUE ? 'IVA Acreditable Pagado' : 'IVA Pendiente de Pago';
                $cuentaIva = $this->obtenerCuentaGenerica($empresaId, $codigoIva, $nombreIva);
                $poliza->asientos()->create([
                    'cuenta_id' => $cuentaIva->id,
                    'debe' => $esEgreso ? 0 : $iva,
                    'haber' => $esEgreso ? $iva : 0,
                    'referencia' => ($esEgreso ? 'IVA 16% (Devolución)' : 'IVA 16%'),
                ]);
            }

            // Retenciones (Abono)
            foreach ($listaRetenciones as $ret) {
                $codigoRet = ($ret['impuesto'] === '001') ? '208.02' : '208.01'; // 001=ISR, 002=IVA
                $nombreRet = ($ret['impuesto'] === '001') ? 'ISR Retenido' : 'IVA Retenido';
                $cuentaRet = $this->obtenerCuentaGenerica($empresaId, $codigoRet, $nombreRet);
                if ($esEgreso) {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaRet->id, 'debe' => $ret['importe'], 'haber' => 0, 'referencia' => 'Devolución Retención ' . $nombreRet]);
                } else {
                    $poliza->asientos()->create(['cuenta_id' => $cuentaRet->id, 'debe' => 0, 'haber' => $ret['importe'], 'referencia' => 'Retención ' . $nombreRet]);
                }
            }
            
            // 3. Proveedor o Banco (Abono)
            if ($bancoId) {
                $cuentaBanco = \App\Models\CuentaBancaria::findOrFail($bancoId);
                $mov = $cuentaBanco->registrarMovimiento($esEgreso ? 'deposito' : 'retiro', $total, "Pago XML: " . $poliza->concepto, 'gasto');
                // Vincular con la póliza en el nuevo módulo de bancos
                $bancoCuenta = $cuentaBanco->getBancoCuentaCorrespondiente();
                if ($bancoCuenta) {
                    \App\Models\Bancos\BancoMovimiento::where('cuenta_bancaria_id', $bancoCuenta->id)
                        ->where('fecha', $poliza->fecha)
                        ->where('monto', $total)
                        ->latest()
                        ->first()?->update(['poliza_id' => $poliza->id]);
                }
                $ctaBancariaCont = $this->obtenerCuentaBancoContable($empresaId, $bancoId);
                $poliza->asientos()->create([
                    'cuenta_id' => $ctaBancariaCont->id,
                    'debe' => $esEgreso ? $total : 0,
                    'haber' => $esEgreso ? 0 : $total,
                    'referencia' => ($esEgreso ? 'Reembolso a ' : 'Pago desde ') . $cuentaBanco->nombre,
                ]);
            } else {
                $metodoPagoCompra = $data['metodo_pago'] ?? 'PUE';
                $esPUE = ($metodoPagoCompra === 'PUE');

                if ($esPUE) {
                    $ctaBancariaCont = $this->obtenerCuentaBancoContable($empresaId, $bancoId);
                    $poliza->asientos()->create([
                        'cuenta_id' => $ctaBancariaCont->id,
                        'debe' => $esEgreso ? $total : 0,
                        'haber' => $esEgreso ? 0 : $total,
                        'referencia' => $esEgreso ? 'Reembolso de contado (PUE)' : 'Pago de contado (PUE)',
                    ]);
                } else {
                    $cuentaProv = $this->obtenerCuentaPorRfc($empresaId, $data['emisor']['rfc'], 'Proveedores', $data['emisor']['nombre'] ?? '');
                    $poliza->asientos()->create([
                        'cuenta_id' => $cuentaProv->id,
                        'debe' => $esEgreso ? $total : 0,
                        'haber' => $esEgreso ? 0 : $total,
                        'referencia' => $esEgreso ? 'Crédito/Devolución Proveedor (PPD)' : 'Cuenta por pagar (PPD)',
                    ]);
                }
            }
        }
    }

    /**
     * Códigos de cuenta estándar
     */
    protected function getAccountMap(): array
    {
        return [
            'bancos' => '102-001',
            'clientes' => '105',
            'isr_retenido_favor' => '113-002',
            'iva_retenido_favor' => '113-001',
            'iva_acreditable' => '118',
            'proveedores' => '201',
            'iva_trasladado' => '213-002', // Directo a IVA por Pagar
            'iva_retenido' => '213.02',    // Directo a IVA por Pagar
            'isr_retenido' => '213.01',    // Directo a ISR por Pagar
            'ventas' => '401',
            'costos' => '501',
            'nomina_gasto' => '601.02',
            'combustibles' => '601.03',
            'vehiculos' => '602',
            'nomina_pasivo' => '201.01',
        ];
    }

    /**
     * Centraliza la lógica de detección de cuenta de gasto (Cargo)
     */
    protected function obtenerCuentaGastoSugerida(array $data, int $empresaId): CuentaContable
    {
        $emisorRfc = $data['emisor']['rfc'] ?? '';
        $mapping = RfcMapping::with('cuenta')->where('empresa_id', $empresaId)->where('rfc', $emisorRfc)->first();
        
        if ($mapping && $mapping->cuenta_id && ($mapping->cuenta?->tipo === 'egreso' || str_starts_with($mapping->cuenta?->codigo ?? '', '501') || str_starts_with($mapping->cuenta?->codigo ?? '', '6'))) {
            return $mapping->cuenta;
        }

        // Intento inteligente de categorización con Inteligencia Artificial (Gemini 2.0 Flash)
        try {
            $aiService = app(AICategorizationService::class);
            $cuentaAi = $aiService->categorizeExpense($data, $empresaId);
            if ($cuentaAi) {
                return $cuentaAi;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en categorización IA: ' . $e->getMessage());
        }

        // Fallback al sistema estático / regex legacy
        $codigo = $this->determinarCuentaGasto($data);
        
        // Nombres amigables según el código detectado
        $nombres = [
            '603' => 'Gastos Financieros (Auto)',
            '601.03' => 'Combustibles y Lubricantes (Auto)',
            '602.04' => 'Mantenimiento de Vehículos (Auto)',
            '602' => 'Gastos de Vehículos (Auto)',
            '501' => 'Costos de Venta (Auto)',
            '601.02' => 'Sueldos y Salarios (Nómina)',
            '601.01' => 'Gastos del Periodo (Auto)'
        ];

        return $this->obtenerCuentaGenerica($empresaId, $codigo, $nombres[$codigo] ?? 'Gasto General');
    }

    /**
     * Determina la cuenta de gasto más apropiada para un CFDI (Legacy / Regex)
     */
    protected function determinarCuentaGasto(array $data): string
    {
        $map = $this->getAccountMap();
        $emisor = strtoupper($data['emisor']['nombre'] ?? '');
        $conceptosStr = strtoupper(collect($data['conceptos'] ?? [])->pluck('descripcion')->implode(' '));
        $fullText = $emisor . ' ' . $conceptosStr;
        
        // 0. Nómina
        if (preg_match('/(NOMINA|SUELDO|SALARIO|AGUINALDO|VACACIONES|GRATIFICACION)/', $fullText)) {
            if (preg_match('/(SOFTWARE|LICENCIA|CONTPAQ|SISTEMA|COMPUTACIONAL)/', $fullText)) {
                // Evitar falsos positivos con licencias de software o servicios de nóminas
            } else {
                return $map['nomina_gasto'] ?? '601.02';
            }
        }

        // 1. Gastos Financieros (Préstamos, Bancos, Comisiones bancarias)
        if (preg_match('/(PRESTAMOS|FINANCI|SAPI|SANTANDER|BBVA|BANAMEX|HSBC|SCOTIA|INBURSA|INTERESES|BURSATIL|GBM)/', $fullText)) {
            return '603';
        }

        // 2. Combustibles
        $clavesCombustible = ['15101514', '15101515', '15101511', '15101505', '15101510', '15111512'];
        foreach (($data['conceptos'] ?? []) as $concepto) {
            if (in_array($concepto['clave_prod_serv'] ?? '', $clavesCombustible)) return $map['combustibles'] ?? '601.03';
        }
        if (preg_match('/(GASOLIN|COMBUSTIBLE|ESTACION.*SERVICIO|OXO.*GAS|PETROMAX|MAGNA|PREMIUM|DIESEL)/', $fullText)) {
            return $map['combustibles'] ?? '601.03';
        }

        // 3. Vehículos / Taller / Mantenimiento
        if (preg_match('/(ARRENDAMIENTO|RENTA.*VEHICULO|AUTO|VEHICULO|AUTOMOVIL|MANTENIMIENTO|REPARACION|REFACCION|TALLER|LLANTA|JOSE AMADO SOLANA)/', $fullText)) {
            return '602.04';
        }

        return $map['costos'] ?? '501';
    }

    /**
     * Busca la cuenta asociada a un RFC, si no existe la crea en una rama auxiliar
     */
    public function obtenerCuentaPorRfc(int $empresaId, string $rfc, string $padreNombre, ?string $nombreCompleto = null): CuentaContable
    {
        $map = $this->getAccountMap();
        $codigoPadre = ($padreNombre === 'Proveedores' || $padreNombre === 'Acreedores') ? $map['proveedores'] : $map['clientes'];

        $mapping = RfcMapping::where('empresa_id', $empresaId)->where('rfc', $rfc)->first();
        if ($mapping && $mapping->cuenta) {
            // Solo usar el mapping si pertenece a la rama solicitada (Clientes vs Proveedores)
            // o si es una cuenta de balance (no de resultados) que ya estaba asociada.
            if (str_starts_with($mapping->cuenta->codigo, $codigoPadre)) {
                return $mapping->cuenta;
            }
        }
        
        // Si no hay mapping, buscar o crear una cuenta genérica para este RFC
        
        // Intentar encontrar cuenta existente por RFC y que pertenezca a la rama correcta
        $existente = CuentaContable::where('empresa_id', $empresaId)
            ->where('codigo', 'ilike', "{$codigoPadre}%")
            ->where('nombre', 'ilike', "%{$rfc}%")
            ->first();
            
        if ($existente) {
            return $existente;
        }
        
        // Crear una cuenta nueva bajo el padre
        $padre = $this->obtenerCuentaGenerica($empresaId, $codigoPadre, $padreNombre);
        
        // Generar siguiente código (ej: 201-01-XXXX)
        $ultimoHijo = CuentaContable::where('empresa_id', $empresaId)
            ->where('padre_id', $padre->id)
            ->orderBy('codigo', 'desc')
            ->first();
            
        $nextNum = 1;
        if ($ultimoHijo) {
            $parts = explode('-', $ultimoHijo->codigo);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $nextNum = (int) $lastPart + 1;
            }
        }
        
        $nuevoCodigo = $padre->codigo . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        $nombreCuenta = $nombreCompleto ? "{$rfc} - {$nombreCompleto}" : "Auxiliar RFC {$rfc}";
        
        $cuenta = CuentaContable::create([
            'empresa_id' => $empresaId,
            'codigo' => $nuevoCodigo,
            'nombre' => $nombreCuenta,
            'tipo' => $padre->tipo,
            'naturaleza' => $padre->naturaleza,
            'nivel' => $padre->nivel + 1,
            'padre_id' => $padre->id,
            'es_detalle' => true,
        ]);
        
        RfcMapping::updateOrCreate(
            ['empresa_id' => $empresaId, 'rfc' => $rfc],
            ['cuenta_id' => $cuenta->id, 'nombre_auxiliar' => $rfc]
        );
        
        return $cuenta;
    }

    /**
     * Obtiene la cuenta contable asociada a un banco o un fallback genérico robusto
     */
    public function obtenerCuentaBancoContable(int $empresaId, ?int $bancoId = null): CuentaContable
    {
        $cuentaBanco = $bancoId 
            ? \App\Models\CuentaBancaria::find($bancoId)
            : \App\Models\CuentaBancaria::where('empresa_id', $empresaId)->where('activa', true)->first();

        if ($cuentaBanco) {
            $bancoCuenta = $cuentaBanco->getBancoCuentaCorrespondiente();
            if ($bancoCuenta && $bancoCuenta->cuenta_contable_id) {
                $cuenta = CuentaContable::find($bancoCuenta->cuenta_contable_id);
                if ($cuenta) {
                    return $cuenta;
                }
            }
        }

        // Buscar primera cuenta detalle bajo 102 (Bancos)
        $primera = CuentaContable::where('empresa_id', $empresaId)
            ->where('codigo', 'like', '102-%')
            ->where('es_detalle', true)
            ->orderBy('codigo', 'asc')
            ->first();

        if ($primera) {
            return $primera;
        }

        return $this->obtenerCuentaGenerica($empresaId, '102.01', 'Bancos Nacionales');
    }

    /**
     * Obtiene una cuenta base o la crea si no existe (Cuentas de Mayor)
     */
    public function obtenerCuentaGenerica(int $empresaId, string $codigo, string $nombre): CuentaContable
    {
        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('codigo', $codigo)
            ->first();
            
        if (!$cuenta) {
            // Determinar naturaleza y tipo por el primer dígito
            $tipo = 'activo';
            $naturaleza = 'deudora';
            
            $firstDigit = substr($codigo, 0, 1);
            if ($firstDigit == '2') { $tipo = 'pasivo'; $naturaleza = 'acreedora'; }
            if ($firstDigit == '3') { $tipo = 'capital'; $naturaleza = 'acreedora'; }
            if ($firstDigit == '4') { $tipo = 'ingreso'; $naturaleza = 'acreedora'; }
            if ($firstDigit == '5' || $firstDigit == '6') { $tipo = 'egreso'; $naturaleza = 'deudora'; }
            
            $cuenta = CuentaContable::create([
                'empresa_id' => $empresaId,
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'naturaleza' => $naturaleza,
                'nivel' => 1,
                'es_detalle' => false,
            ]);
        }
        
        return $cuenta;
    }

    public function generarSiguienteNumero(int $empresaId, string $tipo, string $anio): string
    {
        $ultima = PolizaContable::withTrashed()
            ->where('empresa_id', $empresaId)
            ->where('tipo', $tipo)
            ->whereYear('fecha', $anio)
            ->orderBy('id', 'desc')
            ->first();
            
        $num = 1;
        if ($ultima && preg_match('/(\d+)$/', $ultima->numero, $m)) {
            $num = (int)$m[1] + 1;
        }
        
        return strtoupper(substr($tipo, 0, 1)) . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Previsualizar datos de la póliza sin guardar en BD
     */
    public function generarPolizaMultiCfdi(array $xmlContents, int $empresaId, int $userId, ?int $bancoId = null, ?string $fechaOverride = null): PolizaContable
    {
        $map = $this->getAccountMap();
        $parsedList = [];
        $total = 0;
        $subtotal = 0;
        $descuento = 0;
        $iva = 0;
        $uuids = [];
        $conceptos = [];
        $xmlConcatenado = '';
        $esVenta = null;
        $esNomina = false;
        $metodoPago = 'PUE';
        $fechas = [];
        $nombreContrapartes = [];
        $retencionesMap = [];
        $esEgreso = true; // Si hay algún 'I', deja de ser egreso puro
        $totalesPorRfc = []; // [rfc => ['nombre' => ..., 'total' => float]]

        foreach ($xmlContents as $xml) {
            $data = $this->parser->parseCfdiXml($xml);
            $parsedList[] = $data;
            $total += (float) $data['total'];
            $subtotal += (float) $data['subtotal'];
            $descuento += (float) ($data['descuento'] ?? 0);
            $iva += (float) ($data['impuestos']['total_impuestos_trasladados'] ?? 0);
            $uuids[] = $data['uuid'];
            $xmlConcatenado .= $xml . "\n";
            
            if (!empty($data['impuestos']['retenciones'])) {
                foreach ($data['impuestos']['retenciones'] as $ret) {
                    $imp = $ret['impuesto']; // 001 ISR, 002 IVA
                    $retencionesMap[$imp] = ($retencionesMap[$imp] ?? 0) + (float)$ret['importe'];
                }
            }

            if (isset($data['conceptos'])) {
                foreach ($data['conceptos'] as $c) {
                    $conceptos[] = $c;
                }
            }

            if ($esVenta === null) {
                $emisorRfc = strtoupper($data['emisor']['rfc'] ?? '');
                $receptorRfc = strtoupper($data['receptor']['rfc'] ?? '');
                $empresa = Empresa::findOrFail($empresaId);
                $miRfc = strtoupper($empresa->rfc);
                $esVenta = ($emisorRfc === $miRfc);
            }

            if (($data['metodo_pago'] ?? 'PUE') === 'PPD') {
                $metodoPago = 'PPD';
            }
            if (($data['tipo_comprobante'] ?? 'I') !== 'E') $esEgreso = false;
            if (($data['tipo_comprobante'] ?? '') === 'N') $esNomina = true;
            if (!empty($data['fecha'])) {
                $fechas[] = substr($data['fecha'], 0, 10);
            }
            $contraNombre = $esVenta ? ($data['receptor']['nombre'] ?? '') : ($data['emisor']['nombre'] ?? '');
            if ($contraNombre) $nombreContrapartes[] = $contraNombre;
            $rfcKey = $esVenta ? ($data['receptor']['rfc'] ?? 'SIN_RFC') : ($data['emisor']['rfc'] ?? 'SIN_RFC');
            if (!isset($totalesPorRfc[$rfcKey])) {
                $totalesPorRfc[$rfcKey] = ['nombre' => $contraNombre ?: $rfcKey, 'total' => 0];
            }
            $totalesPorRfc[$rfcKey]['total'] += (float) $data['total'];
        }

        // Most common date among CFDI
        $fechaUsar = $fechaOverride ?: now()->toDateString();
        if (empty($fechaOverride) && !empty($fechas)) {
            $counts = array_count_values($fechas);
            arsort($counts);
            $fechaUsar = array_key_first($counts);
        }

        $esNomina = !empty($esNomina);
        $modoPago = ($metodoPago === 'PPD') ? ' [PPD]' : ' [PUE]';
        $tipo = $esVenta ? 'ingreso' : 'egreso';
        if ($esNomina) $tipo = 'egreso';
        if ($metodoPago === 'PPD') $tipo = 'diario';

        $uuidCount = count($uuids);

        $nombresUnicos = array_unique(array_filter($nombreContrapartes));
        $contraparte = count($nombresUnicos) === 1 ? reset($nombresUnicos) : 'Varios';

        if ($esNomina) {
            $contraparte = 'Múltiples empleados';
        } elseif ($contraparte === 'Varios' && count($nombresUnicos) > 0) {
            $contraparte = 'Varios (' . implode(', ', array_slice($nombresUnicos, 0, 3)) . (count($nombresUnicos) > 3 ? '...' : '') . ')';
        }

        $periodosNomina = [];
        foreach ($parsedList as $d) {
            if (!empty($d['complementos']['nomina'])) {
                $nom = $d['complementos']['nomina'];
                if (!empty($nom['fecha_inicial_pago']) && !empty($nom['fecha_final_pago'])) {
                    $periodosNomina[] = \Carbon\Carbon::parse($nom['fecha_inicial_pago'])->format('d/m') . ' al ' . \Carbon\Carbon::parse($nom['fecha_final_pago'])->format('d/m/Y');
                }
            }
        }
        $periodoTexto = '';
        if (!empty($periodosNomina)) {
            $uniques = array_unique($periodosNomina);
            $periodoTexto = (count($uniques) === 1) ? " Período: " . $uniques[0] : " Varios Períodos";
        }

        $label = $esNomina ? 'Nóminas Múltiples' : ($esVenta ? 'Ventas Múltiples' : 'Compras/Gastos Múltiples');
        $subtotalNeto = $subtotal - $descuento;

        $alertasExt = [];
        foreach ($parsedList as $pData) {
            $alerta = $this->verificarExtemporaneo($pData);
            if ($alerta) $alertasExt[] = $alerta;
        }
        $conceptoPrefix = !empty($alertasExt) ? implode(' ', array_unique($alertasExt)) . " - " : "";

        return DB::transaction(function () use ($empresaId, $userId, $tipo, $metodoPago, $modoPago, $contraparte, $total, $subtotalNeto, $iva, $uuids, $uuidCount, $xmlConcatenado, $conceptos, $esVenta, $esNomina, $bancoId, $fechaUsar, $label, $periodoTexto, $parsedList, $retencionesMap, $totalesPorRfc, $map, $conceptoPrefix, $esEgreso) {
            $poliza = PolizaContable::create([
                'empresa_id' => $empresaId,
                'tipo' => $tipo,
                'fecha' => $fechaUsar,
                'numero' => $this->generarSiguienteNumero($empresaId, $tipo, substr($fechaUsar, 0, 4)),
                'concepto' => $conceptoPrefix . $label . $modoPago . " - " . $contraparte . " (" . $uuidCount . " CFDI)" . $periodoTexto,
                'cfdi_uuid' => $uuids[0],
                'cfdi_uuids' => $uuids,
                'xml_content' => json_encode(['uuids' => $uuids]) . "\n" . $xmlConcatenado,
                'total' => $total,
                'estado' => 'borrador',
                'created_by' => $userId,
            ]);

            if ($esNomina) {
                $cuentaSueldos = $this->obtenerCuentaGenerica($empresaId, $map['nomina_gasto'], 'Sueldos y Salarios Administrativos');
                $poliza->asientos()->create(['cuenta_id' => $cuentaSueldos->id, 'debe' => $total, 'haber' => 0, 'referencia' => 'Nóminas']);
                $cuentaAcreedor = $this->obtenerCuentaGenerica($empresaId, $map['nomina_pasivo'], 'Acreedores');
                $poliza->asientos()->create(['cuenta_id' => $cuentaAcreedor->id, 'debe' => 0, 'haber' => $total, 'referencia' => 'Nóminas']);
            } elseif ($esVenta) {
                foreach ($totalesPorRfc as $rfcKey => $info) {
                    $ctaCliente = $this->obtenerCuentaPorRfc($empresaId, $rfcKey, 'Clientes', $info['nombre']);
                    $poliza->asientos()->create([
                        'cuenta_id' => $ctaCliente->id,
                        'debe' => $esEgreso ? 0 : $info['total'],
                        'haber' => $esEgreso ? $info['total'] : 0,
                        'referencia' => ($esEgreso ? 'Devolución Cliente: ' : 'Cliente: ') . $info['nombre'],
                    ]);
                }
                $cuentaVentas = $this->obtenerCuentaGenerica($empresaId, $map['ventas'], 'Ventas');
                $poliza->asientos()->create([
                    'cuenta_id' => $cuentaVentas->id,
                    'debe' => $esEgreso ? $subtotalNeto : 0,
                    'haber' => $esEgreso ? 0 : $subtotalNeto,
                    'referencia' => $esEgreso ? 'Devolución Ventas múltiples' : 'Ventas múltiples',
                ]);
                if ($iva > 0) {
                    $codigoIva = ($metodoPago === 'PUE') ? $map['iva_trasladado'] : '210';
                    $nombreIva = ($metodoPago === 'PUE') ? 'IVA Trasladado' : 'IVA Pendiente de Cobro';
                    $cuentaIva = $this->obtenerCuentaGenerica($empresaId, $codigoIva, $nombreIva);
                    if ($esEgreso) {
                        $poliza->asientos()->create([
                            'cuenta_id' => $cuentaIva->id,
                            'debe' => $iva,
                            'haber' => 0,
                            'referencia' => 'IVA múltiples (Devolución)',
                        ]);
                    } else {
                        $poliza->asientos()->create([
                            'cuenta_id' => $cuentaIva->id,
                            'debe' => 0,
                            'haber' => $iva,
                            'referencia' => 'IVA múltiples',
                        ]);
                    }
                }

                // Retenciones a Favor (Cargo)
                foreach ($retencionesMap as $impuestoCode => $importe) {
                    $codigoRet = ($impuestoCode === '001') ? $map['isr_retenido_favor'] : $map['iva_retenido_favor'];
                    $nombreRet = ($impuestoCode === '001') ? 'ISR Retenido a Favor' : 'IVA Retenido a Favor';
                    $cuentaRet = $this->obtenerCuentaGenerica($empresaId, $codigoRet, $nombreRet);
                    $poliza->asientos()->create([
                        'cuenta_id' => $cuentaRet->id,
                        'debe' => $esEgreso ? 0 : $importe,
                        'haber' => $esEgreso ? $importe : 0,
                        'referencia' => ($esEgreso ? 'Devolución ' : '') . 'Retención ' . $nombreRet . ' Múltiple',
                    ]);
                }
            } else {
                // Gastos múltiples: Intentamos agrupar por cuenta de gasto si es posible, 
                // pero para simplificar usamos el promedio o el más común.
                // Aquí mejor usamos determinarCuentaGasto para cada uno y agrupamos.
                $gastosPorCuenta = [];
                foreach ($parsedList as $d) {
                    $cuentaGasto = $this->obtenerCuentaGastoSugerida($d, $empresaId);
                    $gastosPorCuenta[$cuentaGasto->id] = ($gastosPorCuenta[$cuentaGasto->id] ?? 0) + ((float)$d['subtotal'] - (float)($d['descuento'] ?? 0));
                }

                foreach ($gastosPorCuenta as $cuentaId => $monto) {
                    $poliza->asientos()->create([
                        'cuenta_id' => $cuentaId,
                        'debe' => $esEgreso ? 0 : $monto,
                        'haber' => $esEgreso ? $monto : 0,
                        'referencia' => $esEgreso ? 'Devolución Gasto agrupado' : 'Gasto agrupado',
                    ]);
                }

                if ($iva > 0) {
                    $codigoIva = ($metodoPago === 'PUE') ? $map['iva_acreditable'] : '119';
                    $nombreIva = ($metodoPago === 'PUE') ? 'IVA Acreditable Pagado' : 'IVA Pendiente de Pago';
                    $cuentaIva = $this->obtenerCuentaGenerica($empresaId, $codigoIva, $nombreIva);
                    $poliza->asientos()->create([
                        'cuenta_id' => $cuentaIva->id,
                        'debe' => $esEgreso ? 0 : $iva,
                        'haber' => $esEgreso ? $iva : 0,
                        'referencia' => $esEgreso ? 'IVA múltiples (Devolución)' : 'IVA múltiples',
                    ]);
                }

                // Retenciones (Abono)
                foreach ($retencionesMap as $impuestoCode => $importe) {
                    $codigoRet = ($impuestoCode === '001') ? $map['isr_retenido'] : $map['iva_retenido'];
                    $nombreRet = ($impuestoCode === '001') ? 'ISR Retenido' : 'IVA Retenido';
                    $cuentaRet = $this->obtenerCuentaGenerica($empresaId, $codigoRet, $nombreRet);
                    $poliza->asientos()->create([
                        'cuenta_id' => $cuentaRet->id,
                        'debe' => $esEgreso ? $importe : 0,
                        'haber' => $esEgreso ? 0 : $importe,
                        'referencia' => ($esEgreso ? 'Devolución ' : '') . 'Retención ' . $nombreRet . ' Múltiple',
                    ]);
                }

                if ($metodoPago === 'PPD') {
                    foreach ($totalesPorRfc as $rfcKey => $info) {
                        $cuentaProveedor = $this->obtenerCuentaPorRfc($empresaId, $rfcKey, 'Proveedores', $info['nombre']);
                        $poliza->asientos()->create([
                            'cuenta_id' => $cuentaProveedor->id,
                            'debe' => $esEgreso ? $info['total'] : 0,
                            'haber' => $esEgreso ? 0 : $info['total'],
                            'referencia' => ($esEgreso ? 'Devolución Proveedor: ' : 'Proveedor: ') . $info['nombre'],
                            'auxiliar' => $info['nombre'],
                        ]);
                    }
                } else {
                    $codigoBanco = $map['bancos'] ?? '102.01-002';
                    $cuentaBanco = $this->obtenerCuentaGenerica($empresaId, $codigoBanco, 'Bancos Nacionales');
                    $poliza->asientos()->create([
                        'cuenta_id' => $cuentaBanco->id,
                        'debe' => $esEgreso ? $total : 0,
                        'haber' => $esEgreso ? 0 : $total,
                        'referencia' => $esEgreso ? 'Reembolso de contado (PUE)' : 'Pago de contado (PUE)',
                        'auxiliar' => $contraparte,
                    ]);
                }
            }

            // 3. Conciliación automática masiva
            foreach ($parsedList as $data) {
                if ($data['tipo_comprobante'] === 'P' || ($data['metodo_pago'] ?? 'PUE') === 'PUE' || ($data['tipo_comprobante'] ?? '') === 'N') {
                    if ($data['tipo_comprobante'] === 'P' && !empty($data['complementos']['pagos'])) {
                        foreach ($data['complementos']['pagos'] as $pago) {
                            if (!empty($pago['doctos_relacionados'])) {
                                foreach ($pago['doctos_relacionados'] as $doc) {
                                    if (!empty($doc['id_documento'])) {
                                        \App\Models\Cfdi::where('uuid', $doc['id_documento'])->update(['estado' => 'pagado']);
                                    }
                                }
                            }
                        }
                    } else {
                        \App\Models\Cfdi::where('uuid', $data['uuid'])->update(['estado' => 'pagado']);
                    }
                }
            }

            return $poliza;
        });
    }

    public function previsualizarPolizaMultiCfdi(array $xmlContents, int $empresaId, int $usuarioId): array
    {
        $map = $this->getAccountMap();
        $parsedList = [];
        $total = 0;
        $subtotal = 0;
        $descuento = 0;
        $iva = 0;
        $retencionesMap = [];
        $esVenta = null;
        $esNominaPrev = false;
        $metodoPago = 'PUE';
        $esEgreso = true; // Empieza en true y se vuelve false si hay algún 'I'
        $fechasMulti = [];
        $totalesPorRfc = []; // [rfc => ['nombre' => ..., 'total' => float]]

        foreach ($xmlContents as $xml) {
            $data = $this->parser->parseCfdiXml($xml);
            $parsedList[] = $data;
            $total += (float) $data['total'];
            $subtotal += (float) $data['subtotal'];
            $descuento += (float) ($data['descuento'] ?? 0);
            $iva += (float) ($data['impuestos']['total_impuestos_trasladados'] ?? 0);

            if (!empty($data['impuestos']['retenciones'])) {
                foreach ($data['impuestos']['retenciones'] as $ret) {
                    $imp = $ret['impuesto'];
                    $retencionesMap[$imp] = ($retencionesMap[$imp] ?? 0) + (float)$ret['importe'];
                }
            }

            if ($esVenta === null) {
                $emisorRfc = strtoupper($data['emisor']['rfc'] ?? '');
                $receptorRfc = strtoupper($data['receptor']['rfc'] ?? '');
                $empresa = Empresa::findOrFail($empresaId);
                $miRfc = strtoupper($empresa->rfc);
                $esVenta = ($emisorRfc === $miRfc);
            }
            if (($data['metodo_pago'] ?? 'PUE') === 'PPD') $metodoPago = 'PPD';
            if (($data['tipo_comprobante'] ?? '') === 'N') $esNominaPrev = true;
            if (($data['tipo_comprobante'] ?? 'I') !== 'E') $esEgreso = false;
            if (!empty($data['fecha'])) $fechasMulti[] = substr($data['fecha'], 0, 10);
            
            $contraRfc = $esVenta ? ($data['receptor']['rfc'] ?? 'SIN_RFC') : ($data['emisor']['rfc'] ?? 'SIN_RFC');
            $contraNombre = $esVenta ? ($data['receptor']['nombre'] ?? 'Sin Nombre') : ($data['emisor']['nombre'] ?? 'Sin Nombre');
            
            if (!isset($totalesPorRfc[$contraRfc])) {
                $totalesPorRfc[$contraRfc] = ['nombre' => $contraNombre, 'total' => 0];
            }
            $totalesPorRfc[$contraRfc]['total'] += (float) $data['total'];
        }

        // Most common date
        $fechaDetectada = now()->toDateString();
        if (!empty($fechasMulti)) {
            $counts = array_count_values($fechasMulti);
            arsort($counts);
            $fechaDetectada = array_key_first($counts);
        }

        $esNominaPrev = !empty($esNominaPrev);
        $tipo = $esVenta ? 'ingreso' : 'egreso';
        if ($esNominaPrev) $tipo = 'egreso';
        if ($metodoPago === 'PPD') $tipo = 'diario';

        $unicosPrev = [];
        foreach ($totalesPorRfc as $info) $unicosPrev[] = $info['nombre'];
        $unicosPrev = array_unique(array_filter($unicosPrev));

        $nombreMulti = count($unicosPrev) === 1 ? reset($unicosPrev) : 'Varios';
        if ($esNominaPrev) $nombreMulti = 'Múltiples empleados';
        elseif ($nombreMulti === 'Varios' && count($unicosPrev) > 0) {
            $nombreMulti = 'Varios (' . implode(', ', array_slice($unicosPrev, 0, 3)) . (count($unicosPrev) > 3 ? '...' : '') . ')';
        }

        $labelMulti = 'Compras/Gastos Múltiples';
        if ($esNominaPrev) $labelMulti = 'Nóminas Múltiples';
        elseif ($esVenta) $labelMulti = 'Ventas Múltiples';

        $subtotalNeto = $subtotal - $descuento;
        $asientos = [];
        if ($esNominaPrev) {
            $asientos[] = ['cuenta_codigo' => $map['nomina_gasto'], 'cuenta_nombre' => 'Sueldos y Salarios Administrativos', 'debe' => $total, 'haber' => 0];
            $asientos[] = ['cuenta_codigo' => $map['nomina_pasivo'], 'cuenta_nombre' => 'Acreedores', 'debe' => 0, 'haber' => $total, 'auxiliar' => 'Múltiples empleados'];
        } elseif ($esVenta) {
            foreach ($totalesPorRfc as $rfc => $info) {
                $cta = $this->obtenerCuentaPorRfc($empresaId, $rfc, 'Clientes', $info['nombre']);
                $asientos[] = ['cuenta_codigo' => $cta->codigo, 'cuenta_nombre' => $cta->nombre, 'debe' => $info['total'], 'haber' => 0];
            }
            $asientos[] = ['cuenta_codigo' => $map['ventas'], 'cuenta_nombre' => 'Ventas', 'debe' => 0, 'haber' => $subtotalNeto];
            if ($iva > 0) {
                $esPPD = ($metodoPago === 'PPD');
                $codIva = $esPPD ? '210' : $map['iva_trasladado'];
                $nomIva = $esPPD ? 'IVA Pendiente de Cobro' : 'IVA Trasladado';
                $asientos[] = ['cuenta_codigo' => $codIva, 'cuenta_nombre' => $nomIva, 'debe' => 0, 'haber' => $iva];
            }

            foreach ($retencionesMap as $impuestoCode => $importe) {
                $codigoRet = ($impuestoCode === '001') ? $map['isr_retenido_favor'] : $map['iva_retenido_favor'];
                $nombreRet = ($impuestoCode === '001') ? 'ISR Retenido a Favor' : 'IVA Retenido a Favor';
                $asientos[] = ['cuenta_codigo' => $codigoRet, 'cuenta_nombre' => $nombreRet, 'debe' => $importe, 'haber' => 0];
            }
        } else {
            $gastosPorCuenta = [];
            foreach ($parsedList as $d) {
                $ctaGasto = $this->determinarCuentaGasto($d);
                $gastosPorCuenta[$ctaGasto] = ($gastosPorCuenta[$ctaGasto] ?? 0) + ((float)$d['subtotal'] - (float)($d['descuento'] ?? 0));
            }

            foreach ($gastosPorCuenta as $codigo => $monto) {
                if ($esEgreso) {
                    $asientos[] = ['cuenta_codigo' => $codigo, 'cuenta_nombre' => 'Gastos', 'debe' => 0, 'haber' => $monto];
                } else {
                    $asientos[] = ['cuenta_codigo' => $codigo, 'cuenta_nombre' => 'Gastos', 'debe' => $monto, 'haber' => 0];
                }
            }

            if ($iva > 0) {
                $esPPD = ($metodoPago === 'PPD');
                $codIva = $esPPD ? '119' : $map['iva_acreditable'];
                $nomIva = $esPPD ? 'IVA Pendiente de Pago' : 'IVA Acreditable Pagado';
                if ($esEgreso) {
                    $asientos[] = ['cuenta_codigo' => $codIva, 'cuenta_nombre' => $nomIva, 'debe' => 0, 'haber' => $iva];
                } else {
                    $asientos[] = ['cuenta_codigo' => $codIva, 'cuenta_nombre' => $nomIva, 'debe' => $iva, 'haber' => 0];
                }
            }

            foreach ($retencionesMap as $impuestoCode => $importe) {
                $codigoRet = ($impuestoCode === '001') ? $map['isr_retenido'] : $map['iva_retenido'];
                $nombreRet = ($impuestoCode === '001') ? 'ISR Retenido' : 'IVA Retenido';
                if ($esEgreso) {
                    $asientos[] = ['cuenta_codigo' => $codigoRet, 'cuenta_nombre' => $nombreRet, 'debe' => $importe, 'haber' => 0];
                } else {
                    $asientos[] = ['cuenta_codigo' => $codigoRet, 'cuenta_nombre' => $nombreRet, 'debe' => 0, 'haber' => $importe];
                }
            }

            if ($metodoPago === 'PPD') {
                foreach ($totalesPorRfc as $rfc => $info) {
                    $cta = $this->obtenerCuentaPorRfc($empresaId, $rfc, 'Proveedores', $info['nombre']);
                    if ($esEgreso) {
                        $asientos[] = ['cuenta_codigo' => $cta->codigo, 'cuenta_nombre' => $cta->nombre, 'debe' => $info['total'], 'haber' => 0];
                    } else {
                        $asientos[] = ['cuenta_codigo' => $cta->codigo, 'cuenta_nombre' => $cta->nombre, 'debe' => 0, 'haber' => $info['total']];
                    }
                }
            } else {
                if ($esEgreso) {
                    $asientos[] = ['cuenta_codigo' => $map['proveedores'], 'cuenta_nombre' => 'Proveedores Locales', 'debe' => $total, 'haber' => 0, 'auxiliar' => $nombreMulti];
                } else {
                    $asientos[] = ['cuenta_codigo' => $map['proveedores'], 'cuenta_nombre' => 'Proveedores Locales', 'debe' => 0, 'haber' => $total, 'auxiliar' => $nombreMulti];
                }
            }
        }

        $periodosNominaPrev = [];
        foreach ($parsedList as $d) {
            if (!empty($d['complementos']['nomina'])) {
                $nom = $d['complementos']['nomina'];
                if (!empty($nom['fecha_inicial_pago']) && !empty($nom['fecha_final_pago'])) {
                    $periodosNominaPrev[] = \Carbon\Carbon::parse($nom['fecha_inicial_pago'])->format('d/m') . ' al ' . \Carbon\Carbon::parse($nom['fecha_final_pago'])->format('d/m/Y');
                }
            }
        }
        $periodoTextoPrev = '';
        if (!empty($periodosNominaPrev)) {
            $uniques = array_unique($periodosNominaPrev);
            $periodoTextoPrev = (count($uniques) === 1) ? " Período: " . $uniques[0] : " Varios Períodos";
        }

        return [
            'numero' => 'MÚLTIPLE',
            'fecha' => $fechaDetectada,
            'tipo' => $tipo,
            'concepto' => $labelMulti . ($metodoPago === 'PPD' ? ' [PPD]' : ' [PUE]') . " - " . $nombreMulti . $periodoTextoPrev,
            'total' => $total,
            'asientos' => $asientos,
            'multi_cfdi' => true,
        ];
    }

    public function previsualizarPolizaDesdeXml(string $xmlContent, int $empresaId, int $usuarioId, ?string $clasificacion = null): array
    {
        $map = $this->getAccountMap();
        $data = $this->parser->parseCfdiXml($xmlContent);
        $empresa = Empresa::findOrFail($empresaId);
        $miRfc = strtoupper($empresa->rfc);
        
        $emisorRfc = strtoupper($data['emisor']['rfc'] ?? '');
        $receptorRfc = strtoupper($data['receptor']['rfc'] ?? '');
        
        $esVenta = ($emisorRfc === $miRfc);
        $esCompra = ($receptorRfc === $miRfc);

        if (!$esVenta && !$esCompra) {
            throw new Exception("El RFC del emisor o receptor no coincide con el de la empresa ({$miRfc}).");
        }

        $esPago = ($data['tipo_comprobante'] ?? '') === 'P';
        $esEgreso = ($data['tipo_comprobante'] ?? '') === 'E';
        $metodoPagoPrev = $data['metodo_pago'] ?? 'PUE';
        $esNomina = ($data['tipo_comprobante'] ?? '') === 'N';
        
        $tipo = $esVenta ? 'ingreso' : 'egreso';
        if ($esNomina) $tipo = 'egreso';
        if ($metodoPagoPrev === 'PPD' && !$esPago) {
            $tipo = 'diario';
        }

        $total = (float) $data['total'];
        if ($esPago) {
            $totalP = 0;
            if (!empty($data['complementos']['pagos'])) {
                foreach ($data['complementos']['pagos'] as $p) $totalP += (float)($p['monto'] ?? 0);
            }
            $total = $totalP;
        }

        $asientos = [];
        $subtotal = (float) $data['subtotal'];
        $descuento = (float)($data['descuento'] ?? 0);
        $subtotalNeto = $subtotal - $descuento;
        $iva = (float) ($data['impuestos']['total_impuestos_trasladados'] ?? 0);

        if ($esNomina) {
            $asientos[] = ['cuenta_codigo' => $map['nomina_gasto'], 'cuenta_nombre' => 'Sueldos y Salarios Administrativos', 'debe' => $total, 'haber' => 0];
            $asientos[] = ['cuenta_codigo' => $map['nomina_pasivo'], 'cuenta_nombre' => 'Acreedores', 'debe' => 0, 'haber' => $total, 'auxiliar' => $data['receptor']['nombre'] ?? 'Empleado'];
        } elseif ($esVenta) {
            // PAGO DE CLIENTE (INGRESOS):
            if ($esPago) {
                $asientos[] = ['cuenta_codigo' => $map['bancos'] ?? '102.01', 'cuenta_nombre' => 'Bancos', 'debe' => $total, 'haber' => 0];
                $cta = $this->obtenerCuentaPorRfc($empresaId, $data['receptor']['rfc'], 'Clientes', $data['receptor']['nombre'] ?? '');
                $asientos[] = ['cuenta_codigo' => $cta->codigo, 'cuenta_nombre' => $cta->nombre, 'debe' => 0, 'haber' => $total];
            } else {
                // VENTA:
                $metodoPagoVenta = $data['metodo_pago'] ?? 'PUE';
                $esPUE = ($metodoPagoVenta === 'PUE');

                if ($esPUE) {
                    $asientos[] = ['cuenta_codigo' => '102.01', 'cuenta_nombre' => 'Bancos Nacionales', 'debe' => $esEgreso ? 0 : $total, 'haber' => $esEgreso ? $total : 0];
                } else {
                    $cta = $this->obtenerCuentaPorRfc($empresaId, $data['receptor']['rfc'], 'Clientes', $data['receptor']['nombre'] ?? '');
                    $asientos[] = ['cuenta_codigo' => $cta->codigo, 'cuenta_nombre' => $cta->nombre, 'debe' => $esEgreso ? 0 : $total, 'haber' => $esEgreso ? $total : 0];
                }

                $asientos[] = ['cuenta_codigo' => $map['ventas'], 'cuenta_nombre' => 'Ventas y/o Servicios', 'debe' => $esEgreso ? $subtotalNeto : 0, 'haber' => $esEgreso ? 0 : $subtotalNeto];
                
                if ($iva > 0) {
                    $codIva = ($metodoPagoPrev === 'PPD') ? '210' : '209.01';
                    $nomIva = ($metodoPagoPrev === 'PPD') ? 'IVA Pendiente de Cobro' : 'IVA Trasladado Cobrado';
                    $asientos[] = ['cuenta_codigo' => $codIva, 'cuenta_nombre' => $nomIva, 'debe' => $esEgreso ? $iva : 0, 'haber' => $esEgreso ? 0 : $iva];
                }

                if (!empty($data['impuestos']['retenciones'])) {
                    foreach ($data['impuestos']['retenciones'] as $ret) {
                        $codigoRet = ($ret['impuesto'] === '001') ? $map['isr_retenido_favor'] : $map['iva_retenido_favor'];
                        $nombreRet = ($ret['impuesto'] === '001') ? 'ISR Retenido a Favor' : 'IVA Retenido a Favor';
                        $asientos[] = ['cuenta_codigo' => $codigoRet, 'cuenta_nombre' => $nombreRet, 'debe' => $esEgreso ? 0 : (float)$ret['importe'], 'haber' => $esEgreso ? (float)$ret['importe'] : 0];
                    }
                }
            }
        } else {
            // PAGO A PROVEEDOR (EGRESOS):
            if ($esPago) {
                $cta = $this->obtenerCuentaPorRfc($empresaId, $data['emisor']['rfc'], 'Proveedores', $data['emisor']['nombre'] ?? '');
                $asientos[] = ['cuenta_codigo' => $cta->codigo, 'cuenta_nombre' => $cta->nombre, 'debe' => $total, 'haber' => 0];
                $asientos[] = ['cuenta_codigo' => $map['bancos'] ?? '102.01', 'cuenta_nombre' => 'Bancos', 'debe' => 0, 'haber' => $total];
            } else {
                // COMPRA/GASTO:
                $codigoGasto = null;
                $nombreGasto = 'Gastos del Periodo';

                if ($clasificacion === 'costo') {
                    $codigoGasto = $this->getAccountMap()['costos'] ?? '501';
                    $nombreGasto = 'Costo de Ventas';
                } elseif ($clasificacion === 'activo') {
                    $codigoGasto = '154.01';
                    $nombreGasto = 'Equipo de Cómputo / Activo';
                } elseif ($clasificacion === 'gasto') {
                    $codigoGasto = '601.01';
                    $nombreGasto = 'Gastos Administrativos';
                }

                if (!$codigoGasto) {
                    $cuentaGasto = $this->obtenerCuentaGastoSugerida($data, $empresaId);
                    $codigoGasto = $cuentaGasto->codigo;
                    $nombreGasto = $cuentaGasto->nombre;
                }

                $asientos[] = ['cuenta_codigo' => $codigoGasto, 'cuenta_nombre' => $nombreGasto, 'debe' => $esEgreso ? 0 : $subtotalNeto, 'haber' => $esEgreso ? $subtotalNeto : 0];
                
                if ($iva > 0) {
                    $esPPD = ($metodoPagoPrev === 'PPD');
                    $codIva = $esPPD ? '119' : ($map['iva_acreditable'] ?? '118');
                    $nomIva = $esPPD ? 'IVA Pendiente de Pago' : 'IVA Acreditable Pagado';
                    $asientos[] = ['cuenta_codigo' => $codIva, 'cuenta_nombre' => $nomIva, 'debe' => $esEgreso ? 0 : $iva, 'haber' => $esEgreso ? $iva : 0];
                }

                if (!empty($data['impuestos']['retenciones'])) {
                    foreach ($data['impuestos']['retenciones'] as $ret) {
                        $codigoRet = ($ret['impuesto'] === '001') ? $map['isr_retenido'] : $map['iva_retenido'];
                        $nombreRet = ($ret['impuesto'] === '001') ? 'ISR Retenido' : 'IVA Retenido';
                        $asientos[] = ['cuenta_codigo' => $codigoRet, 'cuenta_nombre' => $nombreRet, 'debe' => $esEgreso ? (float)$ret['importe'] : 0, 'haber' => $esEgreso ? 0 : (float)$ret['importe']];
                    }
                }

                if ($metodoPagoPrev === 'PPD') {
                    $cta = $this->obtenerCuentaPorRfc($empresaId, $data['emisor']['rfc'], 'Proveedores', $data['emisor']['nombre'] ?? '');
                    $asientos[] = ['cuenta_codigo' => $cta->codigo, 'cuenta_nombre' => $cta->nombre, 'debe' => $esEgreso ? $total : 0, 'haber' => $esEgreso ? 0 : $total];
                } else {
                    $asientos[] = ['cuenta_codigo' => $map['bancos'] ?? '102.01-002', 'cuenta_nombre' => 'Bancos Nacionales', 'debe' => $esEgreso ? $total : 0, 'haber' => $esEgreso ? 0 : $total];
                }
            }
        }

    $periodoUnico = '';
        if (!empty($data['complementos']['nomina'])) {
            $nom = $data['complementos']['nomina'];
            if (!empty($nom['fecha_inicial_pago']) && !empty($nom['fecha_final_pago'])) {
                $periodoUnico = " Período: " . \Carbon\Carbon::parse($nom['fecha_inicial_pago'])->format('d/m') . ' al ' . \Carbon\Carbon::parse($nom['fecha_final_pago'])->format('d/m/Y');
            }
        }

        $label = $esVenta ? 'Venta' : 'Compra/Gasto';
        if ($esNomina) $label = 'Nómina';
        if ($data['tipo_comprobante'] === 'P') $label = 'Pago';

        $alertaExt = $this->verificarExtemporaneo($data);
        $conceptoPrefix = $alertaExt ? $alertaExt . " - " : "";

        // Descripción de lo que se paga (para el modal)
        $descripcionPago = null;
        if (!empty($data['complementos']['pagos'])) {
            $docs = [];
            foreach ($data['complementos']['pagos'] as $p) {
                if (!empty($p['doctos_relacionados'])) {
                    foreach ($p['doctos_relacionados'] as $d) $docs[] = ($d['serie'] ?? '') . ($d['folio'] ?? '');
                }
            }
            if (!empty($docs)) $descripcionPago = "Pago a: " . implode(', ', array_unique(array_filter($docs)));
        }

        return [
            'numero' => 'PREVIA',
            'fecha' => $data['fecha'] ? substr($data['fecha'], 0, 10) : now()->toDateString(),
            'tipo' => $tipo,
            'concepto' => $conceptoPrefix . $label . ($metodoPagoPrev === 'PPD' ? ' [PPD]' : ' [PUE]') . " - " . ($esVenta ? ($data['receptor']['nombre'] ?? 'Sin Nombre') : ($data['emisor']['nombre'] ?? 'Sin Nombre')) . $periodoUnico,
            'total' => $total,
            'asientos' => $asientos,
            'xml_content' => $xmlContent,
            'descripcion_pago' => $descripcionPago,
            'doctos_relacionados_uuids' => $data['doctos_relacionados_uuids'] ?? [],
        ];
    }

    /**
     * Obtener Balanza de Comprobación
     */
    public function obtenerBalanza(int $empresaId, string $inicio, string $fin): array
    {
        // 1. Obtener sumas del periodo actual agrupadas por cuenta
        $periodoSums = AsientoContable::query()
            ->select('cuenta_id')
            ->selectRaw('SUM(debe) as total_debe, SUM(haber) as total_haber')
            ->whereHas('poliza', function($q) use ($inicio, $fin, $empresaId) {
                $q->where('empresa_id', $empresaId)
                  ->whereBetween('fecha', [$inicio, $fin])
                  ->where('estado', '!=', 'anulada');
            })
            ->groupBy('cuenta_id')
            ->get()
            ->keyBy('cuenta_id');

        // 2. Obtener sumas históricas (saldo inicial) agrupadas por cuenta
        $historicoSums = AsientoContable::query()
            ->select('cuenta_id')
            ->selectRaw('SUM(debe) as hist_debe, SUM(haber) as hist_haber')
            ->whereHas('poliza', function($q) use ($inicio, $empresaId) {
                $q->where('empresa_id', $empresaId)
                  ->where('fecha', '<', $inicio)
                  ->where('estado', '!=', 'anulada');
            })
            ->groupBy('cuenta_id')
            ->get()
            ->keyBy('cuenta_id');

        // 3. Obtener todas las cuentas
        $cuentas = CuentaContable::where('empresa_id', $empresaId)
            ->orderBy('codigo')
            ->get();

        // 4. Asignar montos base
        foreach ($cuentas as $cta) {
            $periodo = $periodoSums->get($cta->id);
            $historico = $historicoSums->get($cta->id);

            $cta->cargos = (float)($periodo->total_debe ?? 0);
            $cta->abonos = (float)($periodo->total_haber ?? 0);
            $cta->hist_debe = (float)($historico->hist_debe ?? 0);
            $cta->hist_haber = (float)($historico->hist_haber ?? 0);
        }

        // 5. Calcular profundidad real de forma dinámica para resolver jerarquías y niveles inconsistentes
        $depths = [];
        $getDepth = function($cta) use ($cuentas, &$getDepth, &$depths) {
            if (isset($depths[$cta->id])) return $depths[$cta->id];
            if (!$cta->padre_id) return $depths[$cta->id] = 1;
            $padre = $cuentas->firstWhere('id', $cta->padre_id);
            if (!$padre) return $depths[$cta->id] = 1;
            return $depths[$cta->id] = 1 + $getDepth($padre);
        };

        foreach ($cuentas as $cta) {
            $cta->calculated_depth = $getDepth($cta);
        }

        // Rollup jerárquico (de abajo hacia arriba, ordenado por profundidad calculada)
        $cuentasPorProfundidad = $cuentas->sortByDesc('calculated_depth');
        foreach ($cuentasPorProfundidad as $cta) {
            if ($cta->padre_id) {
                $padre = $cuentas->firstWhere('id', $cta->padre_id);
                if ($padre) {
                    $padre->cargos += $cta->cargos;
                    $padre->abonos += $cta->abonos;
                    $padre->hist_debe += $cta->hist_debe;
                    $padre->hist_haber += $cta->hist_haber;
                }
            }
        }

        $balanza = [];
        foreach ($cuentas as $cta) {
            $saldoInicial = ($cta->naturaleza === 'deudora') 
                ? ($cta->hist_debe - $cta->hist_haber) 
                : ($cta->hist_haber - $cta->hist_debe);

            $saldoFinal = ($cta->naturaleza === 'deudora')
                ? ($saldoInicial + $cta->cargos - $cta->abonos)
                : ($saldoInicial + $cta->abonos - $cta->cargos);

            if (round(abs($saldoInicial), 2) > 0 || round($cta->cargos, 2) > 0 || round($cta->abonos, 2) > 0 || round(abs($saldoFinal), 2) > 0) {
                $balanza[] = [
                    'id' => $cta->id,
                    'codigo' => $cta->codigo,
                    'nombre' => $cta->nombre,
                    'tipo' => $cta->tipo,
                    'naturaleza' => $cta->naturaleza,
                    'saldo_inicial' => $saldoInicial,
                    'cargos' => $cta->cargos,
                    'abonos' => $cta->abonos,
                    'saldo_final' => $saldoFinal,
                    'es_detalle' => $cta->es_detalle,
                    'nivel' => $cta->calculated_depth
                ];
            }
        }

        return $balanza;
    }

    /**
     * Obtener Estado de Resultados
     */
    public function obtenerEstadoResultados(int $empresaId, string $inicio, string $fin): array
    {
        $movimientos = AsientoContable::query()
            ->select('cuenta_id')
            ->selectRaw('SUM(debe) as total_debe, SUM(haber) as total_haber')
            ->whereHas('poliza', function($q) use ($inicio, $fin, $empresaId) {
                $q->where('empresa_id', $empresaId)
                  ->whereBetween('fecha', [$inicio, $fin])
                  ->where('estado', '!=', 'anulada');
            })
            ->whereHas('cuenta', function($q) {
                $q->whereIn('tipo', ['ingreso', 'egreso']);
            })
            ->groupBy('cuenta_id')
            ->get()
            ->keyBy('cuenta_id');

        $cuentas = CuentaContable::where('empresa_id', $empresaId)
            ->whereIn('tipo', ['ingreso', 'egreso'])
            ->orderBy('codigo')
            ->get();

        foreach ($cuentas as $cta) {
            $mov = $movimientos->get($cta->id);
            $cta->total_debe = (float)($mov->total_debe ?? 0);
            $cta->total_haber = (float)($mov->total_haber ?? 0);
        }

        $cuentasPorNivel = $cuentas->sortByDesc('nivel');
        foreach ($cuentasPorNivel as $cta) {
            if ($cta->padre_id) {
                $padre = $cuentas->firstWhere('id', $cta->padre_id);
                if ($padre) {
                    $padre->total_debe += $cta->total_debe;
                    $padre->total_haber += $cta->total_haber;
                }
            }
        }

        $ingresos = [];
        $costos = [];
        $gastosVenta = [];
        $gastosAdmin = [];
        $gastosFin = [];
        $otros = [];
        $impuestos = [];

        foreach ($cuentas as $cta) {
            $monto = ($cta->tipo === 'ingreso') 
                ? (float)($cta->total_haber - $cta->total_debe)
                : (float)($cta->total_debe - $cta->total_haber);

            if (round(abs($monto), 2) == 0) continue;

            // No incluir las grandes raíces contables 4, 5, 6, 7 y 8
            if (in_array($cta->codigo, ['4', '5', '6', '7', '8'])) continue;

            $data = [
                'id' => $cta->id,
                'codigo' => $cta->codigo, 
                'nombre' => $cta->nombre, 
                'monto' => $monto, 
                'nivel' => $cta->nivel, 
                'es_detalle' => $cta->es_detalle,
                'padre_id' => $cta->padre_id
            ];

            $codigo = (string)$cta->codigo;
            
            if ($cta->tipo === 'ingreso') {
                $ingresos[] = $data;
            } else {
                if (str_starts_with($codigo, '501')) {
                    $costos[] = $data;
                } elseif (str_starts_with($codigo, '601')) {
                    $gastosVenta[] = $data;
                } elseif (str_starts_with($codigo, '602')) {
                    $gastosAdmin[] = $data;
                } elseif (str_starts_with($codigo, '603')) {
                    $gastosFin[] = $data;
                } elseif (str_starts_with($codigo, '7') || str_starts_with($codigo, '8') || str_contains(strtolower($cta->nombre), 'impuesto') || str_contains(strtolower($cta->nombre), 'isr')) {
                    $impuestos[] = $data;
                } else {
                    if ($cta->codigo !== '5') {
                        $otros[] = $data;
                    }
                }
            }
        }

        // Totales calculados sumando únicamente las cuentas raíz de la sección (las que no tienen a su padre dentro de la sección)
        $sumarSeccion = function($items) {
            $ids = collect($items)->pluck('id')->toArray();
            return collect($items)
                ->filter(function($item) use ($ids) {
                    return !in_array($item['padre_id'], $ids);
                })
                ->sum('monto');
        };

        $totalIngresos = $sumarSeccion($ingresos);
        $totalCostos = $sumarSeccion($costos);
        $utilidadBruta = $totalIngresos - $totalCostos;
        
        $totalVenta = $sumarSeccion($gastosVenta);
        $totalAdmin = $sumarSeccion($gastosAdmin);
        $totalGastosOperacion = $totalVenta + $totalAdmin;
        
        $utilidadOperacion = $utilidadBruta - $totalGastosOperacion;
        
        $totalFin = $sumarSeccion($gastosFin);
        $totalOtros = $sumarSeccion($otros);

        $utilidadAntesImpuestos = $utilidadOperacion - $totalFin - $totalOtros;
        
        $totalImpuestos = $sumarSeccion($impuestos);
        $utilidadNeta = $utilidadAntesImpuestos - $totalImpuestos;

        // Función para inyectar porcentajes
        $addPercent = function($items) use ($totalIngresos) {
            return collect($items)->map(function($item) use ($totalIngresos) {
                $item['porcentaje'] = $totalIngresos > 0 ? ($item['monto'] / $totalIngresos) * 100 : 0;
                return $item;
            })->sortBy('codigo')->values()->toArray();
        };

        return [
            'secciones' => [
                ['key' => 'ingresos', 'titulo' => 'Ingresos Operativos', 'items' => $addPercent($ingresos), 'total' => $totalIngresos],
                ['key' => 'costos', 'titulo' => 'Costo de Ventas', 'items' => $addPercent($costos), 'total' => $totalCostos],
                ['key' => 'gastos_venta', 'titulo' => 'Gastos de Venta', 'items' => $addPercent($gastosVenta), 'total' => $totalVenta],
                ['key' => 'gastos_admin', 'titulo' => 'Gastos de Administración', 'items' => $addPercent($gastosAdmin), 'total' => $totalAdmin],
                ['key' => 'gastos_fin', 'titulo' => 'Gastos Financieros (RIF)', 'items' => $addPercent($gastosFin), 'total' => $totalFin],
                ['key' => 'otros', 'titulo' => 'Otros Gastos e Ingresos', 'items' => $addPercent($otros), 'total' => $totalOtros],
                ['key' => 'impuestos', 'titulo' => 'Impuestos a la Utilidad', 'items' => $addPercent($impuestos), 'total' => $totalImpuestos],
            ],
            'resumen' => [
                'ventas_netas' => $totalIngresos,
                'utilidad_bruta' => $utilidadBruta,
                'utilidad_operacion' => $utilidadOperacion,
                'utilidad_antes_impuestos' => $utilidadAntesImpuestos,
                'impuestos_ejercicio' => $totalImpuestos,
                'utilidad_neta' => $utilidadNeta,
                'margen_bruto' => $totalIngresos > 0 ? ($utilidadBruta / $totalIngresos) * 100 : 0,
                'margen_operativo' => $totalIngresos > 0 ? ($utilidadOperacion / $totalIngresos) * 100 : 0,
                'margen_antes_impuestos' => $totalIngresos > 0 ? ($utilidadAntesImpuestos / $totalIngresos) * 100 : 0,
                'margen_neto' => $totalIngresos > 0 ? ($utilidadNeta / $totalIngresos) * 100 : 0,
            ],
            // Mantener por compatibilidad básica
            'ingresos' => $addPercent($ingresos),
            'egresos' => $addPercent(array_merge($costos, $gastosVenta, $gastosAdmin, $gastosFin, $otros, $impuestos)),
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalCostos + $totalVenta + $totalAdmin + $totalFin + $totalOtros + $totalImpuestos,
            'utilidad' => $utilidadNeta
        ];
    }

    /**
     * Obtener Reporte de IVA basado en PÓLIZAS (Contabilidad)
     */
    public function obtenerReporteIva(int $empresaId, string $inicio, string $fin): array
    {
        $map = $this->getAccountMap();
        
        // Helper para sumar movimientos por código de cuenta
        $sumarCuenta = function($codigoPrefix, $tipoSaldo = 'haber-debe') use ($empresaId, $inicio, $fin) {
            $query = AsientoContable::whereHas('poliza', function($q) use ($empresaId, $inicio, $fin) {
                $q->where('empresa_id', $empresaId)
                  ->whereBetween('fecha', [$inicio, $fin])
                  ->where('estado', '!=', 'anulada')
                  ->where('concepto', 'not like', '%Contribuciones%')
                  ->where('concepto', 'not like', '%SAT - Transf%')
                  ->where('concepto', 'not like', '%Pago de Impuestos%');
            })->whereHas('cuenta', function($q) use ($codigoPrefix) {
                $q->where('codigo', 'like', $codigoPrefix . '%');
            });

            if ($tipoSaldo === 'haber-debe') {
                return (float) $query->selectRaw('SUM(haber) - SUM(debe) as total')->value('total') ?: 0;
            } else {
                return (float) $query->selectRaw('SUM(debe) - SUM(haber) as total')->value('total') ?: 0;
            }
        };

        // 1. IVA TRASLADADO (VENTAS/COBROS) - Saldo Acreedor en 213-002 o 213.02
        $ivaTrasladado = $sumarCuenta('213-002', 'haber-debe') + $sumarCuenta('213.02', 'haber-debe');

        // 2. IVA ACREDITABLE (GASTOS/PAGOS) - Solo cargos (Debe) en 118
        $ivaAcreditable = (float) AsientoContable::whereHas('poliza', function($q) use ($empresaId, $inicio, $fin) {
            $q->where('empresa_id', $empresaId)
              ->whereBetween('fecha', [$inicio, $fin])
              ->where('estado', '!=', 'anulada');
        })->whereHas('cuenta', function($q) {
            $q->where('codigo', 'like', '118%');
        })->sum('debe');

        // 3. INGRESOS BRUTOS - Flujo de Efectivo (Base IVA 16% + Ventas sin IVA)
        $baseIva16 = $ivaTrasladado > 0 ? ($ivaTrasladado / 0.16) : 0;
        
        $ventasSinIva = AsientoContable::whereHas('poliza', function($q) use ($empresaId, $inicio, $fin) {
            $q->where('empresa_id', $empresaId)
              ->whereBetween('fecha', [$inicio, $fin])
              ->where('tipo', 'ingreso')
              ->where('estado', '!=', 'anulada');
        })->whereHas('cuenta', function($q) {
            $q->where('codigo', 'like', '401%');
        })->whereNotExists(function($q) {
            $q->select(DB::raw(1))
              ->from('contab_asientos as a2')
              ->join('contab_cuentas as c2', 'a2.cuenta_id', '=', 'c2.id')
              ->whereRaw('a2.poliza_id = contab_asientos.poliza_id')
              ->where(function($sq) {
                  $sq->where('c2.codigo', 'like', '213-002%')
                    ->orWhere('c2.codigo', 'like', '213.02%');
              });
        })->selectRaw('SUM(haber) - SUM(debe) as total')->value('total') ?: 0;

        $ingresosBrutos = round($baseIva16 + $ventasSinIva, 2);

        // 4. RETENCIONES
        $isrRetenidoClientes = $sumarCuenta('113-002', 'debe-haber') + $sumarCuenta('113.02', 'debe-haber'); // ISR Retenido a Favor
        $isrRetenidoNomina = $sumarCuenta('213.01', 'haber-debe');   // Provisión de ISR (Nómina/RESICO)

        // 5. IVA DEVOLUCIONES (Suman al cargo en el SAT)
        // Buscamos movimientos en el Haber de la cuenta 118 que provengan de Notas de Crédito
        // o si existe una cuenta específica para devoluciones (ej. 501.02)
        $ivaDevolucionesGastos = (float) AsientoContable::whereHas('poliza', function($q) use ($empresaId, $inicio, $fin) {
            $q->where('empresa_id', $empresaId)
              ->whereBetween('fecha', [$inicio, $fin])
              ->where('estado', '!=', 'anulada');
        })->whereHas('cuenta', function($q) {
            $q->where('codigo', 'like', '118%');
        })->sum('haber');


        // Tasas ISR RESICO (Lógica de cálculo sobre ingresos contabilizados)
        $isAnnual = (substr($inicio, 5, 5) === '01-01' && substr($fin, 5, 5) === '12-31');
        $tasaIsr = 0.01;
        if ($isAnnual) {
            if ($ingresosBrutos > 300000) $tasaIsr = 0.011;
            if ($ingresosBrutos > 600000) $tasaIsr = 0.015;
            if ($ingresosBrutos > 1000000) $tasaIsr = 0.02;
            if ($ingresosBrutos > 2500000) $tasaIsr = 0.025;
        } else {
            if ($ingresosBrutos > 25000) $tasaIsr = 0.011;
            if ($ingresosBrutos > 50000) $tasaIsr = 0.015;
            if ($ingresosBrutos > 83333.33) $tasaIsr = 0.02;
            if ($ingresosBrutos > 208333.33) $tasaIsr = 0.025;
        }

        $isrResico = round($ingresosBrutos * $tasaIsr, 2);

        return [
            'trasladado' => round($ivaTrasladado, 2),
            'acreditable' => round($ivaAcreditable, 2),
            'iva_devoluciones_gastos' => round($ivaDevolucionesGastos, 2),
            'diferencia' => round($ivaTrasladado - $ivaAcreditable + $ivaDevolucionesGastos, 2),
            'ingresos_brutos' => round($ingresosBrutos, 2),
            'isr_resico' => $isrResico,
            'isr_retenido_clientes' => round($isrRetenidoClientes, 2),
            'isr_retenido_nomina' => round($isrRetenidoNomina, 2),
            'isr_neto_pagar' => round(max(0, $isrResico - $isrRetenidoClientes), 2),
            'total_sueldos' => 0,
            'tasa_isr' => $tasaIsr,
            'detalle_flujo' => [
                'informativo' => 'Saldos obtenidos directamente de Pólizas Contables',
                'iva_trasladado_total' => round($ivaTrasladado, 2),
                'iva_acreditable_total' => round($ivaAcreditable, 2),
                'iva_devoluciones_total' => round($ivaDevolucionesGastos, 2),
            ],
        ];

    }

    /**
     * Obtiene pólizas que no están cuadradas (Debe != Haber)
     */
    public function obtenerPolizasDescuadradas(int $empresaId): \Illuminate\Support\Collection
    {
        return \App\Models\Contab\PolizaContable::where('empresa_id', $empresaId)
            ->withSum('asientos as total_debe', 'debe')
            ->withSum('asientos as total_haber', 'haber')
            ->get()
            ->filter(function ($p) {
                return round((float)$p->total_debe, 2) != round((float)$p->total_haber, 2);
            })
            ->map(function ($p) {
                $p->debe_haber_diff = (float)$p->total_debe - (float)$p->total_haber;
                $p->diferencia = abs($p->debe_haber_diff);
                return $p;
            })
            ->values();
    }

    /**
     * Obtener ingresos brutos anuales acumulados (Flujo de Efectivo para RESICO)
     */
    public function obtenerIngresosAnuales(int $empresaId, int $anio): float
    {
        $inicioDt = $anio . '-01-01 00:00:00';
        $finDt = $anio . '-12-31 23:59:59';

        $pue = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'emitido')
            ->where('tipo_comprobante', 'I')
            ->where('metodo_pago', 'PUE')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')
                  ->orWhereNull('estado_sat');
            })
            ->whereBetween('fecha_emision', [$inicioDt, $finDt])
            ->where('subtotal', '>', 0.01)
            ->get()
            ->sum(fn($v) => (float)$v->subtotal - (float)$v->descuento);

        // 2. Pagos (Flujo de PPD)
        $pagos = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'emitido')
            ->where('tipo_comprobante', 'P')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')
                  ->orWhereNull('estado_sat');
            })
            ->where(function($q) use ($anio) {
                $q->whereBetween('fecha_emision', [$anio . '-01-01 00:00:00', $anio . '-12-31 23:59:59'])
                  ->orWhereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(complementos::jsonb->'pagos') as p WHERE p->>'fecha_pago' LIKE ?)", [$anio . '%']);
            })
            ->get();

        $ingresosPagos = 0;
        foreach ($pagos as $pago) {
            if (empty($pago->complementos['pagos'])) continue;
            foreach ($pago->complementos['pagos'] as $p) {
                $fechaPago = substr($p['fecha_pago'] ?? '', 0, 4);
                if ($fechaPago != $anio) continue;

                if (!empty($p['impuestos_p']['traslados'])) {
                    foreach ($p['impuestos_p']['traslados'] as $t) {
                        if (($t['impuesto'] ?? '') == '002') {
                            $basePago = (float)($t['base'] ?? 0);
                            if ($basePago > 0.01) {
                                $ingresosPagos += $basePago;
                            }
                        }
                    }
                }
            }
        }

        // 3. Notas de Crédito (tipo N) que restan del ingreso
        $notasCredito = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'emitido')
            ->where('tipo_comprobante', 'N')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')
                  ->orWhereNull('estado_sat');
            })
            ->whereBetween('fecha_emision', [$inicioDt, $finDt])
            ->where('subtotal', '>', 0.01)
            ->get()
            ->sum(fn($v) => (float)$v->subtotal - (float)$v->descuento);

        return (float)($pue + $ingresosPagos - $notasCredito);
    }

    /**
     * Obtiene el listado de Cuentas por Cobrar y por Pagar basándose estrictamente 
     * en los archivos XML (PPD vs REP).
     */
    public function obtenerSaldosXml(int $empresaId, ?string $mes = null, ?string $anio = null): array
    {
        // 1. OBTENER CUENTAS POR COBRAR (Clientes PPD y Notas)
        $queryCxc = \App\Models\CuentasPorCobrar::where('empresa_id', $empresaId)
            ->where('estado', '!=', 'anulada')
            ->with(['cliente', 'cfdi', 'venta.cliente']);

        // 2. OBTENER CUENTAS POR PAGAR (Proveedores PPD y Notas)
        $queryCxp = \App\Models\CuentasPorPagar::where('empresa_id', $empresaId)
            ->where('estado', '!=', 'anulada')
            ->with(['proveedor', 'cfdi', 'compra.proveedor']);

        $cxcList = $queryCxc->get();
        $cxpList = $queryCxp->get();

        // 3. PRE-CALCULAR PAGOS REP POR UUID DE CFDIS TIPO PAGO
        $reps = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('tipo_comprobante', 'P')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat');
            })
            ->orderBy('fecha_emision', 'desc')
            ->get(['id', 'uuid', 'serie', 'folio', 'fecha_emision', 'nombre_emisor', 'rfc_emisor', 'nombre_receptor', 'rfc_receptor', 'direccion', 'complementos', 'total']);

        $movsMap = [];
        foreach (\App\Models\Bancos\BancoMovimiento::whereNotNull('referencia')->where('referencia', '!=', '')->get(['id', 'referencia']) as $bm) {
            $movsMap[strtoupper(trim($bm->referencia))] = $bm->id;
        }

        $polizasMap = [];
        foreach (\App\Models\Contab\PolizaContable::whereNotNull('cfdi_uuid')->orWhereNotNull('cfdi_uuids')->get(['id', 'cfdi_uuid', 'cfdi_uuids', 'numero']) as $p) {
            $info = [
                'id' => $p->id,
                'numero' => $p->numero ?? ('#' . $p->id)
            ];
            if ($p->cfdi_uuid) {
                $polizasMap[strtoupper(trim($p->cfdi_uuid))] = $info;
            }
            if (!empty($p->cfdi_uuids) && is_array($p->cfdi_uuids)) {
                foreach ($p->cfdi_uuids as $u) {
                    if ($u) {
                        $polizasMap[strtoupper(trim($u))] = $info;
                    }
                }
            }
        }

        $repPagadoMap = [];
        $repsFormatted = [];

        foreach ($reps as $rep) {
            $comp = is_string($rep->complementos) ? json_decode($rep->complementos, true) : $rep->complementos;
            $pagos = [];
            $montoTotalPago = 0.0;
            if (isset($comp['pagos']) && is_array($comp['pagos'])) {
                foreach ($comp['pagos'] as $pago) {
                    $montoP = (float)($pago['monto'] ?? 0);
                    $montoTotalPago += $montoP;
                    $doctos = [];
                    if (isset($pago['doctos_relacionados']) && is_array($pago['doctos_relacionados'])) {
                        foreach ($pago['doctos_relacionados'] as $dr) {
                            $uuidDr = strtoupper($dr['id_documento'] ?? '');
                            if ($uuidDr) {
                                $impPagado = (float)($dr['imp_pagado'] ?? 0);
                                $repPagadoMap[$uuidDr] = ($repPagadoMap[$uuidDr] ?? 0.0) + $impPagado;
                                $doctos[] = [
                                    'uuid' => $dr['id_documento'] ?? '',
                                    'serie' => $dr['serie'] ?? '',
                                    'folio' => $dr['folio'] ?? '',
                                    'imp_pagado' => $impPagado
                                ];
                            }
                        }
                    }
                    $pagos[] = [
                        'fecha_pago' => $pago['fecha_pago'] ?? $rep->fecha_emision,
                        'monto' => $montoP,
                        'moneda' => $pago['moneda'] ?? 'MXN',
                        'forma_pago' => $pago['forma_pago'] ?? '03',
                        'doctos' => $doctos
                    ];
                }
            }
            $isRecibido = $rep->direccion === 'recibido';
            $refUpper = strtoupper(trim($rep->serie . $rep->folio));
            $enBancos = isset($movsMap[$refUpper]);
            $repsFormatted[] = [
                'id' => $rep->id,
                'uuid' => $rep->uuid,
                'serie' => $rep->serie,
                'folio' => $rep->folio,
                'fecha_emision' => $rep->fecha_emision,
                'direccion' => $rep->direccion,
                'tipo_flujo' => $isRecibido ? 'egreso' : 'ingreso',
                'contraparte_nombre' => $isRecibido ? $rep->nombre_emisor : $rep->nombre_receptor,
                'contraparte_rfc' => $isRecibido ? $rep->rfc_emisor : $rep->rfc_receptor,
                'monto_total' => $montoTotalPago > 0 ? $montoTotalPago : (float)$rep->total,
                'pagos' => $pagos,
                'en_bancos' => $enBancos,
                'mov_banco_id' => $enBancos ? $movsMap[$refUpper] : null
            ];
        }

        $cuentasCobrar = [];
        $cuentasPagar = [];

        foreach ($cxcList as $cxc) {
            $fechaEmision = $cxc->cfdi?->fecha_emision ?? $cxc->created_at;
            $dt = \Carbon\Carbon::parse($fechaEmision);
            
            // Aplicar filtros de mes y año
            if ($anio && $anio !== 'todos' && $dt->year != $anio) continue;
            if ($mes && $mes !== 'todos' && $dt->format('m') != $mes) continue;

            $dias = (int) now()->startOfDay()->diffInDays($dt->startOfDay());

            $isFactura = !empty($cxc->cfdi_id);
            $uuid = $cxc->cfdi?->uuid;
            $repPagado = $uuid && isset($repPagadoMap[strtoupper($uuid)]) ? $repPagadoMap[strtoupper($uuid)] : 0.0;
            
            $total = (float)$cxc->monto_total;
            $pagado = max((float)$cxc->monto_pagado, $repPagado);
            $saldo = max(0.0, $total - $pagado);

            if ($saldo <= 0.01 || $cxc->estado === 'pagado') {
                $estadoPago = 'pagado';
            } elseif ($pagado > 0.01 && $saldo > 0.01) {
                $estadoPago = 'parcial';
            } else {
                $estadoPago = 'pendiente';
            }

            if ($isFactura) {
                $serie = $cxc->cfdi?->serie;
                $folio = $cxc->cfdi?->folio ?? (string)$cxc->id;
                $razonSocial = $cxc->cliente?->nombre_razon_social ?? 'Público en General';
                $rfc = $cxc->cliente?->rfc;
            } else {
                $serie = 'NV';
                $folio = $cxc->venta?->numero_venta ?? (string)$cxc->id;
                $razonSocial = $cxc->venta?->cliente?->nombre_razon_social ?? $cxc->cliente?->nombre_razon_social ?? 'Público en General (Nota)';
                $rfc = $cxc->venta?->cliente?->rfc ?? $cxc->cliente?->rfc ?? 'XAXX010101000';
            }

            $refUpper = strtoupper(trim($serie . $folio));
            $enBancos = isset($movsMap[$refUpper]) || isset($movsMap[strtoupper(trim($folio))]);
            $metodoPago = $isFactura ? ($cxc->cfdi?->metodo_pago ?? 'PUE') : 'PUE';
            $polizaInfo = ($uuid && isset($polizasMap[strtoupper(trim($uuid))])) ? $polizasMap[strtoupper(trim($uuid))] : null;
            $tienePoliza = !empty($polizaInfo);

            $cuentasCobrar[] = [
                'uuid' => $uuid ?? 'NV-' . $cxc->id,
                'tipo_doc' => $isFactura ? 'factura' : 'nota',
                'estado_pago' => $estadoPago,
                'tiene_rep' => $repPagado > 0.01,
                'rep_pagado' => $repPagado,
                'fecha' => $fechaEmision,
                'folio' => $folio,
                'serie' => $serie,
                'razon_social' => $razonSocial,
                'rfc' => $rfc,
                'total' => $total,
                'pagado' => $pagado,
                'saldo' => $saldo,
                'dias_vencimiento' => -$dias,
                'en_bancos' => $enBancos,
                'mov_banco_id' => $enBancos ? ($movsMap[$refUpper] ?? $movsMap[strtoupper(trim($folio))] ?? null) : null,
                'metodo_pago' => $metodoPago,
                'tiene_poliza' => $tienePoliza,
                'poliza_id' => $tienePoliza ? $polizaInfo['id'] : null,
                'poliza_numero' => $tienePoliza ? $polizaInfo['numero'] : null,
            ];
        }

        foreach ($cxpList as $cxp) {
            $fechaEmision = $cxp->cfdi?->fecha_emision ?? $cxp->fecha_emision ?? $cxp->created_at;
            $dt = \Carbon\Carbon::parse($fechaEmision);

            // Aplicar filtros de mes y año
            if ($anio && $anio !== 'todos' && $dt->year != $anio) continue;
            if ($mes && $mes !== 'todos' && $dt->format('m') != $mes) continue;

            $dias = (int) now()->startOfDay()->diffInDays($dt->startOfDay());

            $isFactura = !empty($cxp->cfdi_id);
            $uuid = $cxp->cfdi?->uuid;
            $repPagado = $uuid && isset($repPagadoMap[strtoupper($uuid)]) ? $repPagadoMap[strtoupper($uuid)] : 0.0;

            $total = (float)$cxp->monto_total;
            $pagado = max((float)$cxp->monto_pagado, $repPagado);
            $saldo = max(0.0, $total - $pagado);

            if ($saldo <= 0.01 || $cxp->estado === 'pagado' || $cxp->estado === 'pagada') {
                $estadoPago = 'pagado';
            } elseif ($pagado > 0.01 && $saldo > 0.01) {
                $estadoPago = 'parcial';
            } else {
                $estadoPago = 'pendiente';
            }

            if ($isFactura) {
                $serie = $cxp->cfdi?->serie;
                $folio = $cxp->cfdi?->folio ?? (string)$cxp->id;
                $razonSocial = $cxp->proveedor?->nombre_razon_social ?? $cxp->compra?->proveedor?->nombre_razon_social ?? 'Proveedor sin nombre';
                $rfc = $cxp->proveedor?->rfc ?? $cxp->compra?->proveedor?->rfc;
            } else {
                $serie = 'OC';
                $folio = $cxp->compra?->numero_compra ?? (string)$cxp->id;
                $razonSocial = $cxp->compra?->proveedor?->nombre_razon_social ?? $cxp->proveedor?->nombre_razon_social ?? 'Proveedor (Orden)';
                $rfc = $cxp->compra?->proveedor?->rfc ?? $cxp->proveedor?->rfc ?? 'XEXX010101000';
            }

            $refUpper = strtoupper(trim($serie . $folio));
            $enBancos = isset($movsMap[$refUpper]) || isset($movsMap[strtoupper(trim($folio))]);
            $metodoPago = $isFactura ? ($cxp->cfdi?->metodo_pago ?? 'PUE') : 'PUE';
            $polizaInfo = ($uuid && isset($polizasMap[strtoupper(trim($uuid))])) ? $polizasMap[strtoupper(trim($uuid))] : null;
            $tienePoliza = !empty($polizaInfo);

            $cuentasPagar[] = [
                'uuid' => $uuid ?? 'OC-' . $cxp->id,
                'tipo_doc' => $isFactura ? 'factura' : 'nota',
                'estado_pago' => $estadoPago,
                'tiene_rep' => $repPagado > 0.01,
                'rep_pagado' => $repPagado,
                'fecha' => $fechaEmision,
                'folio' => $folio,
                'serie' => $serie,
                'razon_social' => $razonSocial,
                'rfc' => $rfc,
                'total' => $total,
                'pagado' => $pagado,
                'saldo' => $saldo,
                'dias_vencimiento' => -$dias,
                'en_bancos' => $enBancos,
                'mov_banco_id' => $enBancos ? ($movsMap[$refUpper] ?? $movsMap[strtoupper(trim($folio))] ?? null) : null,
                'metodo_pago' => $metodoPago,
                'tiene_poliza' => $tienePoliza,
                'poliza_id' => $tienePoliza ? $polizaInfo['id'] : null,
                'poliza_numero' => $tienePoliza ? $polizaInfo['numero'] : null,
            ];
        }

        $cobrarCol = collect($cuentasCobrar)->sortByDesc('fecha')->values();
        $pagarCol = collect($cuentasPagar)->sortByDesc('fecha')->values();

        return [
            'reps' => $repsFormatted,
            'por_cobrar' => [
                'items' => $cobrarCol->all(),
                'facturas' => $cobrarCol->where('tipo_doc', 'factura')->values()->all(),
                'notas' => $cobrarCol->where('tipo_doc', 'nota')->values()->all(),
                'total' => (float)$cobrarCol->where('estado_pago', '!=', 'pagado')->sum('saldo'),
                'count' => count($cuentasCobrar)
            ],
            'por_pagar' => [
                'items' => $pagarCol->all(),
                'facturas' => $pagarCol->where('tipo_doc', 'factura')->values()->all(),
                'notas' => $pagarCol->where('tipo_doc', 'nota')->values()->all(),
                'total' => (float)$pagarCol->where('estado_pago', '!=', 'pagado')->sum('saldo'),
                'count' => count($cuentasPagar)
            ]
        ];
    }
    /**
     * Obtener Reporte de IVA basado en XMLs (Fiscal Informativo)
     */
    public function obtenerReporteIvaXml(int $empresaId, string $inicio, string $fin): array
    {
        $inicioDt = $inicio . ' 00:00:00';
        $finDt = $fin . ' 23:59:59';

        // 1. IVA TRASLADADO (VENTAS)
        $ventasPue = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'emitido')
            ->where('tipo_comprobante', 'I')
            ->where('metodo_pago', 'PUE')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat');
            })
            ->whereBetween('fecha_emision', [$inicioDt, $finDt])
            ->get();

        $ingresosPue = (float)$ventasPue->filter(fn($v) => $v->subtotal > 0.01)->sum(fn($v) => $v->subtotal - $v->descuento);
        $ivaPue = (float)$ventasPue->sum('total_impuestos_trasladados');

        $filtroAnual = (substr($inicio, 5, 5) === '01-01' && substr($fin, 5, 5) === '12-31');
        $anioBusqueda = substr($inicio, 0, 4);

        $pagosEmitidosQuery = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'emitido')
            ->where('tipo_comprobante', 'P')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat');
            });

        if ($filtroAnual) {
            $pagosEmitidosQuery->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(complementos::jsonb->'pagos') as p WHERE p->>'fecha_pago' LIKE ?)", [$anioBusqueda . '%']);
        } else {
            $pagosEmitidosQuery->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(complementos::jsonb->'pagos') as p WHERE p->>'fecha_pago' LIKE ?)", [substr($inicio, 0, 7) . '%']);
        }
        $pagosEmitidos = $pagosEmitidosQuery->get();

        $ingresosPagos = 0; $ivaPagos = 0; $retRep = 0;
        foreach ($pagosEmitidos as $pago) {
            if (empty($pago->complementos['pagos'])) continue;
            foreach ($pago->complementos['pagos'] as $p) {
                $fechaPago = substr($p['fecha_pago'] ?? '', 0, 10);
                if ($fechaPago < $inicio || $fechaPago > $fin) continue;
                if (!empty($p['impuestos_p']['traslados'])) {
                    foreach ($p['impuestos_p']['traslados'] as $t) {
                        if (($t['impuesto'] ?? '') == '002') {
                            $basePago = (float)($t['base'] ?? 0);
                            if ($basePago > 0.01) $ingresosPagos += $basePago;
                            $ivaPagos += (float)($t['importe'] ?? 0);
                        }
                    }
                }
                if (!empty($p['impuestos_p']['retenciones'])) {
                    foreach ($p['impuestos_p']['retenciones'] as $r) {
                        if (($r['impuesto'] ?? '') == '001') {
                            $retRep += (float)($r['importe'] ?? 0);
                        }
                    }
                }
            }
        }

        $ingresosBrutos = $ingresosPue + $ingresosPagos;
        
        $detalle = $this->obtenerDetalleConciliacionIva($empresaId, $inicio, $fin);
        $ivaTrasladado = 0;
        $ivaAcreditable = 0;
        
        foreach ($detalle as $item) {
            // Ignorar filas de totales inyectadas para la UI
            if (str_starts_with($item['concepto'] ?? '', '---')) continue;
            
            $ivaTrasladado += (float)($item['iva_trasladado_xml'] ?? 0);
            $ivaAcreditable += (float)($item['iva_acreditable_xml'] ?? 0);
        }

        // 2. IVA ACREDITABLE (GASTOS)
        $gastosPue = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'recibido')
            ->where('tipo_comprobante', 'I')
            ->where(function($q) {
                $q->where('metodo_pago', 'PUE')->orWhere('estado', 'pagado');
            })
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat');
            })
            ->whereBetween('fecha_emision', [$inicioDt, $finDt])
            ->get();

        $ivaAcreditablePue = (float)$gastosPue->sum('total_impuestos_trasladados');

        $pagosRealizadosQuery = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'recibido')
            ->where('tipo_comprobante', 'P')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat');
            });

        if ($filtroAnual) {
            $pagosRealizadosQuery->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(complementos::jsonb->'pagos') as p WHERE p->>'fecha_pago' LIKE ?)", [$anioBusqueda . '%']);
        } else {
            $pagosRealizadosQuery->whereRaw("EXISTS (SELECT 1 FROM jsonb_array_elements(complementos::jsonb->'pagos') as p WHERE p->>'fecha_pago' LIKE ?)", [substr($inicio, 0, 7) . '%']);
        }
        $pagosRealizados = $pagosRealizadosQuery->get();

        $ivaAcreditablePagos = 0;
        foreach ($pagosRealizados as $pago) {
            if (empty($pago->complementos['pagos'])) continue;
            foreach ($pago->complementos['pagos'] as $p) {
                $fechaPago = substr($p['fecha_pago'] ?? '', 0, 10);
                if ($fechaPago < $inicio || $fechaPago > $fin) continue;
                if (!empty($p['impuestos_p']['traslados'])) {
                    foreach ($p['impuestos_p']['traslados'] as $t) {
                        if (($t['impuesto'] ?? '') == '002') {
                            $ivaAcreditablePagos += (float)($t['importe'] ?? 0);
                        }
                    }
                }
            }
        }

        // 3. IVA POR DEVOLUCIONES, DESCUENTOS Y BONIFICACIONES (EGRESOS RECIBIDOS)
        $egresosRecibidos = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'recibido')
            ->where('tipo_comprobante', 'E')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat');
            })
            ->whereBetween('fecha_emision', [$inicioDt, $finDt])
            ->get();
        
        $ivaDevolucionesGastos = (float)$egresosRecibidos->sum('total_impuestos_trasladados');

        $tasaIsr = 0.01;
        if ($ingresosBrutos > 208333.33) $tasaIsr = 0.025;
        else if ($ingresosBrutos > 83333.33) $tasaIsr = 0.02;
        else if ($ingresosBrutos > 50000) $tasaIsr = 0.015;
        else if ($ingresosBrutos > 25000) $tasaIsr = 0.011;

        $retPue = (float) \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where('direccion', 'emitido')
            ->where('tipo_comprobante', 'I')
            ->where('metodo_pago', 'PUE')
            ->where(function($q) {
                $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat');
            })
            ->whereBetween('fecha_emision', [$inicioDt, $finDt])
            ->sum('total_impuestos_retenidos');

        $isrResico = round($ingresosBrutos * $tasaIsr, 2);
        $isrRetenidoClientes = round($retPue + $retRep, 2);

        return [
            'trasladado' => round($ivaTrasladado, 2),
            'acreditable' => round($ivaAcreditable, 2), // Solo Ingresos
            'iva_devoluciones_gastos' => round($ivaDevolucionesGastos, 2),
            'diferencia' => round($ivaTrasladado - $ivaAcreditable + $ivaDevolucionesGastos, 2),
            'ingresos_brutos' => round($ingresosBrutos, 2),
            'isr_resico' => $isrResico,
            'isr_retenido_clientes' => $isrRetenidoClientes,
            'isr_neto_pagar' => round(max(0, $isrResico - $isrRetenidoClientes), 2),
            'tasa_isr' => $tasaIsr,
            'detalle_flujo' => [
                'informativo' => 'Saldos obtenidos directamente de archivos XML del SAT',
                'ventas_pue' => round($ingresosPue, 2),
                'ventas_pagos' => round($ingresosPagos, 2),
                'iva_acreditable_pue' => round($ivaAcreditablePue, 2),
                'iva_acreditable_pagos' => round($ivaAcreditablePagos, 2),
                'iva_devoluciones_gastos' => round($ivaDevolucionesGastos, 2),
                'retenciones_pue' => round($retPue, 2),
                'retenciones_rep' => round($retRep, 2),
            ]
        ];

    }

    public function obtenerDetalleConciliacionIva($empresaId, $inicio, $fin)
    {
        $cacheKey = "{$empresaId}|{$inicio}|{$fin}";
        if (isset(static::$detalleCache[$cacheKey])) {
            return static::$detalleCache[$cacheKey];
        }

        $breakdown = [];
        
        // 1. Pólizas del mes — eager load asientos + cuentas para evitar N+1
        $cuentasTrasladado = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
            ->where(function($q) {
                $q->where('codigo', 'like', '213%')
                  ->orWhere('codigo', 'like', '205%');
            })->pluck('id')->toArray();

        $cuentasAcreditable = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
            ->where(function($q) {
                $q->where('codigo', 'like', '118%')
                  ->orWhere('codigo', 'like', '119%');
            })->pluck('id')->toArray();

        $pols = \App\Models\Contab\PolizaContable::with(['asientos' => function($q) use ($cuentasTrasladado, $cuentasAcreditable) {
                $q->whereIn('cuenta_id', array_merge($cuentasTrasladado, $cuentasAcreditable));
            }])
            ->where('empresa_id', $empresaId)
            ->whereIn('tipo', ['ingreso', 'egreso']) // Solo flujo de efectivo
            ->whereBetween('fecha', [$inicio, $fin])
            ->get();

        foreach ($pols as $p) {
            $ivaTrasladado = 0;
            $ivaAcreditable = 0;
            $ivaDevolucion = 0;
            foreach ($p->asientos as $a) {
                if (in_array($a->cuenta_id, $cuentasTrasladado)) $ivaTrasladado += (float)$a->haber;
                if (in_array($a->cuenta_id, $cuentasAcreditable)) {
                    $ivaAcreditable += (float)$a->debe;
                    $ivaDevolucion += (float)$a->haber;
                }
            }

            if ($ivaTrasladado > 0 || $ivaAcreditable > 0 || $ivaDevolucion > 0) {
                // Si es multi-cfdi, tomamos el primer uuid para el cruce, o usamos el principal
                $uuids = is_array($p->cfdi_uuids) ? $p->cfdi_uuids : (json_decode($p->cfdi_uuids ?? '[]', true) ?: []);
                $mainUuid = $p->cfdi_uuid ?: ($uuids[0] ?? null);
                $key = $mainUuid ?: ('MANUAL-' . $p->numero);
                
                $tipo = $ivaTrasladado > 0 ? 'Ingreso' : 'Gasto';
                // Si es puramente una devolución (solo haber en 118)
                if ($ivaDevolucion > 0 && $ivaAcreditable == 0 && $ivaTrasladado == 0) {
                    $tipo = 'Devolución';
                }

                if (!isset($breakdown[$key])) {
                    $breakdown[$key] = [
                        'uuid' => $mainUuid,
                        'numero_poliza' => $p->numero,
                        'concepto' => $p->concepto,
                        'fecha_poliza' => substr($p->fecha, 0, 10),
                        'fecha_xml' => 'N/A',
                        'iva_trasladado_poliza' => round($ivaTrasladado, 2),
                        'iva_acreditable_poliza' => round($ivaAcreditable, 2),
                        'iva_devolucion_poliza' => round($ivaDevolucion, 2),
                        'iva_trasladado_xml' => 0,
                        'iva_acreditable_xml' => 0,
                        'iva_devolucion_xml' => 0,
                        'tipo' => $tipo
                    ];
                } else {
                    // Si ya existía por otro UUID (caso raro), acumulamos
                    $breakdown[$key]['numero_poliza'] .= ' + ' . $p->numero;
                    $breakdown[$key]['iva_trasladado_poliza'] += round($ivaTrasladado, 2);
                    $breakdown[$key]['iva_acreditable_poliza'] += round($ivaAcreditable, 2);
                    $breakdown[$key]['iva_devolucion_poliza'] += round($ivaDevolucion, 2);
                }
            }
        }

        // 2. XMLs del mes — filtrar por fecha_emision primero
        $cfdis = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where(function($q) { $q->where('estado_sat', '!=', 'Cancelado')->orWhereNull('estado_sat'); })
            ->where(function($q) use ($inicio, $fin) {
                $q->where(function($q2) use ($inicio, $fin) {
                    $q2->whereBetween('fecha_emision', [$inicio . ' 00:00:00', $fin . ' 23:59:59'])
                       ->where('metodo_pago', 'PUE');
                })->orWhere('tipo_comprobante', 'P') // Pagos (REP)
                  ->orWhere('tipo_comprobante', 'E'); // Notas de Crédito (Egreso)

            })
            ->get();

        // Pre-construir mapa UUID -> póliza (evita N+1 queries dentro del loop)
        $uuidPolizaMap = [];
        \App\Models\Contab\PolizaContable::where('empresa_id', $empresaId)
            ->whereNotNull('cfdi_uuids')
            ->where('cfdi_uuids', '!=', '[]')
            ->each(function($p) use (&$uuidPolizaMap) {
                $uuids = is_string($p->cfdi_uuids) ? json_decode($p->cfdi_uuids, true) : $p->cfdi_uuids;
                if (is_array($uuids)) {
                    foreach ($uuids as $uuid) {
                        $uuidPolizaMap[$uuid] = $p;
                    }
                }
            });

        foreach ($cfdis as $cfdi) {
            $ivaAc = 0;
            $ivaTr = 0;
            $esDelMes = false;

            if (($cfdi->metodo_pago === 'PUE' || $cfdi->estado === 'pagado') && substr($cfdi->fecha_emision, 0, 10) >= $inicio && substr($cfdi->fecha_emision, 0, 10) <= $fin) {
                $esDelMes = true;
            }
            if ($cfdi->tipo_comprobante === 'P' && !empty($cfdi->complementos['pagos'])) {
                foreach ($cfdi->complementos['pagos'] as $p) {
                    $fp = substr($p['fecha_pago'] ?? '', 0, 10);
                    if ($fp >= $inicio && $fp <= $fin) {
                        $esDelMes = true;
                        break;
                    }
                }
            }

            if ($esDelMes) {
                if ($cfdi->direccion === 'recibido') {
                    if ($cfdi->tipo_comprobante === 'P') {
                        foreach ($cfdi->complementos['pagos'] as $p) {
                            $fp = substr($p['fecha_pago'] ?? '', 0, 10);
                            if ($fp >= $inicio && $fp <= $fin && !empty($p['impuestos_p']['traslados'])) {
                                foreach ($p['impuestos_p']['traslados'] as $t) {
                                    if (($t['impuesto'] ?? '') == '002') $ivaAc += (float)($t['importe'] ?? 0);
                                }
                            }
                        }
                    } else if ($cfdi->tipo_comprobante === 'E') {
                        $ivaDev = (float)$cfdi->total_impuestos_trasladados;
                    } else {
                        $ivaAc = (float)$cfdi->total_impuestos_trasladados;
                    }
                } else {
                    if ($cfdi->tipo_comprobante === 'P') {
                        foreach ($cfdi->complementos['pagos'] as $p) {
                            $fp = substr($p['fecha_pago'] ?? '', 0, 10);
                            if ($fp >= $inicio && $fp <= $fin && !empty($p['impuestos_p']['traslados'])) {
                                foreach ($p['impuestos_p']['traslados'] as $t) {
                                    if (($t['impuesto'] ?? '') == '002') $ivaTr += (float)($t['importe'] ?? 0);
                                }
                            }
                        }
                    } else {
                        $ivaTr = (float)$cfdi->total_impuestos_trasladados;
                    }
                }

                if ($ivaAc > 0 || $ivaTr > 0 || ($ivaDev ?? 0) > 0) {
                    $uuid = $cfdi->uuid;
                    if (!isset($breakdown[$uuid])) {
                        $polizaMulti = $uuidPolizaMap[$uuid] ?? null;
                        $numPoliza = $polizaMulti ? $polizaMulti->numero : 'Falta Póliza';
                        $fechaPoliza = $polizaMulti ? substr($polizaMulti->fecha, 0, 10) : 'N/A';
                        $ivaTrPol = 0;
                        $ivaAcPol = 0;

                        $breakdown[$uuid] = [
                            'uuid' => $uuid,
                            'numero_poliza' => $numPoliza,
                            'concepto' => $cfdi->nombre_emisor ?? $cfdi->nombre_receptor,
                            'fecha_poliza' => $fechaPoliza,
                            'fecha_xml' => substr($cfdi->fecha_emision, 0, 10),
                            'iva_trasladado_poliza' => $ivaTrPol,
                            'iva_acreditable_poliza' => $ivaAcPol,
                            'iva_devolucion_poliza' => 0,
                            'iva_trasladado_xml' => round((float)$ivaTr, 2),
                            'iva_acreditable_xml' => round((float)$ivaAc, 2),
                            'iva_devolucion_xml' => round((float)($ivaDev ?? 0), 2),
                            'tipo' => $cfdi->tipo_comprobante === 'E' ? 'Devolución' : ($cfdi->direccion === 'recibido' ? 'Gasto' : 'Ingreso')
                        ];

                    } else {
                        $breakdown[$uuid]['fecha_xml'] = substr($cfdi->fecha_emision, 0, 10);
                        $breakdown[$uuid]['iva_trasladado_xml'] = round((float)$ivaTr, 2);
                        $breakdown[$uuid]['iva_acreditable_xml'] = round((float)$ivaAc, 2);
                        $breakdown[$uuid]['iva_devolucion_xml'] = round((float)($ivaDev ?? 0), 2);
                    }
                }
                unset($ivaDev); // Reset for next iteration
            }
        }

        $ingresos = collect($breakdown)->where('tipo', 'Ingreso')->sortBy('fecha_poliza');
        $gastos = collect($breakdown)->where('tipo', 'Gasto')->sortBy('fecha_poliza');
        $devoluciones = collect($breakdown)->where('tipo', 'Devolución')->sortBy('fecha_poliza');

        $result = [];
        foreach ($ingresos as $i) $result[] = $i;
        if ($ingresos->count() > 0) {
            $result[] = [
                'concepto' => '--- TOTAL INGRESOS ---',
                'numero_poliza' => '', 'fecha_poliza' => '', 'fecha_xml' => '', 'tipo' => 'Ingreso',
                'iva_trasladado_poliza' => round($ingresos->sum('iva_trasladado_poliza'), 2),
                'iva_acreditable_poliza' => 0, 'iva_devolucion_poliza' => 0,
                'iva_trasladado_xml' => round($ingresos->sum('iva_trasladado_xml'), 2),
                'iva_acreditable_xml' => 0, 'iva_devolucion_xml' => 0,
            ];
        }
        foreach ($gastos as $g) $result[] = $g;
        if ($gastos->count() > 0) {
            $result[] = [
                'concepto' => '--- TOTAL GASTOS ---',
                'numero_poliza' => '', 'fecha_poliza' => '', 'fecha_xml' => '', 'tipo' => 'Gasto',
                'iva_trasladado_poliza' => 0,
                'iva_acreditable_poliza' => round($gastos->sum('iva_acreditable_poliza'), 2),
                'iva_devolucion_poliza' => 0,
                'iva_trasladado_xml' => 0,
                'iva_acreditable_xml' => round($gastos->sum('iva_acreditable_xml'), 2),
                'iva_devolucion_xml' => 0,
            ];
        }
        foreach ($devoluciones as $d) $result[] = $d;
        if ($devoluciones->count() > 0) {
            $result[] = [
                'concepto' => '--- TOTAL DEVOLUCIONES ---',
                'numero_poliza' => '', 'fecha_poliza' => '', 'fecha_xml' => '', 'tipo' => 'Devolución',
                'iva_trasladado_poliza' => 0, 'iva_acreditable_poliza' => 0,
                'iva_devolucion_poliza' => round($devoluciones->sum('iva_devolucion_poliza'), 2),
                'iva_trasladado_xml' => 0, 'iva_acreditable_xml' => 0,
                'iva_devolucion_xml' => round($devoluciones->sum('iva_devolucion_xml'), 2),
            ];
        }

        static::$detalleCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Sincroniza el estado de los CFDIs vigentes con el SAT
     */
    public function sincronizarEstadosSat(int $empresaId, int $limit = 50): array
    {
        $cfdis = \App\Models\Cfdi::where('empresa_id', $empresaId)
            ->where(function($q) {
                $q->where('estado_sat', 'Vigente')
                  ->orWhereNull('estado_sat');
            })
            ->where('estatus', 'vigente')
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();

        $resultados = [
            'procesados' => 0,
            'cancelados' => 0,
            'errores' => 0,
            'detalles' => []
        ];

        foreach ($cfdis as $cfdi) {
            try {
                $status = $this->satService->consultarEstado(
                    $cfdi->uuid,
                    $cfdi->rfc_emisor,
                    $cfdi->rfc_receptor,
                    (float) $cfdi->total
                );

                $resultados['procesados']++;
                $nuevoEstado = $status['estado'] ?? '';

                if ($nuevoEstado === 'Cancelado' && $cfdi->estado_sat !== 'Cancelado') {
                    $cfdi->update([
                        'estado_sat' => 'Cancelado',
                        'datos_adicionales' => array_merge($cfdi->datos_adicionales ?? [], [
                            'cancelacion_detectada_monitor' => now()->toDateTimeString(),
                            'sat_codigo_estatus' => $status['codigo_estatus'] ?? ''
                        ])
                    ]);
                    
                    $resultados['cancelados']++;
                    $resultados['detalles'][] = [
                        'uuid' => $cfdi->uuid,
                        'folio' => $cfdi->folio,
                        'receptor' => $cfdi->receptor_nombre,
                        'total' => $cfdi->total,
                        'tipo' => $cfdi->direccion
                    ];

                    Log::channel('audit')->warning("[Monitor SAT] Factura cancelada detectada", [
                        'uuid' => $cfdi->uuid,
                        'empresa_id' => $empresaId
                    ]);
                }
                
                // Actualizar timestamp para no procesar los mismos en el siguiente batch si hay muchos
                $cfdi->touch();

            } catch (\Exception $e) {
                $resultados['errores']++;
                Log::error("[Monitor SAT] Error consultando UUID: " . $cfdi->uuid, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $resultados;
    }

    /**
     * Balance General (Activo, Pasivo, Capital)
     */
    public function obtenerBalanceGeneral(int $empresaId, ?string $fecha = null): array
    {
        $fecha = $fecha ?? now()->format('Y-m-d');
        $balanceTypes = ['activo', 'pasivo', 'capital'];

        $sums = AsientoContable::query()
            ->select('cuenta_id')
            ->selectRaw('SUM(debe) as total_debe, SUM(haber) as total_haber')
            ->whereHas('poliza', function($q) use ($fecha, $empresaId) {
                $q->where('empresa_id', $empresaId)
                  ->where('fecha', '<=', $fecha)
                  ->where('estado', '!=', 'anulada');
            })
            ->whereHas('cuenta', function($q) use ($balanceTypes) {
                $q->whereIn('tipo', $balanceTypes);
            })
            ->groupBy('cuenta_id')
            ->get()
            ->keyBy('cuenta_id');

        $cuentas = CuentaContable::where('empresa_id', $empresaId)
            ->whereIn('tipo', $balanceTypes)
            ->orderBy('codigo')
            ->get();

        foreach ($cuentas as $cta) {
            $sum = $sums->get($cta->id);
            $cta->total_debe = (float)($sum->total_debe ?? 0);
            $cta->total_haber = (float)($sum->total_haber ?? 0);
        }

        $cuentasPorNivel = $cuentas->sortByDesc('nivel');
        foreach ($cuentasPorNivel as $cta) {
            if ($cta->padre_id) {
                $padre = $cuentas->firstWhere('id', $cta->padre_id);
                if ($padre) {
                    $padre->total_debe += $cta->total_debe;
                    $padre->total_haber += $cta->total_haber;
                }
            }
        }

        $activo = [];
        $pasivo = [];
        $capital = [];

        foreach ($cuentas as $cta) {
            $saldo = ($cta->naturaleza === 'deudora')
                ? ($cta->total_debe - $cta->total_haber)
                : ($cta->total_haber - $cta->total_debe);

            if (round(abs($saldo), 2) == 0) continue;
            if (in_array((string)$cta->codigo, ['1', '2', '3'])) continue;

            $data = [
                'id' => $cta->id,
                'codigo' => $cta->codigo,
                'nombre' => $cta->nombre,
                'saldo' => $saldo,
                'nivel' => $cta->nivel,
                'es_detalle' => $cta->es_detalle,
                'padre_id' => $cta->padre_id,
            ];

            if ($cta->tipo === 'activo') $activo[] = $data;
            elseif ($cta->tipo === 'pasivo') $pasivo[] = $data;
            elseif ($cta->tipo === 'capital') $capital[] = $data;
        }

        $sumarSeccion = function($items) {
            $ids = collect($items)->pluck('id')->toArray();
            return collect($items)
                ->filter(fn($i) => !in_array($i['padre_id'], $ids))
                ->sum('saldo');
        };

        $totalActivo = $sumarSeccion($activo);
        $totalPasivo = $sumarSeccion($pasivo);
        $totalCapital = $sumarSeccion($capital);

        return [
            'secciones' => [
                ['key' => 'activo', 'titulo' => 'Activo', 'items' => $activo, 'total' => $totalActivo],
                ['key' => 'pasivo', 'titulo' => 'Pasivo', 'items' => $pasivo, 'total' => $totalPasivo],
                ['key' => 'capital', 'titulo' => 'Capital Contable', 'items' => $capital, 'total' => $totalCapital],
            ],
            'total_activo' => $totalActivo,
            'total_pasivo' => $totalPasivo,
            'total_capital' => $totalCapital,
            'total_pasivo_capital' => $totalPasivo + $totalCapital,
            'fecha' => $fecha,
        ];
    }

    /**
     * Flujo de Efectivo (Estado de Flujos de Efectivo simplificado)
     */
    public function obtenerFlujoEfectivo(int $empresaId, string $inicio, string $fin): array
    {
        $cuentasBancos = CuentaContable::where('empresa_id', $empresaId)
            ->where('codigo', 'like', '102%')
            ->pluck('id');

        $cuentasCaja = CuentaContable::where('empresa_id', $empresaId)
            ->where('codigo', 'like', '101%')
            ->pluck('id');

        $allIds = $cuentasBancos->merge($cuentasCaja);

        $saldoInicialDebe = (float) AsientoContable::whereIn('cuenta_id', $allIds)
            ->whereHas('poliza', fn($q) => $q->where('empresa_id', $empresaId)->where('fecha', '<', $inicio)->where('estado', '!=', 'anulada'))
            ->sum('debe');
        $saldoInicialHaber = (float) AsientoContable::whereIn('cuenta_id', $allIds)
            ->whereHas('poliza', fn($q) => $q->where('empresa_id', $empresaId)->where('fecha', '<', $inicio)->where('estado', '!=', 'anulada'))
            ->sum('haber');
        $saldoInicial = $saldoInicialDebe - $saldoInicialHaber;

        $polizas = PolizaContable::with(['asientos' => function($q) use ($allIds) {
                $q->whereIn('cuenta_id', $allIds);
            }])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$inicio, $fin])
            ->where('estado', '!=', 'anulada')
            ->orderBy('fecha')
            ->get();

        $ingresos = [];
        $egresos = [];

        foreach ($polizas as $p) {
            $montoEfectivo = 0;
            foreach ($p->asientos as $a) {
                $montoEfectivo += (float)$a->debe;
                $montoEfectivo -= (float)$a->haber;
            }

            $item = [
                'fecha' => $p->fecha->format('Y-m-d'),
                'numero' => $p->numero,
                'concepto' => $p->concepto,
                'tipo' => $p->tipo,
                'monto' => round($montoEfectivo, 2),
            ];

            if ($montoEfectivo >= 0) $ingresos[] = $item;
            else $egresos[] = $item;
        }

        $totalIngresos = collect($ingresos)->sum('monto');
        $totalEgresos = collect($egresos)->sum('monto');
        $flujoNeto = $totalIngresos + $totalEgresos;
        $saldoFinal = $saldoInicial + $flujoNeto;

        return [
            'saldo_inicial' => round($saldoInicial, 2),
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'total_ingresos' => round($totalIngresos, 2),
            'total_egresos' => round(abs($totalEgresos), 2),
            'flujo_neto' => round($flujoNeto, 2),
            'saldo_final' => round($saldoFinal, 2),
            'periodo' => ['inicio' => $inicio, 'fin' => $fin],
        ];
    }
}
