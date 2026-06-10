<?php

namespace App\Services;

use App\Support\SensitiveDataLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCAPIService
{
    protected $pixelId;
    protected $accessToken;
    protected $version = 'v17.0';

    public function __construct()
    {
        $this->pixelId = config('services.meta.pixel_id');
        $this->accessToken = config('services.meta.access_token');
    }

    /**
     * Enviar evento a Meta Conversions API
     */
    public function sendEvent(string $eventName, array $userData, array $customData = [], string $eventSourceUrl = null)
    {
        if (!config('services.meta.enabled')) {
            return false;
        }

        if (!$this->pixelId || !$this->accessToken) {
            Log::warning('Meta CAPI: Falta Pixel ID o Access Token en la configuración.');
            return false;
        }

        $payload = [
            'data' => [
                [
                    'event_name' => $eventName,
                    'event_time' => time(),
                    'action_source' => 'website',
                    'event_source_url' => $eventSourceUrl ?? url()->current(),
                    'user_data' => $this->formatUserData($userData),
                    'custom_data' => $customData,
                ]
            ],
        ];

        // Añadir código de prueba si existe
        if ($testCode = config('services.meta.test_event_code')) {
            $payload['test_event_code'] = $testCode;
        }

        try {
            $response = Http::timeout(3)->connectTimeout(1)->post("https://graph.facebook.com/{$this->version}/{$this->pixelId}/events?access_token={$this->accessToken}", $payload);

            if ($response->failed()) {
                Log::error('Meta CAPI Error:', SensitiveDataLog::redact([
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'payload' => $payload,
                ]));
                return false;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Meta CAPI Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Formatear y hashear datos del usuario según requerimientos de Meta
     */
    protected function formatUserData(array $data): array
    {
        $userData = [];

        if (!empty($data['email'])) {
            $userData['em'] = hash('sha256', strtolower(trim($data['email'])));
        }

        if (!empty($data['telefono'])) {
            // Solo dígitos
            $phone = preg_replace('/\D/', '', $data['telefono']);
            // Asegurar código de país (asumimos MX +52 si no lo tiene y son 10 dígitos)
            if (strlen($phone) === 10) {
                $phone = '52' . $phone;
            }
            $userData['ph'] = hash('sha256', $phone);
        }

        if (!empty($data['nombre'])) {
            $userData['fn'] = hash('sha256', strtolower(trim($data['nombre'])));
        }

        if (!empty($data['ciudad'])) {
            $userData['ct'] = hash('sha256', strtolower(trim($data['ciudad'])));
        }

        if (!empty($data['estado'])) {
            $userData['st'] = hash('sha256', strtolower(trim($data['estado'])));
        }

        if (!empty($data['cp'])) {
            $userData['zp'] = hash('sha256', strtolower(trim($data['cp'])));
        }

        $userData['client_ip_address'] = request()->ip();
        $userData['client_user_agent'] = request()->userAgent();

        return $userData;
    }
}
