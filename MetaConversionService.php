<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaConversionService
{
    /**
     * Enviar evento a Meta Conversions API (CAPI)
     */
    public function sendEvent(string $eventName, array $userData = [], array $customData = [])
    {
        if (!config('services.meta.enabled')) {
            return false;
        }

        $pixelId = config('services.meta.pixel_id');
        $accessToken = config('services.meta.access_token');
        $testEventCode = config('services.meta.test_event_code');

        if (empty($accessToken)) {
            return false;
        }

        $pixelId = config('services.meta.pixel_id');
        $accessToken = config('services.meta.access_token');
        $testEventCode = config('services.meta.test_event_code');

        if (empty($accessToken)) {
            return false;
        }

        // Estructura básica de datos de usuario (Meta recomienda hashear)
        $hashedUserData = [];

        // Hashear datos sensibles si existen
        if (isset($userData['em']))
            $hashedUserData['em'] = hash('sha256', strtolower(trim($userData['em'])));
        if (isset($userData['ph']))
            $hashedUserData['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $userData['ph']));

        // Datos no sensibles (IP y UserAgent son muy importantes)
        $hashedUserData['client_ip_address'] = $userData['client_ip_address'] ?? request()->ip();
        $hashedUserData['client_user_agent'] = $userData['client_user_agent'] ?? request()->header('User-Agent');

        // FBP y FBC (Cookies de Facebook) son fundamentales para el match
        if (request()->hasCookie('_fbp'))
            $hashedUserData['fbp'] = request()->cookie('_fbp');
        if (request()->hasCookie('_fbc'))
            $hashedUserData['fbc'] = request()->cookie('_fbc');

        $url = "https://graph.facebook.com/v21.0/{$pixelId}/events";

        $payload = [
            'data' => [
                [
                    'event_name' => $eventName,
                    'event_time' => time(),
                    'action_source' => 'website',
                    'event_source_url' => request()->fullUrl(),
                    'user_data' => $hashedUserData,
                    'custom_data' => $customData,
                ]
            ],
            'access_token' => $accessToken,
        ];

        // Solo incluir el código de prueba si está configurado
        if ($testEventCode) {
            $payload['test_event_code'] = $testEventCode;
        }

        try {
            $response = Http::post($url, $payload);

            if ($response->failed()) {
                Log::error("Meta CAPI Error: " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Meta CAPI Exception: " . $e->getMessage());
            return false;
        }
    }
}
