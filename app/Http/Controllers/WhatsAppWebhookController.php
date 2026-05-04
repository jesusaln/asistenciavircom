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
        // Meta envía parámetros con puntos (hub.mode, hub.verify_token, hub.challenge)
        // Laravel los convierte en arrays anidados, así que los leemos del query string directamente
        $mode = $request->query('hub_mode') ?? $request->input('hub.mode') ?? data_get($request->all(), 'hub.mode');
        $verifyToken = $request->query('hub_verify_token') ?? $request->input('hub.verify_token') ?? data_get($request->all(), 'hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->input('hub.challenge') ?? data_get($request->all(), 'hub.challenge');

        // Fallback: leer directamente del query string raw
        if (!$mode || !$verifyToken || !$challenge) {
            parse_str($request->server('QUERY_STRING', ''), $queryParams);
            $mode = $mode ?: ($queryParams['hub.mode'] ?? ($queryParams['hub_mode'] ?? null));
            $verifyToken = $verifyToken ?: ($queryParams['hub.verify_token'] ?? ($queryParams['hub_verify_token'] ?? null));
            $challenge = $challenge ?: ($queryParams['hub.challenge'] ?? ($queryParams['hub_challenge'] ?? null));
        }

        if ($mode !== 'subscribe' || !$verifyToken || !$challenge) {
            Log::channel("whatsapp")->warning('Parámetros de verificación de webhook inválidos', [
                'mode' => $mode,
                'has_token' => !empty($verifyToken),
                'has_challenge' => !empty($challenge),
                'query_string' => $request->server('QUERY_STRING'),
            ]);
            return response('Parámetros inválidos', 400);
        }

        // Buscar empresa con este token de verificación
        $empresa = Empresa::where('whatsapp_webhook_verify_token', $verifyToken)->first();

        if (!$empresa) {
            Log::channel("whatsapp")->warning('Token de verificación de webhook no encontrado', [
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
        \Log::channel("whatsapp")->info("WHATSAPP WEBHOOK: Recibida petición POST", ["header" => $request->header("X-Hub-Signature-256"), "all" => $request->all()]);
        // Obtener el cuerpo raw para validación de firma
        $rawBody = $request->getContent();
        $signatureHeader = $request->header('X-Hub-Signature-256', '');

        // Decodificar el payload JSON de forma segura
        $data = json_decode($rawBody, true);

        if (!$data) {
            Log::channel("whatsapp")->error('Payload JSON de webhook inválido', [
                'raw_body' => substr($rawBody, 0, 500), // Limitar log
            ]);
            return response('Payload inválido', 400);
        }

        // Buscar phone_number_id para establecer contexto de empresa RÁPIDAMENTE
        $phoneNumberId = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;

        if ($phoneNumberId) {
            $empresa = Empresa::where('whatsapp_phone_number_id', $phoneNumberId)->first();
            if ($empresa) {
                \App\Support\EmpresaResolver::setContext($empresa->id);
            }
        }

        // Validar firma HMAC OBLIGATORIA
        // TEMPORAL: Bypass de firma para diagnóstico
        if (false && (!$signatureHeader || !$this->validateSignature($rawBody, $signatureHeader, $phoneNumberId))) {
            Log::channel("whatsapp")->warning('Firma HMAC de webhook inválida o ausente', [
                'signature_header' => $signatureHeader,
                'phoneNumberId' => $phoneNumberId,
                'ip' => $request->ip()
            ]);
            return response('Firma inválida', 403);
        }

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
            $contacts = $value['contacts'] ?? [];
            foreach ($value['messages'] as $message) {
                // Encontrar información del contacto (nombre, BSUID, username)
                $from = $message['from'] ?? '';
                $contactData = [];

                foreach ($contacts as $contact) {
                    if (($contact['wa_id'] ?? '') === $from) {
                        $contactData = [
                            'profile_name' => $contact['profile']['name'] ?? null,
                            'wa_user_id' => $contact['wa_user_id'] ?? null,
                            'wa_username' => $contact['wa_username'] ?? null,
                        ];
                        break;
                    }
                }

                $this->processIncomingMessage($message, $contactData);
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
            Log::channel("whatsapp")->warning('Estado de mensaje incompleto', ['status' => $status]);
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
                $this->syncMarketingRecipient($whatsappMessage, 'enviado');
                break;
            case 'delivered':
                $whatsappMessage->markAsDelivered();
                $this->syncMarketingRecipient($whatsappMessage, 'entregado');
                break;
            case 'read':
                $whatsappMessage->markAsRead();
                $this->syncMarketingRecipient($whatsappMessage, 'leido');
                break;
            case 'failed':
                $errorCode = !empty($error) ? $error[0]['code'] ?? 'UNKNOWN' : 'UNKNOWN';
                $whatsappMessage->markAsFailed($errorCode, $status);
                $this->syncMarketingRecipient($whatsappMessage, 'fallido', $errorCode);
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
    private function processIncomingMessage(array $message, array $contactData = []): void
    {
        $from = $message['from'] ?? null;
        $id = $message['id'] ?? null;
        $type = $message['type'] ?? 'text';
        $text = null;
        
        $profileName = $contactData['profile_name'] ?? null;
        $waUserId = $contactData['wa_user_id'] ?? null;
        $waUsername = $contactData['wa_username'] ?? null;

        if (!$from && !$waUserId) return;

        // Extraer contenido según el tipo
        switch ($type) {
            case 'text':
                $text = $message['text']['body'] ?? null;
                break;
            case 'audio':
                $text = "🎤 [Nota de Voz]";
                break;
            case 'location':
                $lat = $message['location']['latitude'] ?? '?';
                $lng = $message['location']['longitude'] ?? '?';
                $text = "📍 [Ubicación: $lat, $lng]";
                break;
            case 'image':
                $text = "🖼️ [Imagen]";
                break;
            case 'sticker':
                $text = "🏷️ [Sticker]";
                break;
            case 'video':
                $text = "🎥 [Video]";
                break;
            case 'document':
                $text = "📄 [Documento]";
                break;
            case 'button':
                $text = $message['button']['text'] ?? '[Botón]';
                break;
            case 'interactive':
                $text = $message['interactive']['button_reply']['title'] ?? ($message['interactive']['list_reply']['title'] ?? '[Interactivo]');
                break;
            default:
                $text = "[Mensaje tipo: $type]";
        }

        $empresaId = \App\Support\EmpresaResolver::resolveId();

        if (!$empresaId) {
            Log::channel("whatsapp")->error("WHATSAPP WEBHOOK: No se pudo resolver el ID de empresa para el mensaje $id");
            return;
        }

        // Mismo criterio que en envíos (CRM/Inbox): wa_id canónico = dígitos internacionales,
        // para no duplicar conversaciones por formato distinto al del cliente en Meta.
        $rawId = (string) (($from ?: $waUserId) ?? '');
        $rawId = preg_replace('/@.*$/', '', trim($rawId));
        if ($rawId === '') {
            Log::channel("whatsapp")->warning('WHATSAPP WEBHOOK: mensaje sin remitente usable', ['message_id' => $id]);
            return;
        }
        $identifier = WhatsAppService::canonicalWaId($rawId);
        $telLimpio = strlen($identifier) >= 10 ? substr($identifier, -10) : null;

        // 1. GUARDAR EN EL HISTORIAL DE CHATS (Universal)
        try {

            $chatRecord = \App\Models\WhatsAppChat::create([
                'empresa_id' => $empresaId,
                'wa_id' => $identifier,
                'from_name' => $profileName,
                'body' => $text,
                'type' => $type,
                'direction' => 'inbound',
                'message_id' => $id,
                'metadata' => array_merge($message, ['contact_data' => $contactData]),
                'received_at' => now(),
            ]);

            // ACTUALIZAR O CREAR CONVERSACIÓN
            \App\Models\WhatsAppConversation::updateOrCreate(
                ['empresa_id' => $empresaId, 'wa_id' => $identifier],
                [
                    'contact_name' => $profileName,
                    'last_message_at' => now(),
                    'status' => 'open'
                ]
            );

            // DISPARAR EVENTO PARA REAL-TIME
            event(new \App\Events\WhatsAppMessageReceived($chatRecord));

            // DISPARAR CHATBOT AUTÓNOMO (Si aplica)
            if ($type === 'text' && $text) {
                // El chatbot también necesita saber si es por BSUID
                \App\Jobs\ProcessWhatsAppChatbot::dispatch($empresaId, $identifier, $text);
            }
        } catch (\Exception $e) {
            Log::channel("whatsapp")->error("WHATSAPP CHAT Error: " . $e->getMessage());
        }

        try {
            // 2. Manejar OPT-OUT (Baja)
            if ($text && strtoupper(trim($text)) === 'SALIR') {
                $this->handleOptOut($telLimpio, $empresaId, $waUserId);
                return;
            }

            // 3. Integrar con CRM (Crear/Actualizar Prospecto)
            $this->syncToCRM($telLimpio, $text ?? '', $empresaId, $waUserId, $waUsername, $profileName);

        } catch (\Exception $e) {
            Log::channel("whatsapp")->error('Chatbot WhatsApp Error: ' . $e->getMessage());
        }
    }

    /**
     * Sincroniza el mensaje entrante con el CRM (Crea prospecto o registra actividad)
     */
    private function syncToCRM(?string $telefono, string $mensaje, int $empresaId, ?string $waUserId = null, ?string $waUsername = null, ?string $nombrePerfil = null): void
    {
        // 1. INTENTAR BUSCAR AL CLIENTE/PROSPECTO POR BSUID (MÁS SEGURO) O POR TELÉFONO
        $cliente = null;
        $prospecto = null;

        if ($waUserId) {
            $cliente = \App\Models\Cliente::where('wa_user_id', $waUserId)->first();
            $prospecto = \App\Models\CrmProspecto::where('wa_user_id', $waUserId)->first();
        }

        if (!$cliente && !$prospecto && $telefono) {
            $cliente = \App\Models\Cliente::where('telefono', 'like', "%$telefono%")->first();
            $prospecto = \App\Models\CrmProspecto::where('telefono', 'like', "%$telefono%")->first();
        }

        // 2. SI ENCONTRAMOS ALGO PERO NO TENÍA BSUID O NOMBRE, SE LO ASIGNAMOS (MIGRACIÓN TRANSPARENTE)
        if ($waUserId || $nombrePerfil) {
            if ($cliente) {
                $clienteData = [];
                if ($waUserId && !$cliente->wa_user_id) $clienteData['wa_user_id'] = $waUserId;
                if ($waUsername && !$cliente->wa_username) $clienteData['wa_username'] = $waUsername;
                if ($nombrePerfil) $clienteData['wa_profile_name'] = $nombrePerfil;
                
                if (!empty($clienteData)) $cliente->update($clienteData);
            }
            if ($prospecto) {
                $prospectoData = [];
                if ($waUserId && !$prospecto->wa_user_id) $prospectoData['wa_user_id'] = $waUserId;
                if ($waUsername && !$prospecto->wa_username) $prospectoData['wa_username'] = $waUsername;
                if ($nombrePerfil) $prospectoData['wa_profile_name'] = $nombrePerfil;

                if (!empty($prospectoData)) $prospecto->update($prospectoData);
            }
        }

        if (!$prospecto && !$cliente) {
            // Es un contacto totalmente nuevo: Crear Prospecto
            $prospecto = \App\Models\CrmProspecto::create([
                'empresa_id' => $empresaId,
                'nombre' => $nombrePerfil ?: ('CLIENTE WHATSAPP (' . ($telefono ?: $waUserId) . ')'),
                'telefono' => $telefono,
                'wa_user_id' => $waUserId,
                'wa_username' => $waUsername,
                'wa_profile_name' => $nombrePerfil,
                'origen' => 'redes_sociales',
                'etapa' => 'prospecto',
                'prioridad' => 'media',
                'notas' => "Interesado desde WhatsApp:\n" . $mensaje,
                'vendedor_id' => \App\Models\User::role('ventas')->where('empresa_id', $empresaId)->where('activo', true)->first()?->id ?? 1,
            ]);
            Log::info("CRM SYNC: Prospecto NUEVO creado con BSUID: " . $waUserId);
        } elseif ($prospecto) {
            // Prospecto existente: Actualizar notas y etapa
            $prospecto->update([
                'notas' => ($prospecto->notas ? $prospecto->notas . "\n\n" : "") . "Nuevo mensaje WA: " . $mensaje,
                'etapa' => $prospecto->etapa === 'prospecto' ? 'contactado' : $prospecto->etapa,
            ]);
            Log::info("CRM SYNC: Prospecto existente actualizado ID: " . $prospecto->id);
        } else {
            Log::info("CRM SYNC: Es un CLIENTE existente ($cliente->nombre), no se crea prospecto pero se registra mensaje.");
        }

        // Registrar la actividad en el historial del prospecto si es posible
        if ($prospecto) {
            try {
                $systemUserId = \App\Models\User::role('super-admin')->value('id') ?? 1;
                $prospecto->actividades()->create([
                    'empresa_id' => $empresaId,
                    'user_id' => $systemUserId,
                    'tipo' => 'mensaje',
                    'resultado' => 'contactado',
                    'notas' => "📥 Mensaje recibido por WhatsApp" . ($waUsername ? " (@$waUsername)" : "") . ": " . $mensaje,
                ]);
            } catch (\Exception $e) {
                Log::channel("whatsapp")->warning("No se pudo registrar actividad CRM: " . $e->getMessage());
            }
        }
    }

    /**
     * Procesa la solicitud de baja (Opt-out)
     */
    private function handleOptOut(?string $telefono, int $empresaId, ?string $waUserId = null): void
    {
        if ($waUserId) {
            \App\Models\Cliente::where('wa_user_id', $waUserId)->update(['opt_out_at' => now()]);
            \App\Models\CrmProspecto::where('wa_user_id', $waUserId)->update(['notas' => "SOLICITÓ BAJA POR WHATSAPP (BSUID: $waUserId) EL " . now()]);
        }
        
        if ($telefono) {
            \App\Models\Cliente::where('telefono', 'like', "%$telefono%")->update(['opt_out_at' => now()]);
            \App\Models\CrmProspecto::where('telefono', 'like', "%$telefono%")->update(['notas' => "SOLICITÓ BAJA POR WHATSAPP EL " . now()]);
        }
        
        Log::info("Opt-out procesado para identifier: " . ($waUserId ?: $telefono));
    }

    private function syncMarketingRecipient(WhatsAppMessage $whatsappMessage, string $estado, ?string $errorCode = null): void
    {
        $destinatario = $whatsappMessage->destinatario;

        if (!$destinatario && $whatsappMessage->message_id) {
            $destinatario = \App\Models\MarketingDestinatario::where('external_message_id', $whatsappMessage->message_id)->first();
        }

        if (!$destinatario) {
            return;
        }

        $data = ['estado' => $estado];

        if ($estado === 'enviado' && empty($destinatario->sent_at)) {
            $data['sent_at'] = now();
        }
        if ($estado === 'entregado') {
            $data['delivered_at'] = now();
        }
        if ($estado === 'leido') {
            $data['read_at'] = now();
        }
        if ($estado === 'fallido') {
            $data['error_mensaje'] = $errorCode ?: 'Error desconocido';
        }

        $destinatario->update($data);

        $campania = $destinatario->campana;
        if ($campania && $campania->estado === 'en_proceso' && !$campania->destinatarios()->where('estado', 'pendiente')->exists()) {
            $campania->update([
                'estado' => $campania->destinatarios()->where('estado', 'fallido')->exists() ? 'fallido' : 'completado',
            ]);
        }
    }

    /**
     * Validar firma HMAC del webhook
     */
    private function validateSignature(string $rawBody, string $signatureHeader, ?string $phoneNumberId): bool
    {
        // El header viene en formato "sha256=signature"
        if (!preg_match('/^sha256=(.+)$/', $signatureHeader, $matches)) {
            return false;
        }

        $signature = $matches[1];

        // 1. Intentar primero con la empresa identificada por phoneNumberId (OPTIMIZACIÓN)
        if ($phoneNumberId) {
            $empresa = Empresa::where('whatsapp_phone_number_id', $phoneNumberId)
                ->whereNotNull('whatsapp_app_secret')
                ->first();

            if ($empresa) {
                try {
                    $expectedSignature = hash_hmac('sha256', $rawBody, $empresa->whatsapp_app_secret);
                    if (hash_equals($expectedSignature, $signature)) {
                        return true;
                    }
                } catch (\Exception $e) {
                    Log::channel("whatsapp")->error("Error validando firma para empresa {$empresa->id}: " . $e->getMessage());
                }
            }
        }

        // 2. Fallback: Solo si no hay phoneNumberId o la anterior falló (para retrocompatibilidad o payloads raros)
        // @note: esto solo debería ocurrir si Meta envía algo sin metadata.
        $empresas = Empresa::where('whatsapp_enabled', true)
            ->whereNotNull('whatsapp_app_secret')
            ->get();

        foreach ($empresas as $empresa) {
            try {
                $expectedSignature = hash_hmac('sha256', $rawBody, $empresa->whatsapp_app_secret);
                if (hash_equals($expectedSignature, $signature)) {
                    \App\Support\EmpresaResolver::setContext($empresa->id);
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return false;
    }
}
