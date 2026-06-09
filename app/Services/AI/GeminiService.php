<?php

namespace App\Services\AI;

use App\Exceptions\AiServiceNotConfiguredException;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    protected float $temperature;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    protected bool $configured;

    public function __construct()
    {
        $config = null;
        try {
            $config = EmpresaConfiguracion::getConfig();
        } catch (Throwable $e) {
            // Silenciar errores de BD durante migraciones o testing
        }

        $this->apiKey = (string) ($config->gemini_api_key ?? config('services.gemini.api_key', ''));
        $this->model = (string) ($config->gemini_model ?? config('services.gemini.model', 'gemini-2.0-flash'));
        $this->temperature = (float) ($config->gemini_temperature ?? config('services.gemini.temperature', 0.7));
        $this->configured = $this->apiKey !== '';
    }

    /**
     * Enviar un mensaje al chat de Gemini
     *
     * @param  array  $messages  Array de mensajes en formato OpenAI o Gemini
     * @param  array  $tools  Definición de herramientas (opcional)
     */
    public function chat(array $messages, array $tools = [])
    {
        if (! $this->configured) {
            Log::warning('Gemini: API key no configurada o vacía');

            return ['success' => false, 'error' => 'API Key de Gemini no configurada.', 'code' => 'not_configured'];
        }

        try {
            $contents = $this->transformMessages($messages);

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => $this->temperature,
                    'topP' => 0.95,
                    'topK' => 64,
                    'maxOutputTokens' => 2048,
                ],
            ];

            if (! empty($tools)) {
                $payload['tools'] = [['functionDeclarations' => $this->transformTools($tools)]];
                $payload['toolConfig'] = ['functionCallingConfig' => ['mode' => 'AUTO']];
            }

            $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(30)->post($url, $payload);

            if ($response->failed()) {
                Log::error('Gemini API Error:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => false, 'error' => 'Error comunicándose con Gemini API.'];
            }

            $data = $response->json();
            $candidate = $data['candidates'][0] ?? null;

            if (! $candidate) {
                return ['success' => false, 'error' => 'Respuesta vacía de Gemini'];
            }

            $aiContent = $candidate['content']['parts'][0] ?? null;

            $message = ['role' => 'assistant', 'content' => $aiContent['text'] ?? ''];

            if (isset($aiContent['functionCall'])) {
                $message['tool_calls'] = [
                    [
                        'id' => 'call_'.uniqid(),
                        'type' => 'function',
                        'function' => [
                            'name' => $aiContent['functionCall']['name'],
                            'arguments' => $aiContent['functionCall']['args'],
                        ],
                    ],
                ];
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'message' => $message,
                    'raw' => $data,
                ],
            ];
        } catch (Throwable $e) {
            Log::error('Gemini Exception: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => 'Error interno del servicio de IA.'];
        }
    }

    private function transformMessages(array $messages): array
    {
        $contents = [];
        foreach ($messages as $msg) {
            $role = ($msg['role'] === 'user' || $msg['role'] === 'system') ? 'user' : 'model';

            $content = $msg['content'] ?? '';

            if ($msg['role'] === 'system') {
                $contents[] = ['role' => 'user', 'parts' => [['text' => 'INSTRUCCIONES DE SISTEMA: '.$content]]];
                $contents[] = ['role' => 'model', 'parts' => [['text' => 'Entendido, seguiré esas instrucciones.']]];

                continue;
            }

            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $content]],
            ];
        }

        return $contents;
    }

    private function transformTools(array $tools): array
    {
        $declarations = [];
        foreach ($tools as $tool) {
            $f = $tool['function'];
            $declarations[] = [
                'name' => $f['name'],
                'description' => $f['description'],
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => $this->fixPropertyTypes($f['parameters']['properties']),
                    'required' => $f['parameters']['required'] ?? [],
                ],
            ];
        }

        return $declarations;
    }

    private function fixPropertyTypes(array $props): array
    {
        foreach ($props as &$p) {
            if (isset($p['type'])) {
                $p['type'] = strtoupper($p['type']);
            }
        }

        return $props;
    }

    public function isAvailable(): bool
    {
        return $this->configured;
    }

    /**
     * @throws AiServiceNotConfiguredException
     */
    public function requireConfigured(): void
    {
        if (! $this->configured) {
            throw new AiServiceNotConfiguredException('Gemini API key no configurada.');
        }
    }
}
