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
        $dbApiKey = '';
        $dbModel = '';
        $dbTemp = 0.7;

        try {
            $config = EmpresaConfiguracion::getConfig();
            $dbApiKey = (string) ($config->gemini_api_key ?? '');
            $dbModel = (string) ($config->gemini_model ?? '');
            $dbTemp = (float) ($config->gemini_temperature ?? 0.7);
        } catch (Throwable $e) {
            // Silenciar DecryptException o errores de BD y usar fallback del env
        }

        $this->apiKey = $dbApiKey !== '' ? $dbApiKey : (string) config('services.gemini.api_key', '');
        $this->model = $dbModel !== '' ? $dbModel : 'gemini-3-flash-preview';
        $this->temperature = $dbTemp > 0 ? $dbTemp : (float) config('services.gemini.temperature', 0.2);
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
                    'maxOutputTokens' => 8192,
                ],
            ];

            if (! empty($tools)) {
                $payload['tools'] = [['functionDeclarations' => $this->transformTools($tools)]];
                $payload['toolConfig'] = ['functionCallingConfig' => ['mode' => 'ANY']];
            }

            $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(120)->post($url, $payload);

            if ($response->failed()) {
                $errBody = $response->json();
                $errMsg = $errBody['error']['message'] ?? 'Error comunicándose con Gemini API.';
                
                Log::error('Gemini API Error:', [
                    'status' => $response->status(),
                    'body' => $errBody,
                ]);

                return ['success' => false, 'error' => $errMsg, 'status' => $response->status()];
            }

            $data = $response->json();
            $candidate = $data['candidates'][0] ?? null;

            if (! $candidate) {
                return ['success' => false, 'error' => 'Respuesta vacía de Gemini'];
            }

            $parts = $candidate['content']['parts'] ?? [];
            $textContent = '';
            $functionCall = null;

            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $textContent .= $part['text'] . ' ';
                }
                if (isset($part['functionCall'])) {
                    $functionCall = $part['functionCall'];
                }
            }

            $message = ['role' => 'assistant', 'content' => trim($textContent)];

            if ($functionCall) {
                $message['tool_calls'] = [
                    [
                        'id' => 'call_'.uniqid(),
                        'type' => 'function',
                        'function' => [
                            'name' => $functionCall['name'],
                            'arguments' => $functionCall['args'],
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

            if (isset($msg['parts']) && is_array($msg['parts'])) {
                $contents[] = [
                    'role' => $role,
                    'parts' => $msg['parts'],
                ];
                continue;
            }

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
                    'properties' => $this->fixPropertyTypes((array) ($f['parameters']['properties'] ?? [])),
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
