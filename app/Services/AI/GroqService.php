<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://api.groq.com/openai/v1';
    protected float $temperature;

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key', '');
        $this->model = config('services.groq.model', 'llama-3.1-70b-versatile');
        $this->temperature = (float) config('services.groq.temperature', 0.7);
    }

    /**
     * Enviar un mensaje al chat de Groq
     *
     * @param array $messages Array de mensajes [['role' => 'user', 'content' => '...']]
     * @param array $tools (Opcional) Definición de herramientas para Function Calling
     * @return array Respuesta estructurada
     */
    public function chat(array $messages, array $tools = [])
    {
        if (empty($this->apiKey)) {
            Log::error('Groq API Key no configurada');
            return [
                'success' => false,
                'error' => 'API Key de Groq no configurada.'
            ];
        }

        try {
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'max_tokens' => 1024,
            ];

            if (!empty($tools)) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            Log::info('Groq Request:', ['model' => $this->model, 'messages_count' => count($messages)]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                Log::error('Groq API Error:', ['status' => $response->status(), 'body' => $response->body()]);
                return [
                    'success' => false,
                    'error' => 'Error comunicándose con Groq API.'
                ];
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;

            if (!$choice) {
                return ['success' => false, 'error' => 'Respuesta vacía de Groq'];
            }

            Log::info('Groq Response:', ['usage' => $data['usage'] ?? [], 'finish_reason' => $choice['finish_reason'] ?? 'unknown']);

            // Formatear respuesta para compatibilidad con el controlador existente
            $message = $choice['message'];

            // Convertir tool_calls de formato OpenAI a formato esperado
            if (isset($message['tool_calls'])) {
                foreach ($message['tool_calls'] as &$toolCall) {
                    // Decodificar argumentos JSON string a array
                    if (isset($toolCall['function']['arguments']) && is_string($toolCall['function']['arguments'])) {
                        $toolCall['function']['arguments'] = json_decode($toolCall['function']['arguments'], true) ?? [];
                    }
                }
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'message' => $message,
                    'usage' => $data['usage'] ?? null
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Groq Exception:', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Excepción interna del servicio de IA: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si el servicio está disponible
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
