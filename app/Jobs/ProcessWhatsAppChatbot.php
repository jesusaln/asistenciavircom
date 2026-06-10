<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\Cita;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Ticket;
use App\Models\WhatsAppChat;
use App\Models\WhatsAppConversation;
use App\Services\AI\VircomBotService;
use App\Services\WhatsAppService;
use App\Events\WhatsAppMessageReceived;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessWhatsAppChatbot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use Concerns\HandlesMenuFlow, Concerns\HandlesAppointmentFlow, Concerns\HandlesCancelFlow, Concerns\HandlesPresupuestoFlow;

    public $tries = 5;
    public $maxExceptions = 3;

    protected $empresaId;
    protected $waId;
    protected $incomingMessage;

    public function __construct(int $empresaId, string $waId, string $incomingMessage)
    {
        $this->empresaId = $empresaId;
        $this->waId = $waId;
        $this->incomingMessage = $incomingMessage;
    }

    public function handle(): void
    {
        \App\Support\EmpresaResolver::setContext($this->empresaId);

        try {
            $empresa = Empresa::find($this->empresaId);
            if (!$empresa || !$empresa->whatsapp_chatbot_enabled) {
                Log::channel("whatsapp")->info("ProcessWhatsAppChatbot: Chatbot disabled or empresa not found", [
                    'empresa_id' => $this->empresaId,
                    'wa_id' => $this->waId,
                ]);
                return;
            }

            // Si hay un asesor humano activo en la conversación, omitimos el bot
            $humanActiveKey = "whatsapp_human_active_{$this->empresaId}_{$this->waId}";
            if (Cache::get($humanActiveKey, false)) {
                Log::channel("whatsapp")->info("ProcessWhatsAppChatbot: Asesor humano está activo en esta conversación ({$this->waId}). Omitiendo bot.");
                return;
            }

            // Verificar si el bot está deshabilitado para esta conversación
            $conv = \App\Models\WhatsAppConversation::where('empresa_id', $this->empresaId)
                ->where('canonical_wa_id', $this->waId)
                ->where('chatbot_disabled', true)
                ->exists();
            if ($conv) {
                Log::channel("whatsapp")->info("ProcessWhatsAppChatbot: Bot deshabilitado para esta conversación ({$this->waId}). Omitiendo.");
                return;
            }

            // 1. Control de Abuso: Comprobar si el usuario está bloqueado por spam
            $blockedKey = "whatsapp_chatbot_spam_blocked_{$this->empresaId}_{$this->waId}";
            if (Cache::has($blockedKey)) {
                Log::channel("whatsapp")->info("ProcessWhatsAppChatbot: Usuario {$this->waId} bloqueado por límite de consultas diarias excedido. Omitiendo bot.");
                return;
            }

            // 2. Incrementar contador diario de consultas del usuario
            $countKey = "whatsapp_chatbot_msg_count_{$this->empresaId}_{$this->waId}";
            $msgCount = (int) Cache::get($countKey, 0) + 1;
            Cache::put($countKey, $msgCount, now()->addDay());

            // Si excede el límite de 200 consultas diarias, bloquear y enviar advertencia
            if ($msgCount > 200) {
                Log::channel("whatsapp")->warning("ProcessWhatsAppChatbot: Límite de consultas diarias excedido para usuario {$this->waId}. Bloqueando.");
                
                // Bloquear por 24 horas
                Cache::put($blockedKey, true, now()->addDay());

                // Forzar estado 'human' permanente
                $stateKey = "whatsapp_menu_state_{$this->empresaId}_{$this->waId}";
                Cache::put($stateKey, 'human', now()->addDay());
                Cache::put("{$stateKey}_human_since", now()->addYears(10), now()->addDay()); // Evitar auto-timeout de 1 hora

                // Enviar mensaje de advertencia
                $this->sendReply("⚠️ *Límite de consultas excedido:*\n\nHas superado el límite de 30 consultas diarias con nuestro asistente virtual.\n\nPara evitar saturación, he transferido esta conversación a un asesor humano, quien te atenderá a la brevedad.");
                return;
            }

            // Evitar saturación: Espaciar los mensajes si se procesan en ráfaga (ej: después de atasco de cola)
            if (!app()->environment('testing')) {
                $lastReplyKey = "whatsapp_last_reply_sent_{$this->empresaId}_{$this->waId}";
                $lastReplyTime = Cache::get($lastReplyKey);
                if ($lastReplyTime) {
                    $diff = now()->diffInSeconds($lastReplyTime, true);
                    if ($diff < 2) {
                        Log::channel("whatsapp")->info("ProcessWhatsAppChatbot: Espaciando respuestas para el usuario {$this->waId}. Re-encolando con retraso de 3s.", [
                            'diff_seconds' => $diff
                        ]);
                        $this->release(3);
                        return;
                    }
                }
            }

            // Validar Modo
            if ($empresa->whatsapp_chatbot_mode === 'off_hours' && $this->isBusinessHours($empresa)) {
                return;
            }

            if (($empresa->whatsapp_chatbot_type ?? 'ai') === 'menu') {
                try {
                    $this->handleMenuChatbot($empresa);
                } catch (\Throwable $e) {
                    Log::channel("whatsapp")->error("ProcessWhatsAppChatbot: Error en handleMenuChatbot", [
                        'empresa_id' => $this->empresaId,
                        'wa_id' => $this->waId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->sendReply("⚠️ Ocurrió un error interno. Por favor escribe *menu* para reiniciar la conversación.");
                }
                return;
            }

            try {
                $bot = new VircomBotService();
                $sessionId = "wa_chatbot_{$this->waId}";
                
                // Contexto con prompt personalizado
                $context = [
                    'custom_prompt' => $empresa->whatsapp_chatbot_prompt
                ];
                
                $response = $bot->getResponse($this->incomingMessage, $sessionId, $context);
                $botReply = $response['message'] ?? null;

                if ($botReply) {
                    $whatsappService = WhatsAppService::fromEmpresa($empresa);
                    $apiRes = $whatsappService->sendTextMessage($this->waId, $botReply);
                    
                    $messageId = $apiRes['messages'][0]['id'] ?? 'bot_' . time();

                    $chatMessage = WhatsAppChat::create([
                        'empresa_id' => $this->empresaId,
                        'wa_id' => $this->waId,
                        'body' => $botReply,
                        'direction' => 'outbound',
                        'type' => 'text',
                        'message_id' => $messageId,
                        'status' => 'sent',
                        'received_at' => now(),
                    ]);

                    // Actualizar conversación
                    WhatsAppConversation::where('wa_id', $this->waId)
                        ->where('empresa_id', $this->empresaId)
                        ->update(['last_message_at' => now()]);

                    Cache::put("whatsapp_last_reply_sent_{$this->empresaId}_{$this->waId}", now(), 60);

                    event(new WhatsAppMessageReceived($chatMessage));
                }
            } catch (\Exception $e) {
                Log::error("Chatbot Job Error: " . $e->getMessage());
                try {
                    $this->sendReply("⚠️ El asistente virtual no está disponible en este momento. Por favor intenta más tarde o escribe *menu*.");
                } catch (\Throwable $ignore) {}
            }
        } finally {
            \App\Support\EmpresaResolver::clearCache();
        }
    }


    protected function handleReagendarLista(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array($msg, ['menu', 'atras', 'atrás', '0'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->sendReply("🔙 Regresando al menú principal...");
            $this->handleMenuChatbot($empresa);
            return;
        }

        $cliente = $this->buscarClientePorWaId();
        if (!$cliente) {
            $this->sendReply("Para reagendar, escribe tu *folio de cita* o *menu* para volver.");
            Cache::put($stateKey, 'reagendar_lista', now()->addDay());
            return;
        }

        $citas = Cita::where('empresa_id', $this->empresaId)
            ->where('cliente_id', $cliente->id)
            ->whereIn('estado', ['programado', 'pendiente', 'confirmado'])
            ->whereDate('fecha_hora', '>=', today())
            ->orderBy('fecha_hora')
            ->get();

        if ($citas->isEmpty()) {
            $this->sendReply("📅 No tienes citas activas para reagendar. Escribe *menu* para volver.");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        Cache::put("{$stateKey}_reagendar_citas", $citas->toArray(), now()->addDay());

        $reply = "📅 *Tus citas activas:*\n\n";
        foreach ($citas as $idx => $cita) {
            $num = $idx + 1;
            $fecha = $cita->fecha_hora->format('d/m/Y H:i');
            $tipo = ucfirst(str_replace('_', ' ', $cita->tipo_servicio));
            $reply .= "{$num}️⃣ *{$tipo}* — {$fecha} ({$cita->folio})\n";
        }
        $reply .= "\nResponde el *número* de la cita que quieres reagendar.\n✏️ Escribe *menu* para volver.";
        $this->sendReply($reply);
        Cache::put($stateKey, 'reagendar_seleccion', now()->addDay());
    }

    protected function buscarClientePorWaId(): ?\App\Models\Cliente
    {
        $digitsOnly = preg_replace('/\D+/', '', $this->waId);
        if (strlen($digitsOnly) < 10) return null;
        $last10 = substr($digitsOnly, -10);

        // Buscar primero con LIKE simple (rápido)
        $cliente = \App\Models\Cliente::where('empresa_id', $this->empresaId)
            ->where('telefono', 'ilike', "%{$last10}%")
            ->first(['id', 'telefono', 'nombre_razon_social', 'email', 'calle', 'numero_exterior', 'colonia', 'codigo_postal']);

        if ($cliente) return $cliente;

        // Fallback: normalizar y buscar contra todos (lento pero seguro)
        $clientes = \App\Models\Cliente::where('empresa_id', $this->empresaId)->get(['id', 'telefono']);
        return $clientes->first(function ($c) use ($last10) {
            $cleanPhone = preg_replace('/\D+/', '', $c->telefono ?? '');
            return str_contains($cleanPhone, $last10);
        });
    }

    protected function sendInteractiveButtonsReply(string $bodyText, array $buttons, ?string $header = null, ?string $footer = null): void
    {
        try {
            $empresa = Empresa::find($this->empresaId);
            $whatsappService = WhatsAppService::fromEmpresa($empresa);
            $apiRes = $whatsappService->sendInteractiveButtons($this->waId, $bodyText, $buttons, $header, $footer);
            $messageId = $apiRes['messages'][0]['id'] ?? 'bot_' . time();

            $chatMessage = WhatsAppChat::create([
                'empresa_id' => $this->empresaId,
                'wa_id' => $this->waId,
                'body' => '[Botones] ' . mb_substr($bodyText, 0, 100),
                'direction' => 'outbound',
                'type' => 'interactive',
                'message_id' => $messageId,
                'status' => 'sent',
                'received_at' => now(),
            ]);

            WhatsAppConversation::where('wa_id', $this->waId)
                ->where('empresa_id', $this->empresaId)
                ->update(['last_message_at' => now()]);

            Cache::put("whatsapp_last_reply_sent_{$this->empresaId}_{$this->waId}", now(), 60);

            event(new WhatsAppMessageReceived($chatMessage));
        } catch (\Exception $e) {
            Log::error("Chatbot sendInteractiveButtonsReply Error: " . $e->getMessage());
            $this->sendReply($bodyText);
        }
    }

    protected function sendInteractiveListReply(string $bodyText, string $buttonText, array $sections, ?string $header = null, ?string $footer = null): void
    {
        try {
            $empresa = Empresa::find($this->empresaId);
            $whatsappService = WhatsAppService::fromEmpresa($empresa);
            $apiRes = $whatsappService->sendInteractiveList($this->waId, $bodyText, $buttonText, $sections, $header, $footer);
            $messageId = $apiRes['messages'][0]['id'] ?? 'bot_' . time();

            $chatMessage = WhatsAppChat::create([
                'empresa_id' => $this->empresaId,
                'wa_id' => $this->waId,
                'body' => '[Lista] ' . mb_substr($bodyText, 0, 100),
                'direction' => 'outbound',
                'type' => 'interactive',
                'message_id' => $messageId,
                'status' => 'sent',
                'received_at' => now(),
            ]);

            WhatsAppConversation::where('wa_id', $this->waId)
                ->where('empresa_id', $this->empresaId)
                ->update(['last_message_at' => now()]);

            Cache::put("whatsapp_last_reply_sent_{$this->empresaId}_{$this->waId}", now(), 60);

            event(new WhatsAppMessageReceived($chatMessage));
        } catch (\Exception $e) {
            Log::error("Chatbot sendInteractiveListReply Error: " . $e->getMessage());
            $this->sendReply($bodyText);
        }
    }

    protected function sendReply(string $replyText): void
    {
        try {
            $empresa = Empresa::find($this->empresaId);
            $whatsappService = WhatsAppService::fromEmpresa($empresa);
            $apiRes = $whatsappService->sendTextMessage($this->waId, $replyText);
            
            $messageId = $apiRes['messages'][0]['id'] ?? 'bot_' . time();

            $chatMessage = WhatsAppChat::create([
                'empresa_id' => $this->empresaId,
                'wa_id' => $this->waId,
                'body' => $replyText,
                'direction' => 'outbound',
                'type' => 'text',
                'message_id' => $messageId,
                'status' => 'sent',
                'received_at' => now(),
            ]);

            // Actualizar conversación
            WhatsAppConversation::where('wa_id', $this->waId)
                ->where('empresa_id', $this->empresaId)
                ->update(['last_message_at' => now()]);

            Cache::put("whatsapp_last_reply_sent_{$this->empresaId}_{$this->waId}", now(), 60);

            event(new WhatsAppMessageReceived($chatMessage));
        } catch (\Exception $e) {
            Log::error("Chatbot sendReply Error: " . $e->getMessage());
        }
    }

    protected function isBusinessHours(?Empresa $empresa = null): bool
    {
        $timezone = $empresa?->timezone ?: config('app.timezone', 'America/Hermosillo');
        $now = Carbon::now($timezone);
        $day = $now->dayOfWeek;
        $hour = $now->hour;

        // Lunes a Viernes: 9am - 6pm
        if ($day >= 1 && $day <= 5) {
            return $hour >= 9 && $hour < 18;
        }
        
        // Sábados: 9am - 2pm
        if ($day === 6) {
            return $hour >= 9 && $hour < 14;
        }

        // Domingos: Cerrado
        return false;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWhatsAppChatbot failed permanently (falló tras reintentos)', [
            'empresa_id' => $this->empresaId,
            'wa_id' => $this->waId,
            'error' => $exception->getMessage(),
        ]);

        try {
            $this->sendReply("⚠️ Ups, tuve un problema técnico procesando tu mensaje. Por favor escribe *menu* para intentar de nuevo.");
        } catch (\Throwable $ignore) {}
    }
}
