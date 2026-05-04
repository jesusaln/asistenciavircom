<?php

namespace App\Jobs;

use App\Models\Empresa;
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

class ProcessWhatsAppChatbot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $empresa = Empresa::find($this->empresaId);
        if (!$empresa || !$empresa->whatsapp_chatbot_enabled) return;

        // Validar Modo
        if ($empresa->whatsapp_chatbot_mode === 'off_hours' && $this->isBusinessHours()) {
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

                event(new WhatsAppMessageReceived($chatMessage));
            }
        } catch (\Exception $e) {
            Log::error("Chatbot Job Error: " . $e->getMessage());
        }
    }

    protected function isBusinessHours(): bool
    {
        $now = Carbon::now('America/Mexico_City');
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
}
