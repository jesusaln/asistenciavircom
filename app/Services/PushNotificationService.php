<?php

namespace App\Services;

use GuzzleHttp\Client;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PushNotificationService
{
    protected $client;
    protected $projectId;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 15,          // 15 segundos máximo para recibir respuesta
            'connect_timeout' => 5,   // 5 segundos máximo para establecer conexión
        ]);
        // El Project ID se obtiene del JSON de credenciales
    }

    /**
     * Enviar notificación push a un usuario específico
     */
    public function sendNotification($fcmToken, $title, $body, $data = [])
    {
        if (!$fcmToken) return false;

        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Log::error('No se pudo obtener el token de acceso de Firebase. Verifique firebase-auth.json');
                return false;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'default'
                        ]
                    ]
                ]
            ];

            if (!empty($data)) {
                $payload['message']['data'] = array_map('strval', $data);
            }

            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            Log::error('Error enviando notificación Push: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener token de acceso OAuth2 usando el archivo de service account
     */
    protected function getAccessToken()
    {
        $path = storage_path('app/firebase-auth.json');
        
        if (!file_exists($path)) {
            Log::warning('Archivo de credenciales de Firebase no encontrado en: ' . $path);
            return null;
        }

        try {
            $googleClient = new GoogleClient();
            $googleClient->setAuthConfig($path);
            $googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');
            
            $auth = $googleClient->fetchAccessTokenWithAssertion();
            $this->projectId = json_decode(file_get_contents($path))->project_id;
            
            return $auth['access_token'];
        } catch (\Exception $e) {
            Log::error('Error autenticando con Google SDK: ' . $e->getMessage());
            return null;
        }
    }
}
