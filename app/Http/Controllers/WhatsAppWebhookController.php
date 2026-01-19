<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\WhatsAppMessage;
use App\Services\AI\VircomBotService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verificar webhook (GET challenge)
     */
    public function verify(Request $request)
    {
        // Validar parámetros requeridos
        $validator = Validator::make($request->all(), [
            'hub.mode' => 'required|string|in:subscribe',
            'hub.verify_token' => 'required|string',
            'hub.challenge' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Parámetros de verificación de webhook inválidos', [
                'errors' => $validator->errors(),
                'request' => $request->all(),
            ]);
            return response('Parámetros inválidos', 400);
        }

        $verifyToken = $request->input('hub.verify_token');
        $challenge = $request->input('hub.challenge');

        // Buscar empresa con este token de verificación
        $empresa = Empresa::where('whatsapp_webhook_verify_token', $verifyToken)->first();

        if (!$empresa) {
            Log::warning('Token de verificación de webhook no encontrado', [
                'token' => $verifyToken,
                'ip' => $request->ip(),
            ]);
            return response('Token de verificación inválido', 403);
        }

        Log::info('Webhook verificado exitosamente', [
            'empresa_id' => $empresa->id,
            'empresa_nombre' => $empresa->nombre_razon_social,
        ]);

        // Responder con el challenge para completar la verificación
        return response($challenge, 200);
    }

    /**
     * Recibir notificaciones del webhook (POST)
     */
    public function receive(Request $request)
    {
        // Obtener el cuerpo raw para validación de firma
        $rawBody = $request->getContent();
        $signatureHeader = $request->header('X-Hub-Signature-256', '');

        // Validar firma HMAC si está presente
        if ($signatureHeader && !$this->validateAndSetContext($rawBody, $signatureHeader)) {
            Log::warning('Firma HMAC de webhook inválida', [
                'signature_header' => $signatureHeader,
                'body_length' => strlen($rawBody),
            ]);
            return response('Firma inválida', 403);
        }

        // Decodificar el payload JSON
        $data = json_decode($rawBody, true);

        if (!$data) {
            Log::error('Payload JSON de webhook inválido', [
                'raw_body' => $rawBody,
            ]);
            return response('Payload inválido', 400);
        }

        Log::info('Webhook recibido', [
            'data' => $data,
        ]);

        // Procesar diferentes tipos de eventos
        if (isset($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                // Procesar cambios de estado de mensajes
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        $this->processChange($change);
                    }
                }
            }
        }

        // Responder 200 OK para confirmar recepción
        return response('OK', 200);
    }

    /**
     * Procesar un cambio individual del webhook
     */
    private function processChange(array $change): void
    {
        $value = $change['value'] ?? [];

        // Procesar cambios de estado de mensajes
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                $this->processMessageStatus($status);
            }
        }

        // Procesar mensajes entrantes (si implementas recepción de mensajes)
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                $this->processIncomingMessage($message);
            }
        }
    }

    /**
     * Procesar cambio de estado de un mensaje
     */
    private function processMessageStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $statusValue = $status['status'] ?? null;
        $error = $status['errors'] ?? [];

        if (!$messageId || !$statusValue) {
            Log::warning('Estado de mensaje incompleto', ['status' => $status]);
            return;
        }

        // Buscar el mensaje en la base de datos
        $whatsappMessage = WhatsAppMessage::where('message_id', $messageId)->first();

        if (!$whatsappMessage) {
            Log::info('Estado recibido para mensaje no encontrado en BD', [
                'message_id' => $messageId,
                'status' => $statusValue,
            ]);
            return;
        }

        // Actualizar estado según el valor recibido
        switch ($statusValue) {
            case 'sent':
                $whatsappMessage->markAsSent($messageId, $status);
                break;
            case 'delivered':
                $whatsappMessage->markAsDelivered();
                break;
            case 'read':
                $whatsappMessage->markAsRead();
                break;
            case 'failed':
                $errorCode = !empty($error) ? $error[0]['code'] ?? 'UNKNOWN' : 'UNKNOWN';
                $whatsappMessage->markAsFailed($errorCode, $status);
                break;
            default:
                Log::info('Estado de mensaje desconocido', [
                    'message_id' => $messageId,
                    'status' => $statusValue,
                ]);
        }

        Log::info('Estado de mensaje actualizado', [
            'message_id' => $messageId,
            'new_status' => $statusValue,
            'empresa_id' => $whatsappMessage->empresa_id,
        ]);
    }

    /**
     * Procesar mensaje entrante (para futuras funcionalidades)
     */
    private function processIncomingMessage(array $message): void
    {
        $from = $message['from'] ?? null;
        $text = $message['text']['body'] ?? null;
        $id = $message['id'] ?? null;

        if (!$from || !$text) {
            return;
        }

        Log::info("Chatbot WhatsApp: Mensaje de $from: $text");

        try {
            // 1. Obtener respuesta de la IA
            $bot = app(VircomBotService::class);

            // Usar el número de teléfono como session_id para mantener contexto
            $response = $bot->getResponse($text, "wa_$from");

            // 2. Enviar respuesta por WhatsApp
            $empresaId = \App\Support\EmpresaResolver::resolveId() ?? 1;
            $empresa = Empresa::find($empresaId);

            if ($empresa && $empresa->whatsapp_enabled) {
                $ws = WhatsAppService::fromEmpresa($empresa);
                $ws->sendTextMessage($from, $response['message']);

                Log::info("Chatbot WhatsApp: Respuesta enviada a $from");
            }

        } catch (\Exception $e) {
            Log::error('Chatbot WhatsApp Error: ' . $e->getMessage());
        }
    }

    /**
     * Validar firma HMAC del webhook y establecer contexto de empresa
     */
    private function validateAndSetContext(string $rawBody, string $signatureHeader): bool
    {
        // El header viene en formato "sha256=signature"
        if (!preg_match('/^sha256=(.+)$/', $signatureHeader, $matches)) {
            return false;
        }

        $signature = $matches[1];

        // Para validar necesitamos el app_secret de alguna empresa
        // Como no sabemos cuál empresa, intentamos con todas las que tienen WhatsApp habilitado
        // @todo Optimizar esto usando un mapa de secretos o buscando por business_id del payload primero
        $empresas = Empresa::where('whatsapp_enabled', true)
            ->whereNotNull('whatsapp_app_secret')
            ->get();

        foreach ($empresas as $empresa) {
            try {
                $expectedSignature = hash_hmac('sha256', $rawBody, decrypt($empresa->whatsapp_app_secret));

                if (hash_equals($expectedSignature, $signature)) {
                    Log::info('Firma HMAC validada correctamente', [
                        'empresa_id' => $empresa->id,
                    ]);

                    // ESTABLECER CONTEXTO DE EMPRESA
                    // Esto es crucial para que los modelos con scope funcionen correctamente
                    \App\Support\EmpresaResolver::setContext($empresa->id);

                    return true;
                }
            } catch (\Exception $e) {
                Log::warning('Error al validar firma HMAC para empresa', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        Log::warning('Firma HMAC no válida para ninguna empresa');
        return false;
    }
}
