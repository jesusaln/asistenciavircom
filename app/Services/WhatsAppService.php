<?php

namespace App\Services;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Models\WhatsAppMessage;
use App\Support\SensitiveDataLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public Client $httpClient;
    private string $graphVersion;
    private string $phoneNumberId;
    private string $accessToken;
    private ?string $businessAccountId;

    /**
     * Constructor del servicio WhatsApp
     */
    public function __construct(string $phoneNumberId, string $accessToken, ?string $businessAccountId = null, string $graphVersion = 'v20.0')
    {
        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken = $accessToken;
        $this->businessAccountId = $businessAccountId;
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

        return new self(
            $empresa->whatsapp_phone_number_id,
            $accessToken,
            $empresa->whatsapp_business_account_id,
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

        // Auto-detectar si la plantilla tiene un header IMAGE/VIDEO/DOCUMENT
        // y agregar el componente automáticamente si no se proporcionaron headerParams
        if (empty($headerParams)) {
            $headerParams = $this->resolveTemplateHeaderParams($templateName, $language);
        }

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
            Log::info('Enviando plantilla WhatsApp', SensitiveDataLog::redact([
                'to' => $to,
                'formatted_to' => $formattedPhone,
                'template' => $templateName,
                'payload' => $payload,
            ]));

            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('Plantilla WhatsApp enviada exitosamente', SensitiveDataLog::redact([
                'to' => $to,
                'message_id' => $responseData['messages'][0]['id'] ?? null,
                'response' => $responseData,
            ]));

            return $responseData;

        } catch (RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            $errorData = json_decode($errorBody, true);

            Log::error('Error al enviar plantilla WhatsApp', SensitiveDataLog::redact([
                'to' => $to,
                'formatted_to' => $formattedPhone,
                'template' => $templateName,
                'error' => $e->getMessage(),
                'error_response' => $errorData,
                'http_status' => $errorResponse ? $errorResponse->getStatusCode() : 'unknown',
            ]));

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
                        'Número de teléfono inválido o no autorizado. Meta API devolvió: ' . $errorMessage . '. Verifique que el número esté en formato E.164 y haya interactuado/verificado con su Test Account de Meta.',
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
     * Enviar mensaje de texto libre (Sesión activa)
     */
    public function sendTextMessage(string $to, string $message): array
    {
        $formattedPhone = self::formatPhoneToE164($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formattedPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ];

        try {
            Log::info('Enviando mensaje de texto WhatsApp', SensitiveDataLog::redact([
                'to' => $to,
                'payload' => $payload,
            ]));

            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            Log::error('Error enviando texto WhatsApp', ['error' => $errorBody]);
            throw new \Exception('Error al enviar mensaje de texto: ' . $errorBody);
        }
    }

    /**
     * Enviar imagen
     */
    public function sendImage(string $to, string $imageUrl, ?string $caption = null): array
    {
        $formattedPhone = self::formatPhoneToE164($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formattedPhone,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
                'caption' => $caption,
            ],
        ];

        try {
            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            Log::error('Error enviando imagen WhatsApp', ['error' => $errorBody]);
            throw new \Exception('Error al enviar imagen: ' . $errorBody);
        }
    }

    /**
     * Enviar sticker
     */
    public function sendSticker(string $to, string $stickerUrlOrId): array
    {
        $formattedPhone = self::formatPhoneToE164($to);

        $stickerData = [];
        if (filter_var($stickerUrlOrId, FILTER_VALIDATE_URL)) {
            $stickerData['link'] = $stickerUrlOrId;
        } else {
            $stickerData['id'] = $stickerUrlOrId;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formattedPhone,
            'type' => 'sticker',
            'sticker' => $stickerData,
        ];

        try {
            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            Log::error('Error enviando sticker WhatsApp', ['error' => $errorBody]);
            throw new \Exception('Error al enviar sticker: ' . $errorBody);
        }
    }

    /**
     * Subir archivo a Meta para obtener un media_id
     */
    public function uploadMedia(string $filePath, string $mimeType): string
    {
        try {
            $response = $this->httpClient->post("{$this->phoneNumberId}/media", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                        'Mime-Type' => $mimeType,
                    ],
                    [
                        'name' => 'messaging_product',
                        'contents' => 'whatsapp',
                    ],
                    [
                        'name' => 'type',
                        'contents' => explode('/', $mimeType)[0] === 'image' ? 'image' : 'document',
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['id'];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            Log::error('Error subiendo media a WhatsApp', ['error' => $errorBody]);
            throw new \Exception('Error al subir archivo a WhatsApp: ' . $errorBody);
        }
    }

    /**
     * Enviar media por ID
     */
    public function sendMediaById(string $to, string $mediaId, string $type, ?string $caption = null): array
    {
        $formattedPhone = self::formatPhoneToE164($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formattedPhone,
            'type' => $type,
            $type => [
                'id' => $mediaId,
            ],
        ];

        if ($caption && in_array($type, ['image', 'video', 'document'])) {
            $payload[$type]['caption'] = $caption;
        }

        try {
            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            Log::error('Error enviando media ID WhatsApp', ['error' => $errorBody]);
            throw new \Exception('Error al enviar media: ' . $errorBody);
        }
    }
    /**
     * Enviar mensaje interactivo con botones de respuesta rápida (máximo 3 botones)
     *
     * @param string $to Número del destinatario
     * @param string $bodyText Texto principal del mensaje
     * @param array $buttons Array de ['id' => string, 'title' => string] (máx 3, título máx 20 chars)
     * @param string|null $header Texto de header opcional (máx 60 chars)
     * @param string|null $footer Texto de footer opcional (máx 60 chars)
     */
    public function sendInteractiveButtons(string $to, string $bodyText, array $buttons, ?string $header = null, ?string $footer = null): array
    {
        $formattedPhone = self::formatPhoneToE164($to);

        $actionButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $actionButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $btn['id'],
                    'title' => mb_substr($btn['title'], 0, 20),
                ],
            ];
        }

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $bodyText],
            'action' => ['buttons' => $actionButtons],
        ];

        if ($header) {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($header, 0, 60)];
        }
        if ($footer) {
            $interactive['footer'] = ['text' => mb_substr($footer, 0, 60)];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formattedPhone,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        try {
            Log::info('Enviando botones interactivos WhatsApp', SensitiveDataLog::redact([
                'to' => $to,
                'buttons_count' => count($actionButtons),
            ]));

            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => ['Authorization' => "Bearer {$this->accessToken}"],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            Log::error('Error enviando botones interactivos WhatsApp', ['error' => $errorBody]);
            throw new \Exception('Error al enviar botones interactivos: ' . $errorBody);
        }
    }

    /**
     * Enviar mensaje interactivo con lista desplegable (hasta 10 opciones por sección)
     *
     * @param string $to Número del destinatario
     * @param string $bodyText Texto principal del mensaje
     * @param string $buttonText Texto del botón que abre la lista (máx 20 chars)
     * @param array $sections Array de secciones: [['title' => 'Sección', 'rows' => [['id' => 'x', 'title' => 'Opción', 'description' => '...']]]]
     * @param string|null $header Texto de header opcional (máx 60 chars)
     * @param string|null $footer Texto de footer opcional (máx 60 chars)
     */
    public function sendInteractiveList(string $to, string $bodyText, string $buttonText, array $sections, ?string $header = null, ?string $footer = null): array
    {
        $formattedPhone = self::formatPhoneToE164($to);

        // Sanitizar secciones: títulos máx 24 chars, descripciones máx 72 chars
        $sanitizedSections = [];
        foreach ($sections as $section) {
            $rows = [];
            foreach (($section['rows'] ?? []) as $row) {
                $rowData = [
                    'id' => $row['id'],
                    'title' => mb_substr($row['title'], 0, 24),
                ];
                if (!empty($row['description'])) {
                    $rowData['description'] = mb_substr($row['description'], 0, 72);
                }
                $rows[] = $rowData;
            }
            $sectionData = ['rows' => $rows];
            if (!empty($section['title'])) {
                $sectionData['title'] = mb_substr($section['title'], 0, 24);
            }
            $sanitizedSections[] = $sectionData;
        }

        $interactive = [
            'type' => 'list',
            'body' => ['text' => $bodyText],
            'action' => [
                'button' => mb_substr($buttonText, 0, 20),
                'sections' => $sanitizedSections,
            ],
        ];

        if ($header) {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($header, 0, 60)];
        }
        if ($footer) {
            $interactive['footer'] = ['text' => mb_substr($footer, 0, 60)];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formattedPhone,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        try {
            Log::info('Enviando lista interactiva WhatsApp', SensitiveDataLog::redact([
                'to' => $to,
                'sections_count' => count($sanitizedSections),
            ]));

            $response = $this->httpClient->post("{$this->phoneNumberId}/messages", [
                'headers' => ['Authorization' => "Bearer {$this->accessToken}"],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorResponse = $e->getResponse();
            $errorBody = $errorResponse ? $errorResponse->getBody()->getContents() : 'No response body';
            Log::error('Error enviando lista interactiva WhatsApp', ['error' => $errorBody]);
            throw new \Exception('Error al enviar lista interactiva: ' . $errorBody);
        }
    }

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
     * wa_id estable para BD y conversaciones: solo dígitos en formato internacional,
     * alineado con el campo `from` de los webhooks de Meta (sin + ni sufijos).
     * Así el envío desde CRM/cotización no crea un hilo distinto al del mismo número en la bandeja.
     */
    /**
     * wa_id estable para BD y bandeja. Unifica:
     * - sufijos tipo @c.us de algunos payloads
     * - México Meta: "521" + 10 dígitos nacionales (13 chars) vs "52" + 10 (12) o 10 dígitos locales en CRM
     */
    public static function canonicalWaId(string $phone): string
    {
        $phone = preg_replace('/@.*$/u', '', trim((string) $phone));
        $digits = preg_replace('/\D+/', '', $phone);

        // +52 1 XX… en formato compacto Meta: 521 + diez dígitos → mismo E.164 que 52 + diez dígitos
        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            $digits = '52'.substr($digits, 3, 10);
        }

        return preg_replace('/\D+/', '', self::formatPhoneToE164($digits));
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
     * Auto-detectar y resolver parámetros de header para plantillas con media (IMAGE/VIDEO/DOCUMENT)
     * Consulta la definición de la plantilla en Meta para obtener la URL del media del ejemplo.
     */
    private function resolveTemplateHeaderParams(string $templateName, string $language = 'es_MX'): array
    {
        try {
            $templates = $this->listTemplates();

            foreach ($templates as $template) {
                if ($template['name'] === $templateName && $template['language'] === $language) {
                    foreach ($template['components'] ?? [] as $component) {
                        if ($component['type'] === 'HEADER') {
                            $format = strtolower($component['format'] ?? 'TEXT');

                            if (in_array($format, ['image', 'video', 'document'])) {
                                // Obtener la URL del ejemplo del header
                                $handleUrl = $component['example']['header_handle'][0] ?? null;

                                if ($handleUrl) {
                                    Log::info('Auto-resolviendo header media para plantilla', [
                                        'template' => $templateName,
                                        'format' => $format,
                                    ]);

                                    return [
                                        [
                                            'type' => $format,
                                            $format => ['link' => $handleUrl],
                                        ],
                                    ];
                                }
                            }
                        }
                    }
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo auto-resolver header de plantilla', [
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Listar plantillas de WhatsApp disponibles
     */
    /**
     * Obtener la URL pública de un media de WhatsApp
     */
    public function getMediaUrl(string $mediaId): string
    {
        try {
            $response = $this->httpClient->get("{$mediaId}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['url'] ?? '';
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = $e->getResponse()?->getBody()->getContents() ?? 'No response';
            Log::error('Error obteniendo URL de media WhatsApp', ['media_id' => $mediaId, 'error' => $errorBody]);
            throw new \Exception('Error al obtener URL del media: ' . $errorBody);
        }
    }

    /**
     * Descargar un media desde una URL de WhatsApp
     */
    public function downloadMedia(string $mediaUrl): string
    {
        try {
            $response = $this->httpClient->get($mediaUrl, [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                ],
            ]);
            return $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = $e->getResponse()?->getBody()->getContents() ?? 'No response';
            Log::error('Error descargando media de WhatsApp', ['url' => $mediaUrl, 'error' => $errorBody]);
            throw new \Exception('Error al descargar media: ' . $errorBody);
        }
    }

    public function listTemplates(): array
    {
        try {
            if (!$this->businessAccountId) {
                throw new \Exception('Business Account ID (WABA ID) no configurado en el servicio');
            }
            $response = $this->httpClient->get("{$this->businessAccountId}/message_templates", [
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
