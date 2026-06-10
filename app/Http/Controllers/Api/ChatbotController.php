<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\VircomBotService;

class ChatbotController extends Controller
{
    /**
     * Manejar la conversación del chatbot.
     */
    public function chat(Request $request, VircomBotService $bot)
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string'
        ]);

        $sessionId = $request->input('session_id', 'default_session');
        $userMessage = $request->input('message');

        // El servicio maneja historial, herramientas e IA
        $response = $bot->getResponse($userMessage, $sessionId);

        return response()->json([
            'message' => $response['message'],
            'action_taken' => $response['action'] ?? null
        ]);
    }

    /**
     * Manejar la conversación del chatbot de menú para la Web.
     */
    public function webMenuChat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'required|string'
        ]);

        $sessionId = $request->input('session_id');
        $userMessage = $request->input('message');

        $empresaId = \App\Support\EmpresaResolver::resolveId() ?? 1;
        \App\Support\EmpresaResolver::setContext($empresaId);

        try {
            $empresa = \App\Models\Empresa::find($empresaId);

            if (!$empresa) {
                return response()->json(['error' => 'Empresa no encontrada'], 404);
            }

            // Subclase local para ejecutar el chatbot de menú de forma síncrona
            $webBot = new class($empresaId, $sessionId, $userMessage) extends \App\Jobs\ProcessWhatsAppChatbot {
                public array $sentReplies = [];

                public function run($empresa)
                {
                    $this->handleMenuChatbot($empresa);
                }

                protected function sendReply(string $replyText): void
                {
                    $this->sentReplies[] = $replyText;
                }
            };

            $webBot->run($empresa);

            // Obtener el estado actual del bot para saber qué opciones/UI mostrar en el front-end
            $stateKey = "whatsapp_menu_state_{$empresaId}_{$sessionId}";
            $currentState = \Illuminate\Support\Facades\Cache::get($stateKey, 'menu');

            return response()->json([
                'replies' => $webBot->sentReplies,
                'state' => $currentState
            ]);
        } finally {
            \App\Support\EmpresaResolver::clearCache();
        }
    }
}

