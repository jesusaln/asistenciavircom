<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ContpaqiService
{
    protected $baseUrl;
    protected $rutaEmpresa;
    protected $passCSD;
    protected $conceptCode;
    protected $conceptCodePago;
    protected $conceptCodeAnticipo;

    public function __construct(
        protected \App\Services\CfdiXmlParserService $xmlParser
    ) {
        $this->baseUrl = config('services.contpaqi.url');
        $this->rutaEmpresa = config('services.contpaqi.ruta_empresa');
        $this->conceptCode = config('services.contpaqi.concept_code', '4');
        $this->conceptCodePago = config('services.contpaqi.concept_code_pago', '100');
        $this->conceptCodeAnticipo = config('services.contpaqi.concept_code_anticipo', '4');
        $this->passCSD = config('services.contpaqi.csd_pass');
    }

    /**
     * Proceso completo para enviar una venta a CONTPAQi y timbrarla
     * 
     * @param Venta $venta
     * @return array Resultado de la operación
     */
    public function procesarVenta(Venta $venta)
    {
        try {
            // 1. Sincronizar Cliente
            $this->syncCliente($venta->cliente);

            // 2. Sincronizar Productos/Servicios
            foreach ($venta->items as $item) {
                if ($item->ventable) {
                    $this->syncItem($item->ventable);
                }
            }

            // 3. Crear Documento (Factura)
            $documentoResponse = $this->crearFactura($venta);

            $folio = $documentoResponse['folio'] ?? null;
            $serie = $documentoResponse['serie'] ?? null;

            if (!$folio && isset($documentoResponse['message'])) {
                if (preg_match('/Folio: (\d+)/', $documentoResponse['message'], $matches)) {
                    $folio = $matches[1];
                }
                if (preg_match('/Serie: ([A-Z]*)/', $documentoResponse['message'], $matches)) {
                    $serie = $matches[1];
                }
            }

            if (!$folio) {
                throw new Exception("La factura se creó pero no se pudo obtener el folio. Respuesta: " . json_encode($documentoResponse));
            }

            // 4. Timbrar
            $timbreResponse = [];
            if (!empty($this->passCSD)) {
                $timbreResponse = $this->timbrarFactura($folio, $serie);
            }

            // 5. Vincular localmente (Marcar como Facturada)
            // Buscamos o creamos un registro en la tabla facturas
            $factura = \App\Models\Factura::updateOrCreate(
                ['empresa_id' => $venta->empresa_id, 'folio' => (int) $folio],
                [
                    'numero_factura' => ($serie ?? '') . $folio,
                    'cliente_id' => $venta->cliente_id,
                    'subtotal' => $venta->subtotal,
                    'iva' => $venta->iva,
                    'total' => $venta->total,
                    'fecha_emision' => now(),
                    'estado' => 'pagada'
                ]
            );

            $venta->update(['factura_id' => $factura->id]);

            // 6. Intentar obtener XML para generar PDF local (Requiere Bridge actualizado)
            $cfdi = null;
            try {
                $xmlRes = $this->getDocumentoXml($folio, $serie);
                if (isset($xmlRes['success']) && $xmlRes['success'] && !empty($xmlRes['xml'])) {
                    $cfdi = $this->procesarXmlEmitido($xmlRes['xml'], $venta, $factura);
                    Log::info("XML obtenido y procesado para folio {$folio}");
                }
            } catch (\Exception $e) {
                Log::warning("No se pudo extraer el XML automáticamente: " . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Venta procesada y vinculada exitosamente',
                'folio' => $folio,
                'factura_id' => $factura->id,
                'cfdi' => $cfdi,
                'timbre' => $timbreResponse
            ];

        } catch (Exception $e) {
            Log::error("Error procesando venta CONTPAQi [ID: {$venta->id}]: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function procesarPago(\App\Models\CuentasPorCobrar $cxc, float $monto, string $metodoPago, \Carbon\Carbon $fechaPago)
    {
        try {
            if (!$cxc->cobrable || !$cxc->cobrable instanceof Venta) {
                throw new Exception("El pago debe ser de una Venta para emitir REP.");
            }
            $venta = $cxc->cobrable;

            // 1. Sincronizar Cliente
            $this->syncCliente($venta->cliente);

            // 2. Crear Documento de Pago
            $payload = [
                "rutaEmpresa" => $this->rutaEmpresa,
                "codigoConcepto" => $this->conceptCodePago,
                "codigoCliente" => $this->getClienteCodigo($venta->cliente),
                "fecha" => $fechaPago->format('Y-m-d\TH:i:s'),
                "monto" => $monto,
                "formaPago" => $metodoPago,
                "documentosAPagar" => [
                    [
                        "serie" => $venta->factura ? $venta->factura->serie : '',
                        "folio" => $venta->factura ? $venta->factura->folio : $venta->folio,
                        "monto" => $monto
                    ]
                ]
            ];

            Log::info("CONTPAQi: Creando pago", ['payload' => $payload]);
            $response = Http::timeout(30)->post("{$this->baseUrl}/api/Documentos/pago", $payload);

            if ($response->failed()) {
                throw new Exception("Error creando pago (HTTP {$response->status()}): " . $response->body());
            }

            $documentoResponse = $response->json();
            $folio = $documentoResponse['folio'] ?? null;
            $serie = $documentoResponse['serie'] ?? null;

            if (!$folio) {
                if (preg_match('/Folio: (\d+)/', $documentoResponse['message'] ?? '', $matches)) {
                    $folio = $matches[1];
                }
            }

            if (!$folio) {
                throw new Exception("El pago se creó pero no se pudo obtener el folio.");
            }

            // 3. Timbrar
            $timbreResponse = $this->timbrarFactura($folio, $serie, $this->conceptCodePago);

            // 4. Obtener XML y procesar CFDI
            $cfdi = null;
            try {
                $xmlRes = $this->getDocumentoXml($folio, $serie, $this->conceptCodePago);
                if (isset($xmlRes['success']) && $xmlRes['success'] && !empty($xmlRes['xml'])) {
                    // Usamos la misma lógica para procesar XML, pero indicando que es pago
                    $cfdi = $this->procesarXmlEmitido($xmlRes['xml'], $venta);
                }
            } catch (Exception $e) {
                Log::warning("No se pudo obtener el XML del pago: " . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Pago procesado exitosamente',
                'folio' => $folio,
                'cfdi' => $cfdi,
                'timbre' => $timbreResponse
            ];

        } catch (Exception $e) {
            Log::error("Error procesando pago CONTPAQi: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function facturarAnticipo(Venta $venta, float $montoTotal, string $metodoPagoInterno)
    {
        try {
            $this->syncCliente($venta->cliente);

            $payload = [
                "rutaEmpresa" => $this->rutaEmpresa,
                "codigoConcepto" => $this->conceptCodeAnticipo,
                "codigoCliente" => $this->getClienteCodigo($venta->cliente),
                "fecha" => now()->format('Y-m-d\TH:i:s'),
                "monto" => $montoTotal,
                "formaPago" => $metodoPagoInterno,
                "usoCFDI" => "CP01",
                "esAnticipo" => true
            ];

            Log::info("CONTPAQi: Creando Factura Anticipo", ['payload' => $payload]);
            $response = Http::timeout(30)->post("{$this->baseUrl}/api/Documentos/anticipo", $payload);

            if ($response->failed()) {
                throw new Exception("Error creando anticipo (HTTP {$response->status()}): " . $response->body());
            }

            $documentoResponse = $response->json();
            $folio = $documentoResponse['folio'] ?? null;
            $serie = $documentoResponse['serie'] ?? null;

            if (!$folio)
                throw new Exception("No se obtuvo folio del anticipo.");

            $timbreResponse = $this->timbrarFactura($folio, $serie, $this->conceptCodeAnticipo);

            $cfdi = null;
            try {
                $xmlRes = $this->getDocumentoXml($folio, $serie, $this->conceptCodeAnticipo);
                if (!empty($xmlRes['xml'])) {
                    $cfdi = $this->procesarXmlEmitido($xmlRes['xml'], $venta);
                }
            } catch (Exception $e) {
            }

            return [
                'success' => true,
                'folio' => $folio,
                'cfdi' => $cfdi,
                'timbre' => $timbreResponse
            ];

        } catch (Exception $e) {
            Log::error("Error facturando anticipo CONTPAQi: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envia o actualiza el cliente en CONTPAQi
     */
    public function syncCliente(Cliente $cliente)
    {
        $payload = [
            "rutaEmpresa" => $this->rutaEmpresa,
            "codigo" => $this->getClienteCodigo($cliente),
            "razonSocial" => $cliente->nombre_razon_social,
            "rfc" => $cliente->rfc,
            "email" => $cliente->email ?? 'sin_correo@ejemplo.com',
            "calle" => $cliente->calle ?? '.',
            "colonia" => $cliente->colonia ?? '.',
            "codigoPostal" => $cliente->codigo_postal ?? '00000',
            "ciudad" => $cliente->municipio ?? '.',
            "estado" => $cliente->estado ?? '.',
            "pais" => $cliente->pais ?? 'MEXICO',
            "regimenFiscal" => $cliente->regimen_fiscal ?? '601',
            "usoCFDI" => $cliente->uso_cfdi ?? 'G03',
            "formaPago" => $cliente->forma_pago_default ?? '99'
        ];

        Log::info("CONTPAQi: Sincronizando cliente", ['payload' => $payload]);
        $response = Http::timeout(15)->post("{$this->baseUrl}/api/Clientes", $payload);

        if ($response->failed()) {
            throw new Exception("Error sincronizando cliente (HTTP {$response->status()}): " . $response->body());
        }

        $resJson = $response->json();
        Log::info("CONTPAQi: Respuesta sync cliente", ['response' => $resJson]);

        return $resJson;
    }

    public function syncItem($item)
    {
        // Determinar tipo
        $esServicio = $item instanceof Servicio;

        $payload = [
            "rutaEmpresa" => $this->rutaEmpresa,
            "codigo" => $item->codigo,
            "nombre" => substr($item->nombre, 0, 60),
            "descripcion" => $item->descripcion ?? $item->nombre,
            "precio" => (float) ($item->precio_venta ?? 0),
            "tipoProducto" => $esServicio ? 3 : 1, // 1=Producto, 3=Servicio
            "claveSAT" => $item->sat_clave_prod_serv ?? '01010101',
            "unidadMedida" => $item->sat_clave_unidad ?? ($esServicio ? 'E48' : 'H87')
        ];

        Log::info("CONTPAQi: Sincronizando producto", ['payload' => $payload]);
        $response = Http::timeout(15)->post("{$this->baseUrl}/api/Productos", $payload);

        if ($response->failed()) {
            Log::warning("CONTPAQi: Error sincronizando producto {$item->codigo}: " . $response->body());
        }

        return $response->json();
    }

    public function crearFactura(Venta $venta)
    {
        $productosPayload = [];

        foreach ($venta->items as $item) {
            $ventable = $item->ventable;
            if (!$ventable)
                continue;

            $productosPayload[] = [
                "codigo" => $ventable->codigo,
                "cantidad" => (float) $item->cantidad,
                "precio" => (float) $item->precio,
            ];
        }

        $codigoCliente = $this->getClienteCodigo($venta->cliente);

        // Formatear fecha correctamente
        $fecha = $venta->fecha;
        if (!($fecha instanceof \Carbon\Carbon)) {
            $fecha = \Illuminate\Support\Carbon::parse($fecha);
        }

        $payload = [
            "rutaEmpresa" => $this->rutaEmpresa,
            "codigoConcepto" => $this->conceptCode,
            "codigoCliente" => $codigoCliente,
            "fecha" => $fecha->format('Y-m-d\TH:i:s'),
            "productos" => $productosPayload,
            "formaPago" => $venta->forma_pago_sat ?? '99',
            "metodoPago" => $venta->metodo_pago_sat ?? 'PUE',
        ];

        Log::info("CONTPAQi: Creando factura", ['payload' => $payload]);

        $response = Http::timeout(30)->post("{$this->baseUrl}/api/Documentos/factura", $payload);

        if ($response->failed()) {
            throw new Exception("Error creando factura (HTTP {$response->status()}): " . $response->body());
        }

        $resJson = $response->json();
        if (isset($resJson['success']) && !$resJson['success']) {
            throw new Exception("Error creando factura (Bridge): " . ($resJson['message'] ?? 'Desconocido'));
        }

        return $resJson; // Debería retornar { "folio": 1234, "uuid": ... }
    }

    /**
     * Timbra una factura existente por folio
     */
    public function timbrarFactura($folio, $serie = '', $conceptCode = null)
    {
        $payload = [
            "rutaEmpresa" => $this->rutaEmpresa,
            "codigoConcepto" => $conceptCode ?: $this->conceptCode,
            "serie" => $serie,
            "folio" => (int) $folio,
            "passCSD" => $this->passCSD
        ];

        // Endpoint 2B: POST /api/Documentos/timbrar
        $response = Http::timeout(60)->post("{$this->baseUrl}/api/Documentos/timbrar", $payload);

        if ($response->failed()) {
            throw new Exception("Error timbrando factura Folio {$folio}: " . $response->body());
        }

        return $response->json();
    }
    /**
     * Obtiene el XML de un documento timbrado desde el Bridge.
     */
    public function getDocumentoXml($folio, $serie = '', $conceptCode = null)
    {
        $params = [
            "rutaEmpresa" => $this->rutaEmpresa,
            "codigoConcepto" => $conceptCode ?: $this->conceptCode,
            "serie" => $serie,
            "folio" => (int) $folio
        ];

        Log::info("CONTPAQi: Solicitando XML", $params);
        $response = Http::timeout(20)->get("{$this->baseUrl}/api/Documentos/xml", $params);

        if ($response->failed()) {
            throw new Exception("Error obteniendo XML (HTTP {$response->status()}): " . $response->body());
        }

        return $response->json();
    }

    /**
     * Obtiene el código del cliente para CONTPAQi, generando uno si no tiene.
     */
    private function getClienteCodigo(Cliente $cliente)
    {
        if (!empty($cliente->codigo)) {
            return $cliente->codigo;
        }

        // Caso especial: Público en General
        if (strtoupper($cliente->nombre_razon_social) === 'PÚBLICO EN GENERAL' || $cliente->rfc === 'XAXX010101000') {
            return 'PG';
        }

        // Generar código basado en ID: CTE_00010
        return 'CTE_' . str_pad($cliente->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica estado de conexión
     */
    /**
     * Procesa el contenido XML de una factura emitida y crea el registro CFDI
     */
    private function procesarXmlEmitido(string $xmlContent, Venta $venta, \App\Models\Factura $factura = null)
    {
        $data = $this->xmlParser->parseCfdiXml($xmlContent);

        if (empty($data['uuid'])) {
            throw new Exception("El XML obtenido no tiene UUID.");
        }

        // Guardar archivo XML
        $date = !empty($data['fecha']) ? \Carbon\Carbon::parse($data['fecha']) : now();
        $year = $date->format('Y');
        $month = $date->format('m');
        $directory = "cfdis/emitidos/{$year}/{$month}";
        $filename = "{$data['uuid']}.xml";

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($directory);
        $xmlPath = "{$directory}/{$filename}";
        \Illuminate\Support\Facades\Storage::disk('public')->put($xmlPath, $xmlContent);

        // Crear registro CFDI
        $cfdi = \App\Models\Cfdi::updateOrCreate(
            ['uuid' => $data['uuid']],
            [
                'venta_id' => $venta->id,
                'cliente_id' => $venta->cliente_id,
                'empresa_id' => $venta->empresa_id,
                'direccion' => \App\Models\Cfdi::DIRECCION_EMITIDO,
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'I',
                'serie' => $data['serie'] ?? null,
                'folio' => $data['folio'] ?? null,
                'rfc_emisor' => $data['emisor']['rfc'] ?? null,
                'nombre_emisor' => $data['emisor']['nombre'] ?? null,
                'regimen_fiscal_emisor' => $data['emisor']['regimen_fiscal'] ?? null,
                'rfc_receptor' => $data['receptor']['rfc'] ?? null,
                'nombre_receptor' => $data['receptor']['nombre'] ?? null,
                'fecha_emision' => $date,
                'fecha_timbrado' => !empty($data['timbre']['fecha_timbrado']) ? \Carbon\Carbon::parse($data['timbre']['fecha_timbrado']) : null,
                'subtotal' => $data['subtotal'] ?? 0,
                'descuento' => $data['descuento'] ?? 0,
                'total' => $data['total'] ?? 0,
                'total_impuestos_trasladados' => $data['impuestos']['total_impuestos_trasladados'] ?? 0,
                'total_impuestos_retenidos' => $data['impuestos']['total_impuestos_retenidos'] ?? 0,
                'moneda' => $data['moneda'] ?? 'MXN',
                'tipo_cambio' => $data['tipo_cambio'] ?? 1,
                'forma_pago' => $data['forma_pago'] ?? null,
                'metodo_pago' => $data['metodo_pago'] ?? null,
                'uso_cfdi' => $data['receptor']['uso_cfdi'] ?? null,
                'xml_url' => $xmlPath,
                'estatus' => \App\Models\Cfdi::ESTATUS_VIGENTE,
                'datos_adicionales' => [
                    'comprobante' => $data,
                    'is_contpaqi' => true
                ]
            ]
        );

        // Guardar conceptos si no existen
        if ($cfdi->conceptos()->count() === 0) {
            foreach ($data['conceptos'] as $concepto) {
                $cfdi->conceptos()->create([
                    'clave_prod_serv' => $concepto['clave_prod_serv'] ?? null,
                    'no_identificacion' => $concepto['no_identificacion'] ?? null,
                    'cantidad' => $concepto['cantidad'] ?? 1,
                    'clave_unidad' => $concepto['clave_unidad'] ?? null,
                    'unidad' => $concepto['unidad'] ?? null,
                    'descripcion' => $concepto['descripcion'] ?? '',
                    'valor_unitario' => $concepto['valor_unitario'] ?? 0,
                    'importe' => $concepto['importe'] ?? 0,
                    'descuento' => $concepto['descuento'] ?? 0,
                    'objeto_imp' => $concepto['objeto_imp'] ?? '02',
                ]);
            }
        }

        return $cfdi;
    }

    /**
     * Cancela una factura en CONTPAQi
     * 
     * @param string $uuid UUID de la factura a cancelar
     * @param string $motivoCancelacion Motivo SAT: 01, 02, 03, 04
     * @param string|null $folioSustitucion UUID de la factura de sustitucion (solo para motivo 01)
     * @return array
     */
    public function cancelarFactura(string $uuid, string $motivoCancelacion = '02', ?string $folioSustitucion = null)
    {
        $payload = [
            "rutaEmpresa" => $this->rutaEmpresa,
            "uuid" => strtoupper($uuid),
            "motivoCancelacion" => $motivoCancelacion,
            "passCSD" => $this->passCSD,
        ];

        if ($motivoCancelacion === '01' && $folioSustitucion) {
            $payload['folioSustitucion'] = strtoupper($folioSustitucion);
        }

        Log::info("CONTPAQi: Solicitando cancelacion", ['uuid' => $uuid, 'motivo' => $motivoCancelacion]);

        $response = Http::timeout(60)->post("{$this->baseUrl}/api/Documentos/cancelar", $payload);

        if ($response->failed()) {
            throw new Exception("Error cancelando factura (HTTP {$response->status()}): " . $response->body());
        }

        $result = $response->json();

        if (isset($result['success']) && !$result['success']) {
            throw new Exception("Error cancelando factura (Bridge): " . ($result['message'] ?? 'Desconocido'));
        }

        return $result;
    }

    public function checkStatus()
    {
        try {
            return Http::timeout(5)->get("{$this->baseUrl}/api/Status")->json();
        } catch (Exception $e) {
            return ['status' => 'Offline', 'error' => $e->getMessage()];
        }
    }
}
