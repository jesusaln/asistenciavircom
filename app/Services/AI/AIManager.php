<?php

namespace App\Services\AI;

use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Log;

class AIManager
{
    /**
     * Obtener la instancia del proveedor de IA configurado.
     *
     * @param string|null $provider Nombre del proveedor (groq, gemini, ollama)
     * @return object
     */
    public function provider(?string $provider = null)
    {
        if (!$provider) {
            try {
                $config = EmpresaConfiguracion::getConfig();
                $provider = $config->ai_provider ?? config('services.ai_provider', 'groq');
            } catch (\Throwable $e) {
                $provider = config('services.ai_provider', 'groq');
            }
        }

        return match (strtolower($provider)) {
            'gemini' => app(GeminiService::class),
            'ollama' => app(OllamaService::class),
            default => app(GroqService::class),
        };
    }

    /**
     * Enviar un mensaje al chat utilizando el proveedor activo.
     *
     * @param array $messages Historial de mensajes [['role' => 'user', 'content' => '...']]
     * @param array $tools Definición de herramientas para Function Calling
     * @param string|null $provider Forzar un proveedor específico
     * @return array
     */
    public function chat(array $messages, array $tools = [], ?string $provider = null): array
    {
        return $this->provider($provider)->chat($messages, $tools);
    }

    /**
     * Verificar si el proveedor activo está disponible.
     *
     * @param string|null $provider
     * @return bool
     */
    public function isAvailable(?string $provider = null): bool
    {
        return $this->provider($provider)->isAvailable();
    }
}
