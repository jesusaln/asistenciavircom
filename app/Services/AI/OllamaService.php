<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;
    protected float $temperature;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'llama3.1');
        $this->temperature = (float) config('services.ollama.temperature', 0.7);
    }

    /**
     * Enviar un mensaje al chat de Ollama
     *
     * @param array $messages Array de mensajes [['role' => 'user', 'content' => '...']]
     * @param array $tools (Opcional) Definición de herramientas para Function Calling
     * @return array Respueta estructurada
     */
    public function chat(array $messages, array $tools = [])
    {
        try {
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'options' => [
                    'temperature' => $this->temperature,
                ]
            ];

            if (!empty($tools)) {
                $payload['tools'] = $tools;
            }

            Log::info('Ollama Request:', ['payload' => $payload]);

            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/api/chat", $payload);

            if ($response->failed()) {
                Log::error('Ollama API Error:', ['status' => $response->status(), 'body' => $response->body()]);
                return [
                    'success' => false,
                    'error' => 'Error comunicándose con el agente de IA.'
                ];
            }

            $data = $response->json();

            Log::info('Ollama Response:', ['response' => $data]);

            return [
                'success' => true,
                'message' => $data['message'] ?? null,
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Ollama Exception:', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Excepción interna del servicio de IA.'
            ];
        }
    }

    /**
     * Verificar si el servicio está disponible
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
