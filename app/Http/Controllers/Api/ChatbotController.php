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
}
