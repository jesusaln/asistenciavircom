<?php

namespace App\Services\Cfdi;

use App\Models\Venta;
use App\Models\Factura;
use App\Models\Cfdi;
use App\Services\SwSapienService;
use App\Services\SatConsultaDirectaService;
use App\Models\CuentasPorCobrar;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use App\Models\Contab\PolizaContable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CfdiService
{
    protected SwSapienService $swSapienService;
    protected CfdiUploadService $uploadService;
    protected SatConsultaDirectaService $satConsultaDirecta;
    protected CfdiPagoJsonBuilder $pagoJsonBuilder;

    public function __construct(
        SwSapienService $swSapienService,
        CfdiUploadService $uploadService,
        SatConsultaDirectaService $satConsultaDirecta,
        CfdiPagoJsonBuilder $pagoJsonBuilder
    ) {
        $this->swSapienService = $swSapienService;
        $this->uploadService = $uploadService;
        $this->satConsultaDirecta = $satConsultaDirecta;
        $this->pagoJsonBuilder = $pagoJsonBuilder;
    }

    /**
     * Proceso completo de facturación de un documento (Venta o Factura agrupada) mediante SW Sapien en la Nube
     */
    public function facturarVenta(Venta|Factura $documento, array $options = []): array
    {
        return $this->facturarDocumento($documento, $options);
    }

    public function facturarDocumento(Venta|Factura $documento, array $options = []): array
    {
        try {
            $isVenta = $documento instanceof Venta;

            // 1. Validar que no esté ya facturado
            if ($isVenta) {
                if ($documento->cfdis()->timbrados()->exists()) {
                    throw new \Exception("Esta venta ya cuenta con una factura timbrada.");
                }
            } else {
                if ($documento->cfdi()->where('estatus', Cfdi::ESTATUS_TIMBRADO)->exists()) {
                    throw new \Exception("Esta factura ya se encuentra timbrada.");
                }
            }

            // 2. Validar cliente
            $cliente = $documento->cliente;
            if (!$cliente) {
                throw new \Exception("El documento no tiene un cliente asociado.");
            }

            if (!$cliente->requiere_factura) {
                if (!empty($cliente->rfc)) {
                    $cliente->update(['requiere_factura' => true]);
                } else {
                    throw new \Exception(
                        "⚠️ Este cliente no requiere factura.\n\n" .
                        "Si desea facturar este documento, por favor:\n" .
                        "1. Vaya al catálogo de Clientes\n" .
                        "2. Edite el cliente \"{$cliente->nombre_razon_social}\"\n" .
                        "3. Active la casilla \"Requiere Factura\"\n" .
                        "4. Complete los datos fiscales requeridos (RFC, Régimen Fiscal, Uso CFDI, C.P. Domicilio Fiscal)\n" .
                        "5. Guarde los cambios e intente facturar nuevamente"
                    );
                }
            }

            // 3. Validar datos fiscales del cliente
            $erroresDatos = [];
            if (empty($cliente->rfc)) {
                $erroresDatos[] = "RFC";
            }
            if (empty($cliente->regimen_fiscal)) {
                $erroresDatos[] = "Régimen Fiscal";
            }
            if (empty($cliente->uso_cfdi)) {
                $erroresDatos[] = "Uso de CFDI";
            }
            if (empty($cliente->domicilio_fiscal_cp) && empty($cliente->codigo_postal)) {
                $erroresDatos[] = "Código Postal del Domicilio Fiscal";
            }

            if (!empty($erroresDatos)) {
                throw new \Exception(
                    "⚠️ El cliente \"{$cliente->nombre_razon_social}\" tiene datos fiscales incompletos.\n\n" .
                    "Faltan los siguientes campos: " . implode(", ", $erroresDatos) . ".\n\n" .
                    "Por favor complete los datos fiscales del cliente antes de facturar."
                );
            }

            // 3.5 Validaciones Pre-Timbrado
            if ($isVenta) {
                $documento->loadMissing('items.ventable');
            } else {
                $documento->loadMissing('ventas.items.ventable');
            }

            $items = $isVenta ? $documento->items : $documento->ventas->flatMap->items;
            $firstVenta = $isVenta ? $documento : $documento->ventas->first();

            $metodoPagoSat = $firstVenta->metodo_pago_sat ?? 'PUE';
            $formaPagoSat = $firstVenta->forma_pago_sat ?: ($isVenta ? $this->mapFormaPago($documento->metodo_pago) : '01');

            $this->validarPreTimbrado($metodoPagoSat, $formaPagoSat, $cliente);

            $rfcCliente = strtoupper(trim($cliente->rfc ?? ''));
            $regimenFiscal = $this->getRegimenFiscalClave($cliente->regimen_fiscal);
            $usoCfdi = $cliente->uso_cfdi ?: 'G03';

            // Reglas SAT 4.0 estrictas e inviolables para Público en General / Genérico
            if ($rfcCliente === 'XAXX010101000' || $rfcCliente === 'XEXX010101000') {
                $regimenFiscal = '616';
                $usoCfdi = 'S01';
            }

            $cpCliente = trim($cliente->domicilio_fiscal_cp ?: $cliente->codigo_postal ?: '');
            if (empty($cpCliente) || $cpCliente === '00000' || strlen($cpCliente) !== 5) {
                $empresa = \App\Models\Empresa::find($documento->empresa_id ?: 1);
                $cpCliente = trim($empresa?->codigo_postal ?: '');
                if (empty($cpCliente) || $cpCliente === '00000' || strlen($cpCliente) !== 5) {
                    $cpCliente = '83000';
                }
            }

            // 4. Construir Conceptos e Impuestos para SW Sapien
            $conceptosSW = [];
            $totalTraslados = 0;
            $ivaTasaDefault = (float) (\App\Models\EmpresaConfiguracion::getConfig()->iva_porcentaje ?? 16) / 100;

            foreach ($items as $index => $item) {
                $nombreProd = $item->ventable?->nombre ?? ($item->nombre ?? ($item->descripcion ?? 'Producto'));
                $claveSat = $item->ventable?->sat_clave_prod_serv ?? ($item->ventable?->clave_sat ?? ($item->clave_sat ?? '84111506'));
                $unidadSat = $item->ventable?->sat_clave_unidad ?? ($item->ventable?->unidad_sat ?? ($item->unidad_sat ?? 'H87'));
                $precio = (float) $item->precio;
                $cantidad = (float) $item->cantidad;
                $importe = round($precio * $cantidad, 2);
                $tasa = (float) ($item->tasa_iva ?? $ivaTasaDefault);
                $impuestoMonto = round($importe * $tasa, 2);

                $totalTraslados += $impuestoMonto;

                $conceptosSW[] = [
                    'ClaveProdServ' => $claveSat,
                    'NoIdentificacion' => 'PART-' . ($index + 1),
                    'Cantidad' => number_format($cantidad, 6, '.', ''),
                    'ClaveUnidad' => $unidadSat,
                    'Unidad' => 'Servicio',
                    'Descripcion' => substr($nombreProd, 0, 100),
                    'ValorUnitario' => number_format($precio, 6, '.', ''),
                    'Importe' => number_format($importe, 2, '.', ''),
                    'ObjetoImp' => '02',
                    'Impuestos' => [
                        'Traslados' => [
                            [
                                'Base' => number_format($importe, 2, '.', ''),
                                'Impuesto' => '002',
                                'TipoFactor' => 'Tasa',
                                'TasaOCuota' => number_format($tasa, 6, '.', ''),
                                'Importe' => number_format($impuestoMonto, 2, '.', '')
                            ]
                        ]
                    ]
                ];
            }

            $subtotalGlobal = round($documento->subtotal, 2);
            $totalGlobal = round($documento->total, 2);

            $configuracion = \App\Models\EmpresaConfiguracion::getConfig();
            $emisorRfc = config('services.sw_sapien.rfc');
            $emisorNombre = config('services.sw_sapien.emisor_nombre');
            $emisorRegimen = config('services.sw_sapien.regimen');

            if (empty($emisorRfc) || $emisorRfc === 'EKU9003173C9') {
                if (config('app.env') !== 'local' && !empty($configuracion->rfc)) {
                    $emisorRfc = $configuracion->rfc;
                    $emisorNombre = $configuracion->razon_social ?: $configuracion->nombre_empresa;
                    $emisorRegimen = $this->getRegimenFiscalClave($configuracion->regimen_fiscal);
                } else {
                    $emisorRfc = 'EKU9003173C9';
                    $emisorNombre = 'ESCUELA KEMPER URGATE';
                    $emisorRegimen = '601';
                }
            }

            $payloadSW = [
                'Version' => '4.0',
                'Serie' => 'F',
                'Folio' => (string) ($documento->id),
                'Fecha' => now()->format('Y-m-d\TH:i:s'),
                'FormaPago' => $formaPagoSat,
                'CondicionesDePago' => $metodoPagoSat === 'PUE' ? 'Contado' : 'Credito',
                'SubTotal' => number_format($subtotalGlobal, 2, '.', ''),
                'Moneda' => $documento->moneda ?: 'MXN',
                'Total' => number_format($totalGlobal, 2, '.', ''),
                'TipoDeComprobante' => 'I',
                'MetodoPago' => $metodoPagoSat,
                'LugarExpedicion' => $cpCliente,
                'Exportacion' => '01',
                'Sello' => '',
                'Certificado' => '',
                'NoCertificado' => '',
                'Emisor' => [
                    'Rfc' => $emisorRfc,
                    'Nombre' => $emisorNombre,
                    'RegimenFiscal' => $emisorRegimen
                ],
                'Receptor' => [
                    'Rfc' => $rfcCliente,
                    'Nombre' => $cliente->nombre_razon_social,
                    'DomicilioFiscalReceptor' => $cpCliente,
                    'RegimenFiscalReceptor' => $regimenFiscal,
                    'UsoCFDI' => $usoCfdi
                ],
                'Conceptos' => $conceptosSW,
                'Impuestos' => [
                    'TotalImpuestosTrasladados' => number_format($totalTraslados, 2, '.', ''),
                    'Traslados' => [
                        [
                            'Base' => number_format($subtotalGlobal, 2, '.', ''),
                            'Impuesto' => '002',
                            'TipoFactor' => 'Tasa',
                            'TasaOCuota' => '0.160000',
                            'Importe' => number_format($totalTraslados, 2, '.', '')
                        ]
                    ]
                ]
            ];

            // 5. Timbrar en la nube a través del PAC seleccionado
            $pacService = $this->getPacService();
            $res = $pacService->timbrarJson($payloadSW, $documento);

            if (!$res['success']) {
                throw new \Exception($res['message']);
            }

            // 6. Registrar en Base de Datos local
            $cfdi = $this->registerTimbradoCfdi($documento, $payloadSW, $res, $options);

            // Guardar XML en Storage
            if (!empty($res['xml_raw'])) {
                Storage::disk('public')->put("cfdis/{$res['uuid']}.xml", $res['xml_raw']);
            }

            // Actualizar estado del documento
            if (!$isVenta) {
                $documento->update(['estado' => 'enviada', 'numero_factura' => ($payloadSW['Serie'] ?? 'F') . '-' . ($payloadSW['Folio'] ?? '1')]);
            }

            $pacName = 'SW Sapien';
            return [
                'success' => true,
                'message' => "Factura timbrada y almacenada exitosamente vía {$pacName}.",
                'uuid' => $cfdi->uuid,
                'cfdi' => $cfdi
            ];

        } catch (\Exception $e) {
            Log::error("Error en CfdiService (SW Sapien): " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function resolveEmpresaIdForCfdi(Venta|Factura $documento): int
    {
        $empresaId = $documento->empresa_id ?: EmpresaResolver::resolveId();
        if (!$empresaId) {
            throw new \RuntimeException('No se pudo determinar la empresa para registrar el CFDI.');
        }
        return (int) $empresaId;
    }

    private function registerTimbradoCfdi(Venta|Factura $documento, array $payload, array $res, array $options = []): Cfdi
    {
        return DB::transaction(function () use ($documento, $payload, $res, $options) {
            $isVenta = $documento instanceof Venta;
            $empresaId = $this->resolveEmpresaIdForCfdi($documento);

            $cfdi = Cfdi::create([
                'cliente_id' => $documento->cliente_id,
                'empresa_id' => $empresaId,
                'venta_id' => $isVenta ? $documento->id : null,
                'factura_id' => !$isVenta ? $documento->id : null,
                'cfdiable_id' => $documento->id,
                'cfdiable_type' => $documento->getMorphClass(),
                'tipo_comprobante' => 'I',
                'serie' => $payload['Serie'] ?? 'F',
                'folio' => $payload['Folio'] ?? '1',
                'uuid' => $res['uuid'],
                'fecha_timbrado' => $res['fecha_timbrado'] ?? now(),
                'fecha_emision' => now(),
                'subtotal' => $documento->subtotal,
                'total_impuestos_trasladados' => $documento->iva,
                'total' => $documento->total,
                'moneda' => $documento->moneda ?: 'MXN',
                'tipo_cambio' => 1,
                'metodo_pago' => $payload['MetodoPago'] ?? 'PUE',
                'forma_pago' => $payload['FormaPago'] ?? '01',
                'uso_cfdi' => $payload['Receptor']['UsoCFDI'] ?? 'G03',
                'estatus' => Cfdi::ESTATUS_TIMBRADO,
                'estado_sat' => 'Vigente',
                'xml_url' => asset("storage/cfdis/{$res['uuid']}.xml"),
                'pdf_url' => route('facturas.pdf', $documento->id),
                'sello_sat' => $res['sello_sat'],
                'sello_cfdi' => $res['sello_cfdi'],
                'no_certificado_sat' => $res['no_certificado_sat'],
                'datos_adicionales' => [
                    'facturama_id' => $res['id'],
                    'cfdi_relacion_tipo' => $options['cfdi_relacion_tipo'] ?? null,
                    'cfdi_relacion_uuids' => $options['cfdi_relacion_uuids'] ?? null,
                ],
            ]);

            foreach ($payload['Conceptos'] as $con) {
                $cfdi->conceptos()->create([
                    'clave_prod_serv' => $con['ClaveProdServ'],
                    'cantidad' => $con['Cantidad'],
                    'clave_unidad' => $con['ClaveUnidad'],
                    'descripcion' => $con['Descripcion'],
                    'valor_unitario' => $con['ValorUnitario'],
                    'importe' => $con['Importe'],
                    'objeto_imp' => '02',
                ]);
            }

            return $cfdi;
        });
    }

    private function registerTimbradoPagoCfdi(CuentasPorCobrar $cxc, Venta $venta, array $payload, array $res, float $montoPago): Cfdi
    {
        return DB::transaction(function () use ($cxc, $venta, $payload, $res, $montoPago) {
            $empresaId = $this->resolveEmpresaIdForCfdi($venta);

            $cfdi = Cfdi::create([
                'cliente_id' => $venta->cliente_id,
                'empresa_id' => $empresaId,
                'venta_id' => $venta->id,
                'cfdiable_id' => $venta->id,
                'cfdiable_type' => $venta->getMorphClass(),
                'tipo_comprobante' => 'P',
                'direccion' => 'emitido',
                'serie' => $payload['Serie'] ?? 'P',
                'folio' => $payload['Folio'] ?? (string) time(),
                'uuid' => $res['uuid'],
                'fecha_timbrado' => $res['fecha_timbrado'] ?? now(),
                'fecha_emision' => now(),
                'rfc_emisor' => $payload['Emisor']['Rfc'] ?? null,
                'nombre_emisor' => $payload['Emisor']['Nombre'] ?? null,
                'regimen_fiscal_emisor' => $payload['Emisor']['RegimenFiscal'] ?? null,
                'rfc_receptor' => $payload['Receptor']['Rfc'] ?? null,
                'nombre_receptor' => $payload['Receptor']['Nombre'] ?? null,
                'subtotal' => 0,
                'total_impuestos_trasladados' => 0,
                'total_impuestos_retenidos' => 0,
                'total' => 0,
                'moneda' => 'MXN',
                'tipo_cambio' => 1,
                'metodo_pago' => 'PPD',
                'forma_pago' => $payload['FormaPago'] ?? '99',
                'uso_cfdi' => 'CP01',
                'estatus' => Cfdi::ESTATUS_TIMBRADO,
                'estado_sat' => 'Vigente',
                'complementos' => $payload['Complemento'] ?? null,
                'xml_url' => asset("storage/cfdis/{$res['uuid']}.xml"),
                'sello_sat' => $res['sello_sat'],
                'sello_cfdi' => $res['sello_cfdi'],
                'no_certificado_sat' => $res['no_certificado_sat'],
                'no_certificado_cfdi' => $res['no_certificado_cfdi'] ?? $payload['NoCertificado'] ?? null,
                'datos_adicionales' => [
                    'facturama_id' => $res['id'],
                    'monto_pago' => $montoPago,
                    'cxc_id' => $cxc->id,
                ],
            ]);

            foreach ($payload['Conceptos'] as $con) {
                $cfdi->conceptos()->create([
                    'clave_prod_serv' => $con['ClaveProdServ'] ?? '84111506',
                    'cantidad' => $con['Cantidad'] ?? 1,
                    'clave_unidad' => $con['ClaveUnidad'] ?? 'ACT',
                    'descripcion' => $con['Descripcion'] ?? 'Pago',
                    'valor_unitario' => $con['ValorUnitario'] ?? 0,
                    'importe' => $con['Importe'] ?? 0,
                    'objeto_imp' => $con['ObjetoImp'] ?? '01',
                ]);
            }

            return $cfdi;
        });
    }

    public function timbrarPago(CuentasPorCobrar $cxc, float $monto, string $metodoPago, Carbon $fechaPago): array
    {
        try {
            $venta = $cxc->cobrable;
            if (!$venta || !($venta instanceof Venta)) {
                throw new \Exception('La cuenta por cobrar no está asociada a una venta.');
            }

            $payload = $this->pagoJsonBuilder->build($cxc, $monto, $metodoPago, $fechaPago);
            $payloadSW = $payload['Comprobante'];

            $pacService = $this->getPacService();
            $res = $pacService->timbrarJson($payloadSW, $venta);

            if (!$res['success']) {
                throw new \Exception($res['message']);
            }

            $cfdi = $this->registerTimbradoPagoCfdi($cxc, $venta, $payloadSW, $res, $monto);

            if (!empty($res['xml_raw'])) {
                Storage::disk('public')->put("cfdis/{$res['uuid']}.xml", $res['xml_raw']);
            }

            Log::info("REP timbrado exitosamente", [
                'uuid' => $res['uuid'],
                'cxc_id' => $cxc->id,
                'venta_id' => $venta->id,
                'monto' => $monto,
            ]);

            return [
                'success' => true,
                'uuid' => $res['uuid'],
                'cfdi' => $cfdi,
                'message' => 'Complemento de pago timbrado exitosamente.',
            ];

        } catch (\Exception $e) {
            Log::error("Error en CfdiService::timbrarPago: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function facturarAnticipo(Venta $venta, float $montoTotal, string $metodoPagoInterno): array
    {
        return [
            'success' => false,
            'message' => 'Facturación de anticipos en nube lista para usar con SW Sapien.'
        ];
    }

    private function mapFormaPago(?string $metodoPagoInterno): string
    {
        if (!$metodoPagoInterno) {
            return '99';
        }

        $mapeo = [
            'efectivo' => '01',
            'cheque' => '02',
            'transferencia' => '03',
            'tarjeta_credito' => '04',
            'tarjeta_debito' => '28',
            'credito' => '99',
            'por_definir' => '99',
        ];

        return $mapeo[strtolower($metodoPagoInterno)] ?? '99';
    }

    private function validarPreTimbrado(string &$metodoPagoSat, string &$formaPagoSat, $cliente): void
    {
        $rfc = strtoupper(trim($cliente->rfc ?? ''));
        if ($rfc !== 'XAXX010101000' && $rfc !== 'XEXX010101000') {
            if (!preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z\d]{3}$/', $rfc)) {
                throw new \Exception("El RFC '{$cliente->rfc}' no tiene un formato válido para el SAT.");
            }
        }

        if ($metodoPagoSat === 'PUE' && $formaPagoSat === '99') {
            $formaPagoSat = '01'; // Default Efectivo
        }

        if ($metodoPagoSat === 'PPD' && $formaPagoSat !== '99') {
            $formaPagoSat = '99'; // Obligatorio Por Definir
        }

        $regimenCliente = $this->getRegimenFiscalClave($cliente->regimen_fiscal);
        if ($regimenCliente === '616' && $cliente->uso_cfdi !== 'S01' && $cliente->uso_cfdi !== 'CP01') {
            $cliente->uso_cfdi = 'S01';
            $cliente->save();
        }
    }

    private function getRegimenFiscalClave($regimen): string
    {
        if (empty($regimen)) {
            return '601';
        }
        if (preg_match('/^(\d{3})/', $regimen, $matches)) {
            return $matches[1];
        }
        return '601';
    }

    public function consultarEstadoSat(string $uuid, float $total, string $rfcEmisor, string $rfcReceptor): array
    {
        return $this->satConsultaDirecta->consultarEstado($uuid, $rfcEmisor, $rfcReceptor, $total);
    }

    /**
     * Solicita la cancelación de un CFDI a través de CfdiCancelService (SW Sapien)
     */
    public function cancelar(Cfdi $cfdi, string $motivo = '02', ?string $uuidSustitucion = null): array
    {
        $cancelService = app(CfdiCancelService::class);
        $res = $cancelService->cancelar($cfdi, $motivo, $uuidSustitucion);

        if ($res['success']) {
            PolizaContable::where('cfdi_uuid', $cfdi->uuid)->update(['estado' => 'anulada']);
        }

        return $res;
    }

    public function importarXml(\Illuminate\Http\UploadedFile $file, string $direccion): Cfdi
    {
        $cfdi = $this->uploadService->uploadFromXml($file);
        if ($direccion !== 'recibido') {
            $cfdi->update(['direccion' => 'emitido']);
        }
        return $cfdi;
    }

    /**
     * Obtener el servicio PAC activo (SW Sapiens).
     */
    protected function getPacService()
    {
        return $this->swSapienService;
    }
}
