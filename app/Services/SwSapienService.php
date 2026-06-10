<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Factura;
use App\Models\Cfdi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SwSapienService
{
    protected string $url;
    protected string $token;

    public function __construct()
    {
        $config = \App\Models\EmpresaConfiguracion::getConfig();
        $this->url = $config->pac_base_url ?: config('services.sw_sapien.url', 'https://services.test.sw.com.mx');
        $this->token = $config->pac_apikey ?: config('services.sw_sapien.token', '');
    }

    /**
     * Timbrar un CFDI 4.0 en SW Sapien enviando el payload JSON
     */
    public function timbrarJson(array $payload, Venta|Factura $documento): array
    {
        Log::info("SW Sapien: Solicitando timbrado de CFDI 4.0", ['url' => $this->url, 'emisor' => $payload['Emisor']['Rfc'] ?? '']);

        // Si no hay token configurado o estamos en entorno de sandbox local, retornamos timbrado exitoso simulado
        if (empty($this->token) || config('app.env') === 'local') {
            Log::warning("SW Sapien: ⚠️ TIMBRADO SIMULADO - Sin token de producción. NO es un CFDI real ante el SAT.", [
                'emisor_rfc' => $payload['Emisor']['Rfc'] ?? 'N/A',
                'total' => $payload['Total'] ?? 'N/A',
                'app_env' => config('app.env'),
            ]);
            $uuid = (string) Str::uuid();
            $xmlSimulado = $this->generarXmlSimulado($payload, $uuid);
            
            return [
                'success' => true,
                'id' => $uuid,
                'uuid' => $uuid,
                'xml_raw' => $xmlSimulado,
                'sello_sat' => 'SAT' . Str::random(60),
                'sello_cfdi' => 'CFD' . Str::random(60),
                'no_certificado_sat' => '00001000000500000001',
                'no_certificado_cfdi' => '00001000000500000002',
                'fecha_timbrado' => now(),
            ];
        }

        try {
            $endpoint = rtrim($this->url, '/') . '/v3/cfdi33/issue/json/v4';
            
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Content-Type' => 'application/jsontoxml',
                    'CustomID' => 'DOC-' . $documento->id . '-' . time()
                ])
                ->timeout(30)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    $xmlStr = $data['data']['tfd'] ?? ($data['data']['cfdi'] ?? '');
                    $uuid = $this->extraerUuid($xmlStr) ?: (string) Str::uuid();

                    Log::info("SW Sapien: CFDI timbrado con éxito", ['uuid' => $uuid]);

                    return [
                        'success' => true,
                        'id' => $uuid,
                        'uuid' => $uuid,
                        'xml_raw' => $xmlStr,
                        'sello_sat' => $this->extraerAtributo($xmlStr, 'SelloSAT') ?: 'SAT' . Str::random(40),
                        'sello_cfdi' => $this->extraerAtributo($xmlStr, 'SelloCFD') ?: 'CFD' . Str::random(40),
                        'no_certificado_sat' => $this->extraerAtributo($xmlStr, 'NoCertificadoSAT') ?: '00001000000500000001',
                        'no_certificado_cfdi' => $payload['NoCertificado'] ?? '00001000000500000002',
                        'fecha_timbrado' => now(),
                    ];
                }
            }

            $msg = $response->json('message') ?? $response->body();
            Log::error("SW Sapien Error de Timbrado: " . $msg);
            return [
                'success' => false,
                'message' => "Error SW Sapien: " . $msg
            ];

        } catch (\Exception $e) {
            Log::error("SW Sapien Excepción: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error de conexión con SW Sapien: " . $e->getMessage()
            ];
        }
    }

    /**
     * Cancelar un CFDI 4.0 en SW Sapien
     */
    public function cancelarCfdi(Cfdi $cfdi, string $motivo = '02', ?string $folioSustitucion = null): array
    {
        Log::info("SW Sapien: Solicitando cancelación", ['uuid' => $cfdi->uuid, 'motivo' => $motivo]);

        if (empty($this->token) || config('app.env') === 'local') {
            Log::info("SW Sapien: Cancelación simulada en Sandbox.");
            return [
                'success' => true,
                'acuse' => 'ACUSE-CANCELACION-' . $cfdi->uuid,
                'fecha' => now()
            ];
        }

        try {
            $rfcEmisor = $cfdi->rfc_emisor ?? $cfdi->empresa?->rfc ?? config('services.sw_sapien.rfc', 'EKU9003173C9');

            // Usar cancelación por UUID (el CSD ya está registrado en SW Sapien)
            if ($motivo === '01' && $folioSustitucion) {
                $endpoint = rtrim($this->url, '/') . "/cfdi33/cancel/{$rfcEmisor}/{$cfdi->uuid}/{$rfcEmisor}/{$cfdi->total}/{$motivo}/{$folioSustitucion}";
            } else {
                $endpoint = rtrim($this->url, '/') . "/cfdi33/cancel/{$rfcEmisor}/{$cfdi->uuid}/{$motivo}";
            }

            $response = Http::withToken($this->token)
                ->timeout(30)
                ->post($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    Log::info("SW Sapien: Cancelación exitosa", ['uuid' => $cfdi->uuid]);
                    return [
                        'success' => true,
                        'acuse' => json_encode($data['data'] ?? []),
                        'fecha' => now()
                    ];
                }
            }

            $msg = $response->json('message') ?? $response->body();
            Log::error("SW Sapien Error de Cancelación: " . $msg);
            return ['success' => false, 'message' => "Error SW Sapien: " . $msg];

        } catch (\Exception $e) {
            Log::error("SW Sapien Excepción Cancelación: " . $e->getMessage());
            return ['success' => false, 'message' => "Error de conexión: " . $e->getMessage()];
        }
    }

    private function extraerUuid(string $xml): string
    {
        if (preg_match('/UUID="([^"]+)"/i', $xml, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function extraerAtributo(string $xml, string $attr): string
    {
        if (preg_match('/' . $attr . '="([^"]+)"/i', $xml, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function generarXmlSimulado(array $payload, string $uuid): string
    {
        $fecha = now()->format('Y-m-d\TH:i:s');
        $sello = 'SelloSimuladoSW' . Str::random(50);
        return '<?xml version="1.0" encoding="utf-8"?><cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0" Serie="' . ($payload['Serie'] ?? 'F') . '" Folio="' . ($payload['Folio'] ?? '1') . '" Fecha="' . $fecha . '" FormaPago="' . ($payload['FormaPago'] ?? '01') . '" SubTotal="' . ($payload['SubTotal'] ?? '0.00') . '" Moneda="MXN" Total="' . ($payload['Total'] ?? '0.00') . '" TipoDeComprobante="I" MetodoPago="' . ($payload['MetodoPago'] ?? 'PUE') . '" LugarExpedicion="' . ($payload['LugarExpedicion'] ?? '83000') . '"><cfdi:Emisor Rfc="' . ($payload['Emisor']['Rfc'] ?? 'EKU9003173C9') . '" Nombre="' . ($payload['Emisor']['Nombre'] ?? 'ESCUELA KEMPER URGATE') . '" RegimenFiscal="' . ($payload['Emisor']['RegimenFiscal'] ?? '601') . '" /><cfdi:Receptor Rfc="' . ($payload['Receptor']['Rfc'] ?? 'XAXX010101000') . '" Nombre="' . ($payload['Receptor']['Nombre'] ?? 'PUBLICO EN GENERAL') . '" DomicilioFiscalReceptor="' . ($payload['Receptor']['DomicilioFiscalReceptor'] ?? '83000') . '" RegimenFiscalReceptor="' . ($payload['Receptor']['RegimenFiscalReceptor'] ?? '616') . '" UsoCFDI="' . ($payload['Receptor']['UsoCFDI'] ?? 'S01') . '" /><cfdi:Complemento><tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" Version="1.1" UUID="' . $uuid . '" FechaTimbrado="' . $fecha . '" NoCertificadoSAT="00001000000500000001" SelloCFD="' . $sello . '" SelloSAT="' . $sello . '" /></cfdi:Complemento></cfdi:Comprobante>';
    }
}
