<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class FacturaLOPlus
{
    private Client $http;
    private string $apikey;

    public function __construct()
    {
        $config = \App\Models\EmpresaConfiguracion::getConfig();
        
        // Determinar URL base según el modo seleccionado
        if ($config->pac_produccion) {
            $baseUrl = 'https://api.facturaloplus.com/api/rest/servicio/';
        } else {
            $baseUrl = 'https://dev.facturaloplus.com/api/rest/servicio/';
        }
        
        // Sobrescribir si hay una URL personalizada (opcional)
        if ($config->pac_base_url) {
            $baseUrl = $config->pac_base_url;
        }

        $this->apikey = $config->pac_apikey ?: config('services.facturaloplus.apikey');

        // ✅ SECURITY FIX: Enable SSL verification in production
        // Only disable for local development where certificates may not be installed
        $verifySsl = !in_array(config('app.env'), ['local', 'testing']);

        $this->http = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout'  => 30,
            'verify'   => $verifySsl,
        ]);
    }

    private function post(string $endpoint, array $data): array
    {
        try {
            // Depuración de API Key (longitud y primeros caracteres)
            $ak = $data['apikey'] ?? '';
            \Log::debug("PAC Request to: {$endpoint}", [
                'apikey_len' => strlen($ak),
                'apikey_start' => substr($ak, 0, 4) . '...',
                'payload_keys' => array_keys($data)
            ]);

            // El PAC espera application/x-www-form-urlencoded según su documentación/Postman
            $resp = $this->http->post($endpoint, ['form_params' => $data]);
            $body = (string) $resp->getBody();
            
            \Log::debug("PAC Response from: {$endpoint}", [
                'status' => $resp->getStatusCode(),
                'json'   => json_decode($body, true)
            ]);

            return [
                'status' => $resp->getStatusCode(),
                'json'   => json_decode($body, true),
            ];
        } catch (RequestException $e) {
            $code = $e->getResponse()?->getStatusCode() ?? 0;
            $body = (string) ($e->getResponse()?->getBody() ?? '');
            
            \Log::error("PAC Request Error: {$endpoint}", [
                'status' => $code,
                'response' => $body
            ]);

            return ['status' => $code, 'json' => $body ? json_decode($body, true) : ['message' => $e->getMessage()]];
        }
    }

    /** TIMBRADO: JSON -> XML timbrado */
    public function timbrarJSON(string $jsonCfdi, string $cerPem, string $keyPem, string $passCsd): array
    {
        $payload = [
            'apikey' => $this->apikey,
            // jsonB64 debe ir en BASE64 (ver guía de "Utilerías")
            'jsonB64' => base64_encode($jsonCfdi),
            'cerPEM' => $cerPem,
            'keyPEM' => $keyPem,
            'passcsd' => $passCsd,
        ];
        return $this->post('timbrarJSON', $payload);
    }

    /** TIMBRADO: JSON -> XML timbrado (Versión 2 según guía oficial) */
    public function timbrarJSON2(string $jsonCfdi, string $cerPem, string $keyPem, string $passCsd): array
    {
        $payload = [
            'apikey' => $this->apikey,
            'jsonB64' => base64_encode($jsonCfdi),
            'cerPEM' => $cerPem,
            'keyPEM' => $keyPem,
            'passcsd' => $passCsd,
        ];
        return $this->post('timbrarJSON2', $payload);
    }

    /** TIMBRADO: JSON -> XML + datos impresos (UUID, QR, sellos, etc.) */
    public function timbrarJSON3(string $jsonCfdi, string $cerPem, string $keyPem, string $passCsd): array
    {
        $payload = [
            'apikey' => $this->apikey,
            'jsonB64' => base64_encode($jsonCfdi),
            'cerPEM' => $cerPem,
            'keyPEM' => $keyPem,
            'passcsd' => $passCsd,
        ];
        return $this->post('timbrarJSON3', $payload);
    }

    /** TIMBRADO: JSON -> XML + datos (Híbrido: B64 puro sin cabeceras PEM) */
    public function timbrarJSON_Hibrido(string $jsonCfdi, string $cerB64, string $keyB64, string $passCsd): array
    {
        $payload = [
            'apikey' => $this->apikey,
            'jsonB64' => base64_encode($jsonCfdi),
            'cerPEM' => $cerB64, // Enviamos B64 puro en lugar de PEM
            'keyPEM' => $keyB64, // Enviamos B64 puro en lugar de PEM
            'passcsd' => $passCsd,
        ];
        return $this->post('timbrarJSON2', $payload);
    }

    /** CANCELAR (vía CSD suelto en Base64) */
    public function cancelar2(array $args): array
    {
        // $args = ['keyCSD','cerCSD','passCSD','uuid','rfcEmisor','rfcReceptor','total','motivo','folioSustitucion']
        $payload = array_merge(['apikey' => $this->apikey], $args);
        return $this->post('cancelar2', $payload);
    }

    /** CONSULTAR ESTADO EN SAT */
    public function consultarEstadoSAT(string $uuid, string $rfcEmisor, string $rfcReceptor, string $total): array
    {
        $payload = [
            'apikey'      => $this->apikey,
            'uuid'        => $uuid,
            'rfcEmisor'   => $rfcEmisor,
            'rfcReceptor' => $rfcReceptor,
            'total'       => $total,
        ];
        return $this->post('consultarEstadoSAT', $payload);
    }

    /** VALIDAR XML CFDI */
    public function validar(string $xmlCfdi): array
    {
        $payload = [
            'apikey'  => $this->apikey,
            'xmlCFDI' => $xmlCfdi,
        ];
        return $this->post('validar', $payload);
    }
}
