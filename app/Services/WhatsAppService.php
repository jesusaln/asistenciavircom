<?php

namespace App\Services;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Models\WhatsAppMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private Client $httpClient;
    private string $graphVersion;
    private string $phoneNumberId;
    private string $accessToken;

    /**
     * Constructor del servicio WhatsApp
     */
    public function __construct(string $phoneNumberId, string $accessToken, string $graphVersion = 'v20.0')
    {
        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken = $accessToken;
        $this->graphVersion = $graphVersion;

        $this->httpClient = new Client([
            'base_uri' => "https://graph.facebook.com/{$this->graphVersion}/",
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Crear instancia del servicio desde configuración de empresa
     */
    public static function fromEmpresa(Empresa $empresa): self
    {
        if (!$empresa->whatsapp_enabled) {
            throw new \InvalidArgumentException('WhatsApp no está habilitado para esta empresa');
        }

        if (!$empresa->whatsapp_phone_number_id || !$empresa->whatsapp_access_token) {
            throw new \InvalidArgumentException('Configuración de WhatsApp incompleta para esta empresa');
        }

        // Manejar token encriptado o no encriptado
        $accessToken = $empresa->whatsapp_access_token;

        // Verificar si el token parece estar encriptado (formato típico de Laravel)
        if (preg_match('/^[A-Za-z0-9+\/=]{20,}$/', $empresa->whatsapp_access_token)) {
            try {
                // Intentar desencriptar
                $accessToken = decrypt($empresa->whatsapp_access_token);
                Log::info('Token desencriptado exitosamente para WhatsApp', [
                    'empresa_id' => $empresa->id,
                ]);
            } catch (\Exception $e) {
                // Si falla la desencriptación, usar el token tal cual
                Log::info('Usando token sin encriptar para WhatsApp', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Si no parece encriptado, usar directamente
            Log::info('Token detectado como no encriptado para WhatsApp', [
                'empresa_id' => $empresa->id,
            ]);
        }

        return new self(
            $empresa->whatsapp_phone_number_id,
            $accessToken,
            config('services.whatsapp.graph_version', 'v20.0')
        );
    }

    /**
     * Enviar plantilla de WhatsApp
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $language = 'es_MX',
        array $bodyParams = [],
        array $headerParams = []
    ): array {
        // Convertir número de teléfono al formato E.164 si es necesario
        $formattedPhone = self::formatPhoneToE164($to);

        // Validar número de teléfono (debe estar en formato E.164)
        if (!$this->isValidE164Phone($formattedPhone)) {
            throw new \InvalidArgumentException('Número de teléfono debe estar en formato E.164 (ej: +52551234567). Número recibido: ' . $to);
        }

        // Construir payload para la API de WhatsApp
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $formattedPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        // Agregar parámetros del body si existen
        if (!empty($bodyParams)) {
            $payload['template']['components'][] = [
                'type' => 'body',
                'parameters' => array_map(function ($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $bodyParams),
            ];
        }

        // Agregar parámetros del header si existen
        if (!empty($headerParams)) {
            $payload['template']['components'][] = [
                'type' => 'header',
                'parameters' => $headerParams,
            ];
        }

        try {
            Log::info('Enviando plantilla WhatsApp', [
                'to' => $to,
                'formatted_to' => $formattedPhone,
                'template' => $templateName,
                'payload' => $payload,
            ]);

            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('Plantilla WhatsApp enviada exitosamente', [
                'to' => $to,
                'message_id' => $responseData['messages'][0]['id'] ?? null,
                'response' => $responseData,
            ]);

            return $responseData;

        } catch (RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            $errorData = json_decode($errorBody, true);

            Log::error('Error al enviar plantilla WhatsApp', [
                'to' => $to,
                'formatted_to' => $formattedPhone,
                'template' => $templateName,
                'error' => $e->getMessage(),
                'error_response' => $errorData,
                'http_status' => $errorResponse ? $errorResponse->getStatusCode() : 'unknown',
            ]);

            // Manejar errores específicos de autenticación
            if ($errorResponse && $errorResponse->getStatusCode() === 401) {
                $errorMessage = $errorData['error']['message'] ?? $e->getMessage();
                if (strpos($errorMessage, 'Error validating access token') !== false) {
                    throw new \Exception(
                        'Token de acceso de WhatsApp expirado o inválido. Genere un nuevo token en Meta Business y actualícelo en la configuración.',
                        401,
                        $e
                    );
                }
            }

            // Manejar errores de número de teléfono inválido
            if ($errorResponse && $errorResponse->getStatusCode() === 400) {
                $errorMessage = $errorData['error']['message'] ?? $e->getMessage();
                if (strpos($errorMessage, 'phone number') !== false || strpos($errorMessage, 'recipient') !== false) {
                    throw new \Exception(
                        'Número de teléfono inválido para WhatsApp. Verifique que el número esté en formato E.164 y haya interactuado con su WhatsApp Business.',
                        400,
                        $e
                    );
                }
            }

            // Manejar errores de plantilla
            if ($errorResponse && $errorResponse->getStatusCode() === 400) {
                $errorMessage = $errorData['error']['message'] ?? $e->getMessage();
                $errorCode = $errorData['error']['code'] ?? null;

                if (strpos($errorMessage, 'template') !== false || $errorCode === 132001) {
                    throw new \Exception(
                        "Plantilla '{$templateName}' no existe o no está aprobada. " .
                        "Verifique en Meta Business Manager que la plantilla esté creada y en estado 'Aprobada'. " .
                        "Idioma configurado: {$language}",
                        400,
                        $e
                    );
                }
            }

            throw new \Exception(
                'Error al enviar plantilla WhatsApp: ' . ($errorData['error']['message'] ?? $e->getMessage()),
                $errorResponse ? $errorResponse->getStatusCode() : 0,
                $e
            );
        }
    }

    /**
     * Convertir número de teléfono mexicano al formato E.164
     */
    public static function formatPhoneToE164(string $phone): string
    {
        // Limpiar el número: dejar solo dígitos
        $digits = preg_replace('/\D+/', '', $phone);

        // Si ya está en formato E.164, devolverlo tal cual
        if (preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
            return $phone;
        }

        // Si ya tiene + pero no es válido, intentar corregir
        if (str_starts_with($phone, '+')) {
            $digits = preg_replace('/\D+/', '', $phone);
            if (preg_match('/^\d{10,15}$/', $digits)) {
                return '+' . $digits;
            }
        }

        // Para números mexicanos:
        // - 10 dígitos: celular, agregar +52
        if (strlen($digits) === 10) {
            return '+52' . $digits;
        }

        // - 8 dígitos: número local, asumir código de área común (Hermosillo: 662)
        if (strlen($digits) === 8) {
            return '+52662' . $digits;
        }

        // - Si tiene código de país pero sin +, agregarlo
        if (preg_match('/^52\d{10}$/', $digits)) {
            return '+' . $digits;
        }

        // - Si tiene código de área + número local (ej: 6621234567)
        if (preg_match('/^662\d{7}$/', $digits)) {
            return '+52' . $digits;
        }

        // Si no podemos determinar el formato, devolver el número con + al inicio
        // Esto permitirá que la validación E.164 lo rechace con un mensaje claro
        return '+' . $digits;
    }

    /**
     * Validar formato de número de teléfono E.164
     */
    private function isValidE164Phone(string $phone): bool
    {
        // E.164 format: + seguido de código de país y número (ej: +52551234567)
        return preg_match('/^\+[1-9]\d{1,14}$/', $phone) === 1;
    }

    /**
     * Obtener información del número de teléfono
     */
    public function getPhoneInfo(string $phone): array
    {
        try {
            $response = $this->httpClient->get("{$this->phoneNumberId}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Error al obtener información del teléfono', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Error al obtener información del teléfono: ' . $e->getMessage());
        }
    }

    /**
     * Método de utilidad para probar el formateo de números de teléfono
     */
    public static function testPhoneFormatting(): array
    {
        $testNumbers = [
            '5512345678',           // Número celular 10 dígitos
            '6621234567',           // Número local 10 dígitos
            '12345678',             // Número local 8 dígitos
            '+525512345678',        // Ya formateado correctamente
            '+52 55 1234 5678',     // Con espacios y +
            '55 1234 5678',         // Con espacios
            '662-123-4567',         // Con guiones
            'invalid-number',       // Número inválido
        ];

        $results = [];
        foreach ($testNumbers as $number) {
            $formatted = self::formatPhoneToE164($number);
            $isValid = preg_match('/^\+[1-9]\d{1,14}$/', $formatted) === 1;
            $results[] = [
                'original' => $number,
                'formatted' => $formatted,
                'valid' => $isValid,
            ];
        }

        return $results;
    }

    /**
     * Listar plantillas de WhatsApp disponibles
     */
    public function listTemplates(): array
    {
        try {
            // Nota: Necesitamos el Business Account ID para listar plantillas
            // Por ahora, devolveremos una lista basada en la configuración de la empresa
            $empresaId = EmpresaResolver::resolveId();
            $empresa = $empresaId ? Empresa::find($empresaId) : null;

            if (!$empresa || !$empresa->whatsapp_business_account_id) {
                throw new \Exception('Business Account ID no configurado');
            }

            $response = $this->httpClient->get("{$empresa->whatsapp_business_account_id}/message_templates", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'] ?? [];
        } catch (RequestException $e) {
            Log::error('Error al listar plantillas de WhatsApp', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Error al obtener plantillas: ' . $e->getMessage());
        }
    }

    /**
     * Crear plantilla básica de recordatorio de pago
     */
    public function createBasicTemplate(string $templateName = 'recordatorio_de_pago'): array
    {
        $payload = [
            'name' => $templateName,
            'language' => 'es_MX',
            'category' => 'MARKETING',
            'components' => [
                [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => 'Recordatorio de Pago'
                ],
                [
                    'type' => 'BODY',
                    'text' => 'Hola {{1}}, le recordamos que tiene un pago pendiente de {{2}} con fecha límite el {{3}}.',
                    'example' => [
                        'body_text' => [
                            ['Cliente Ejemplo'],
                            ['$1,500.00'],
                            ['15/10/2025']
                        ]
                    ]
                ],
                [
                    'type' => 'BUTTON',
                    'subtype' => 'QUICK_REPLY',
                    'index' => '0',
                    'parameters' => [
                        [
                            'type' => 'payload',
                            'payload' => 'PAGADO'
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->httpClient->post("{$this->phoneNumberId}/message_templates", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Error al crear plantilla de WhatsApp', [
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Error al crear plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Marcar mensaje como leído
     */
    public function markAsRead(string $messageId): array
    {
        try {
            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $messageId,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Error al marcar mensaje como leído', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Error al marcar mensaje como leído: ' . $e->getMessage());
        }
    }
}
