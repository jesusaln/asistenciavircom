<?php

namespace App\Console\Commands;

use App\Models\WhatsAppChat;
use App\Models\ChatbotFeedback;
use Illuminate\Console\Command;

class MineChatbotFeedback extends Command
{
    protected $signature = 'chatbot:mine-feedback {--days=90 : Días hacia atrás para minar} {--dry-run : Solo mostrar, no guardar}';

    protected $description = 'Minar conversaciones históricas de WhatsApp para extraer feedback positivo y alimentar el bot';

    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $since = now()->subDays($days);

        $positivePatterns = [
            'gracias', 'perfecto', 'excelente', 'te agradezco', 'muy bien',
            'buen servicio', 'ok gracias', 'vale gracias', 'me ayudaste',
            'resolviste', 'quedó claro',
        ];

        // Buscar mensajes outbound del bot (contienen 🌵 o emojis típicos)
        $botMessages = WhatsAppChat::where('direction', 'outbound')
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->where('created_at', '>=', $since)
            ->where(function ($q) {
                $q->where('body', 'like', '%🌵%')
                  ->orWhere('body', 'like', '%📅%')
                  ->orWhere('body', 'like', '%💰%')
                  ->orWhere('body', 'like', '%🔧%')
                  ->orWhere('body', 'like', '%📋%')
                  ->orWhere('body', 'like', '%🛡%');
            })
            ->orderBy('created_at')
            ->get();

        $this->info("Encontrados {$botMessages->count()} mensajes del bot en {$days} días.");

        $saved = 0;
        $skipped = 0;

        foreach ($botMessages as $botMsg) {
            // Buscar el siguiente mensaje inbound del mismo wa_id
            $customerReply = WhatsAppChat::where('wa_id', $botMsg->wa_id)
                ->where('direction', 'inbound')
                ->where('created_at', '>', $botMsg->created_at)
                ->where('created_at', '<', $botMsg->created_at->addMinutes(30))
                ->orderBy('created_at')
                ->first();

            if (!$customerReply || empty($customerReply->body)) {
                $skipped++;
                continue;
            }

            $normalized = mb_strtolower(trim($customerReply->body));
            $sentiment = null;
            $trigger = null;

            foreach ($positivePatterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    $sentiment = 'positive';
                    $trigger = $pattern;
                    break;
                }
            }

            if (!$sentiment) {
                $skipped++;
                continue;
            }

            // Ya existe este par?
            $exists = ChatbotFeedback::where('user_message', $customerReply->body)
                ->where('assistant_response', $botMsg->body)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  ➤ Q: {$customerReply->body}");
                $this->line("    A: " . mb_substr($botMsg->body, 0, 80) . "...");
                $this->line("    Trigger: {$trigger}");
                $this->newLine();
                $saved++;
                continue;
            }

            ChatbotFeedback::create([
                'session_id' => 'historic_' . $botMsg->wa_id . '_' . $botMsg->id,
                'user_message' => mb_substr($customerReply->body, 0, 500),
                'assistant_response' => mb_substr($botMsg->body, 0, 1000),
                'sentiment' => 'positive',
                'trigger_phrase' => $trigger,
                'empresa_id' => $botMsg->empresa_id ?? null,
            ]);

            $saved++;
        }

        $this->info("✅ {$saved} pares Q&A guardados, {$skipped} saltados.");
    }
}
