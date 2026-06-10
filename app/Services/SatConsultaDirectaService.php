<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

use App\Services\Traits\WithApiCache;

class SatConsultaDirectaService
{
    use WithApiCache;

    private Client $http;
    private string $endpoint;

    public function __construct()
    {
        // Inicializar caché con prefijo 'sat_status' y TTL default de 10 min
        $this->initCache('sat_status', 10);

        $this->endpoint = config(
            'services.sat_consulta.endpoint',
            'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc'
        );

        $this->http = new Client([
            'timeout' => 30,
            'verify' => (bool) config('services.sat_consulta.verify', true),
        ]);
    }

    public function consultarEstado(string $uuid, string $rfcEmisor, string $rfcReceptor, float $total): array
    {
        return $this->apiCache->remember($uuid, now()->addMinutes(10), function () use ($uuid, $rfcEmisor, $rfcReceptor, $total) {
            return $this->execConsultarEstado($uuid, $rfcEmisor, $rfcReceptor, $total);
        });
    }

    /**
     * Consulta el estado de un RFC ante el SAT (Simulado/Básico).
     * En una implementación real, esto consultaría listas negras 69-B o APIs oficiales.
     */
    public function consultarEstadoPorRFC(string $rfc): array
    {
        return $this->apiCache->remember('rfc_'.$rfc, now()->addHours(24), function () use ($rfc) {
            // Simulación de lógica de validación
            // Podríamos integrar una descarga del listado 69-B del SAT aquí.
            
            $blacklisted = [
                'ABCD123456XYZ', // Ejemplo de RFC bloqueado
            ];

            if (in_array(strtoupper($rfc), $blacklisted)) {
                return ['status' => 'blacklisted', 'detail' => 'Encontrado en listado 69-B del SAT'];
            }

            // Para efectos de la demo, asumimos activo si el formato es correcto
            if (preg_match('/^[A-Z&Ñ]{3,4}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[A-Z0-9]{3}$/i', $rfc)) {
                return ['status' => 'active', 'detail' => 'RFC con formato válido y no reportado en listas negras'];
            }

            return ['status' => 'not_found', 'detail' => 'RFC no reconocido o con formato inválido'];
        });
    }

    protected function execConsultarEstado(string $uuid, string $rfcEmisor, string $rfcReceptor, float $total): array
    {
        $expresion = $this->buildExpresionImpresa($uuid, $rfcEmisor, $rfcReceptor, $total);
        $body = $this->buildSoapEnvelope($expresion);

        try {
            $resp = $this->http->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => 'http://tempuri.org/IConsultaCFDIService/Consulta',
                ],
                'body' => $body,
            ]);
        } catch (RequestException $e) {
            $code = $e->getResponse()?->getStatusCode() ?? 0;
            $respBody = (string) ($e->getResponse()?->getBody() ?? '');
            Log::warning('SAT consulta directa fallo', [
                'status' => $code,
                'response' => $respBody,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Error al consultar SAT directo: ' . $e->getMessage());
        }

        return $this->parseRespuesta((string) $resp->getBody());
    }

    private function buildExpresionImpresa(string $uuid, string $rfcEmisor, string $rfcReceptor, float $total): string
    {
        $totalSat = $this->formatTotalSat($total);

        return sprintf('?re=%s&rr=%s&tt=%s&id=%s', $rfcEmisor, $rfcReceptor, $totalSat, $uuid);
    }

    private function formatTotalSat(float $total): string
    {
        $formatted = number_format($total, 6, '.', '');

        return str_pad($formatted, 17, '0', STR_PAD_LEFT);
    }

    private function buildSoapEnvelope(string $expresion): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' .
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">' .
            '<soapenv:Header/>' .
            '<soapenv:Body>' .
            '<tem:Consulta>' .
            '<tem:expresionImpresa><![CDATA[' . $expresion . ']]></tem:expresionImpresa>' .
            '</tem:Consulta>' .
            '</soapenv:Body>' .
            '</soapenv:Envelope>';
    }

    private function parseRespuesta(string $xml): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xml)) {
            Log::error('Respuesta SAT directa invalida (No se pudo cargar XML)', [
                'snippet' => substr($xml, 0, 500),
                'errors' => libxml_get_errors()
            ]);
            libxml_clear_errors();
            throw new \Exception('Respuesta SAT directa invalida.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xpath->registerNamespace('t', 'http://tempuri.org/');
        $xpath->registerNamespace('a', 'http://schemas.datacontract.org/2004/07/Sat.Cfdi.Negocio.ConsultaCfdi.Servicio');

        $node = $xpath->query('//t:ConsultaResult')->item(0);
        if (!$node) {
            // Reintento sin namespace por si acaso
            $node = $xpath->query('//*[local-name()="ConsultaResult"]')->item(0);
        }

        if (!$node) {
            throw new \Exception('Respuesta SAT directa sin resultado.');
        }

        $results = [];
        $fields = [
            'Estado' => 'estado',
            'EsCancelable' => 'es_cancelable',
            'EstatusCancelacion' => 'estatus_cancelacion',
            'CodigoEstatus' => 'codigo_estatus'
        ];

        foreach ($fields as $satField => $localField) {
            $valNode = $xpath->query('.//a:' . $satField, $node)->item(0);
            if (!$valNode) {
                 $valNode = $xpath->query('.//*[local-name()="' . $satField . '"]', $node)->item(0);
            }
            $results[$localField] = $valNode ? (string) $valNode->nodeValue : '';
        }

        return $results;
    }
}
