<?php

namespace App\Services;

use App\Models\Cfdi;
use App\Models\Venta;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContpaqiBridgeService
{
    /**
     * Crea y timbra una factura usando el ContpaqiBridge
     * 
     * @param Venta $venta
     * @param array $cfdiJson
     * @return array
     */
    public function crearFactura(Venta $venta, array $cfdiJson): array
    {
        try {
            $config = EmpresaConfiguracion::getConfig();

            if (!$config->contpaqi_enabled || !$config->contpaqi_bridge_url) {
                return [
                    'success' => false,
                    'message' => 'Contpaqi Bridge no está habilitado o configurado.'
                ];
            }

            $url = rtrim($config->contpaqi_bridge_url, '/') . '/api/integracion/flujo-completo';

            $cliente = $venta->cliente;
            $itemFirst = $venta->items()->first();
            $ventable = $itemFirst ? $itemFirst->ventable : null;

            // Según indicación del usuario, el código del cliente ahora debe ser el RFC para búsqueda consistente
            $codigoCliente = $cliente->rfc;

            // Preparar payload para el Bridge (flujo-completo)
            $payload = [
                'rutaEmpresa' => 'C:\\Compac\\Empresas\\adJESUS_LOPEZ_NORIEGA',
                'cliente' => [
                    'codigo' => (string) $codigoCliente,
                    'razonSocial' => (string) $cliente->nombre_razon_social,
                    'rfc' => (string) $cliente->rfc,
                    'email' => (string) ($cliente->email ?? ''),
                    'calle' => (string) ($cliente->calle ?? ''),
                    'colonia' => (string) ($cliente->colonia ?? ''),
                    'codigoPostal' => (string) ($cliente->domicilio_fiscal_cp ?: $cliente->codigo_postal ?: '83000'),
                    'ciudad' => (string) ($cliente->ciudad ?? ''),
                    'estado' => (string) ($cliente->estado ?? ''),
                    'pais' => (string) ($cliente->pais ?? 'MX'),
                    // ✅ Datos fiscales del cliente (pueden venir del modal/cfdiJson)
                    'regimenFiscal' => (string) ($cfdiJson['Comprobante']['Receptor']['RegimenFiscalReceptor'] ?? $cliente->regimen_fiscal ?? '616'),
                    'usoCFDI' => (string) ($cfdiJson['Comprobante']['Receptor']['UsoCFDI'] ?? $cliente->uso_cfdi ?? 'G03'),
                    // ✅ FormaPago en cliente: El Bridge usa esto para actualizar el cliente en Contpaqi
                    'formaPago' => str_pad((string) ($cfdiJson['Comprobante']['FormaPago'] ?? $venta->forma_pago ?? '01'), 2, '0', STR_PAD_LEFT),
                ],
                'producto' => [
                    'codigo' => (string) ($ventable->codigo ?? 'PROD-001'),
                    'nombre' => (string) ($ventable->nombre ?? 'Servicio General'),
                    'descripcion' => (string) ($ventable->descripcion ?? $ventable->nombre ?? ''),
                    'precio' => (double) ($itemFirst->precio ?? $venta->subtotal),
                    'claveSAT' => (string) ($ventable->sat_clave_prod_serv ?? '81111504'),
                    'unidadMedida' => (string) ($ventable->sat_clave_unidad ?? 'H87'),
                    // ✅ IVA del producto (16%)
                    'tasaIVA' => 16.0,
                ],
                'factura' => [
                    'codigoConcepto' => '4',
                    'passCSD' => 'ANAHID2188',
                    'cantidad' => (double) ($itemFirst->cantidad ?? 1),
                    // ✅ IVA total de la venta
                    'iva' => (double) ($venta->iva ?? 0),
                    'subtotal' => (double) ($venta->subtotal ?? 0),
                    'total' => (double) ($venta->total ?? 0),
                    // ✅ PULL DYNAMIC FIELDS from cfdiJson (passed from VentaController/Modal)
                    'usoCFDI' => (string) ($cfdiJson['Comprobante']['Receptor']['UsoCFDI'] ?? $venta->uso_cfdi ?? 'G03'),
                    'formaPago' => str_pad((string) ($cfdiJson['Comprobante']['FormaPago'] ?? $venta->forma_pago ?? '01'), 2, '0', STR_PAD_LEFT),
                    'metodoPago' => (string) ($cfdiJson['Comprobante']['MetodoPago'] ?? $venta->metodo_pago ?? 'PUE'),
                ]
            ];

            Log::info('ContpaqiBridge: Enviando Payload Completo', [
                'venta_id' => $venta->id,
                'payload' => $payload
            ]);

            $response = Http::timeout(90)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('ContpaqiBridge: Respuesta recibida', ['data' => $data]);

                // Normalizar keys de respuesta (el Bridge puede devolver success o Success)
                $isSuccess = $data['success'] ?? $data['Success'] ?? false;
                $message = $data['message'] ?? $data['Message'] ?? 'Operación completada';
                $ids = $data['ids'] ?? $data['IDs'] ?? $data['iDs'] ?? null;
                $detalles = $data['detalles'] ?? [];

                // Validar si el timbrado falló explícitamente aunque se haya creado el documento
                if (isset($detalles['timbrado']) && strtoupper($detalles['timbrado']) === 'FALLIDO') {
                    Log::warning('ContpaqiBridge: Documento creado pero timbrado fallido.', ['message' => $message]);
                    return [
                        'success' => false,
                        'message' => $message // Retornar el mensaje original que contiene el error de timbrado
                    ];
                }

                // El mensaje del usuario indica que puede decir "Factura TIMBRADA exitosamente"
                if ($isSuccess || str_contains(strtoupper($message), 'TIMBRADA')) {

                    // Extraer Serie y Folio con tolerancia a mayúsculas/minúsculas
                    $serie = $ids['serie'] ?? $ids['Serie'] ?? null;
                    $folio = $ids['folio'] ?? $ids['Folio'] ?? null;

                    Log::info("ContpaqiBridge: Extrayendo Serie=$serie, Folio=$folio para búsqueda de XML.");

                    $resultData = [
                        'serie' => $serie,
                        'folio' => $folio,
                        'fechaTimbrado' => now()->toDateTimeString(),
                    ];

                    // Intentar obtener el XML si tenemos lo necesario
                    if ($serie && $folio) {
                        try {
                            $urlXml = rtrim($config->contpaqi_bridge_url, '/') . '/api/Documentos/xml';

                            // Reintentos breves por si el Bridge tarda en liberar el archivo
                            for ($i = 0; $i < 2; $i++) {
                                Log::info("ContpaqiBridge: Intento " . ($i + 1) . " de obtener XML...");
                                $resXml = Http::timeout(20)->get($urlXml, [
                                    'rutaEmpresa' => 'C:\\Compac\\Empresas\\adJESUS_LOPEZ_NORIEGA',
                                    'codigoConcepto' => '4',
                                    'serie' => $serie,
                                    'folio' => $folio
                                ]);

                                if ($resXml->successful()) {
                                    $xmlData = $resXml->json();
                                    $xmlSuccess = $xmlData['success'] ?? $xmlData['Success'] ?? false;
                                    if ($xmlSuccess && !empty($xmlData['xml'])) {
                                        $resultData['xml'] = base64_encode($xmlData['xml']);
                                        $resultData['uuid'] = $this->extractUuid($xmlData['xml']);
                                        Log::info("ContpaqiBridge: XML obtenido con éxito. UUID: " . ($resultData['uuid'] ?? 'No encontrado'));
                                        break;
                                    } else {
                                        Log::warning("ContpaqiBridge: El Bridge respondió pero sin XML: " . ($xmlData['message'] ?? $xmlData['Message'] ?? 'Sin mensaje'));
                                    }
                                } else {
                                    Log::warning("ContpaqiBridge: Error HTTP al obtener XML: " . $resXml->status());
                                }
                                if ($i == 0)
                                    sleep(1);
                            }
                        } catch (\Exception $e) {
                            Log::warning("ContpaqiBridge: Excepción al recuperar XML: " . $e->getMessage());
                        }
                    } else {
                        Log::warning("ContpaqiBridge: No se puede buscar XML porque falta Serie o Folio en la respuesta.", ['ids' => $ids]);
                    }

                    return [
                        'success' => true,
                        'message' => $message,
                        'data' => $resultData
                    ];
                }

                return [
                    'success' => false,
                    'message' => $message
                ];
            }

            return [
                'success' => false,
                'message' => 'Error HTTP Bridge: ' . $response->status() . ' - ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('ContpaqiBridge CrearFactura Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Excepción: ' . $e->getMessage()];
        }
    }

    /**
     * Extrae el UUID de un XML de CFDI
     */
    private function extractUuid(string $xml): ?string
    {
        if (preg_match('/UUID="([^"]+)"/i', $xml, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Cancela una factura usando el ContpaqiBridge
     * 
     * @param Cfdi $cfdi
     * @param string $motivo (01, 02, 03, 04)
     * @param string|null $uuidSustitucion
     * @return array
     */
    public function cancelarFactura(Cfdi $cfdi, string $motivo = '02', ?string $uuidSustitucion = null): array
    {
        try {
            $config = EmpresaConfiguracion::getConfig();

            if (!$config->contpaqi_enabled) {
                return [
                    'success' => false,
                    'message' => 'La integración con Contpaqi Bridge no está habilitada.'
                ];
            }

            if (!$config->contpaqi_bridge_url) {
                return [
                    'success' => false,
                    'message' => 'No se ha configurado la URL del Contpaqi Bridge.'
                ];
            }

            $url = rtrim($config->contpaqi_bridge_url, '/') . '/api/Documentos/cancelar';

            $payload = [
                'rutaEmpresa' => $config->contpaqi_ruta_empresa,
                'codigoConcepto' => $config->contpaqi_codigo_concepto,
                'serie' => $cfdi->serie,
                'folio' => (int) $cfdi->folio,
                'motivoCancelacion' => $motivo,
                'passCSD' => $config->csd_password,
                'uuidSustitucion' => $uuidSustitucion ?? '',
            ];

            Log::info('ContpaqiBridge: Intentando cancelar factura', [
                'uuid' => $cfdi->uuid,
                'url' => $url,
                'payload' => array_merge($payload, ['passCSD' => '********'])
            ]);

            $response = Http::timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    return [
                        'success' => true,
                        'message' => $data['message'] ?? 'Factura cancelada exitosamente en Contpaqi y SAT.',
                        'data' => $data
                    ];
                }

                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'El puente respondió con un error desconocido.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Error de conexión con Contpaqi Bridge: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('ContpaqiBridge Error: ' . $e->getMessage(), [
                'uuid' => $cfdi->uuid,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno al procesar la cancelación: ' . $e->getMessage()
            ];
        }
    }
}
