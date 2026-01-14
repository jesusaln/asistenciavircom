<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class SatConsultaDirectaService
{
    private Client $http;
    private string $endpoint;

    public function __construct()
    {
        $this->endpoint = config(
            'services.sat_consulta.endpoint',
            'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc'
        );

        $this->http = new Client([
            'timeout' => 20,
            'verify' => (bool) config('services.sat_consulta.verify', true),
        ]);
    }

    public function consultarEstado(string $uuid, string $rfcEmisor, string $rfcReceptor, float $total): array
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
        $doc = simplexml_load_string($xml);
        if (!$doc) {
            throw new \Exception('Respuesta SAT directa invalida.');
        }

        $doc->registerXPathNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
        $doc->registerXPathNamespace('a', 'http://tempuri.org/');

        $nodes = $doc->xpath('//a:ConsultaResult');
        if (!$nodes || !isset($nodes[0])) {
            throw new \Exception('Respuesta SAT directa sin resultado.');
        }

        $attrs = $nodes[0]->attributes();

        return [
            'estado' => (string) ($attrs['Estado'] ?? ''),
            'es_cancelable' => (string) ($attrs['EsCancelable'] ?? ''),
            'estatus_cancelacion' => (string) ($attrs['EstatusCancelacion'] ?? ''),
            'codigo_estatus' => (string) ($attrs['CodigoEstatus'] ?? ''),
        ];
    }
}
