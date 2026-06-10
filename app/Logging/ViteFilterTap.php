<?php

namespace App\Logging;

use Monolog\LogRecord;

/**
 * Filtro para excluir mensajes de error relacionados con Vite
 *解决错误 #125: Logs inundados por errores de Vite
 */
class ViteFilterTap
{
    /**
     * Lista de patrones de mensajes a filtrar
     */
    private const VITE_PATTERNS = [
        'Vite',
        'vite',
        '/build/',
        'manifest.json',
        'http://localhost',
        'http://127.0.0.1:5173',
        'Mixed Content',
        'Failed to load resource',
        'net::ERR_',
    ];

    /**
     * Channels a excluir completamente
     */
    private const VITE_CHANNELS = [
        'vite',
        'webpack',
        'webpack-dev-server',
    ];

    /**
     * Customize the given Monolog instance.
     * 
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor([$this, 'filter']);
        }
    }

    /**
     * Procesa el registro de log antes de escribirlo
     * Retorna null para descartar el registro, o el registro modificado
     */
    public function filter(LogRecord $record): ?LogRecord
    {
        $message = $record->message;
        $channel = $record->channel ?? '';

        // Verificar si el canal es de Vite/Webpack
        foreach (self::VITE_CHANNELS as $viteChannel) {
            if (stripos($channel, $viteChannel) !== false) {
                return null; // Descartar completamente
            }
        }

        // Verificar si el mensaje contiene patrones de Vite
        foreach (self::VITE_PATTERNS as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return null; // Descartar el mensaje
            }
        }

        // Verificar contexto para errores de red relacionados con Vite
        $context = $record->context ?? [];
        if (isset($context['exception'])) {
            $exception = $context['exception'];
            if ($exception instanceof \Throwable) {
                $message = $exception->getMessage();
                foreach (self::VITE_PATTERNS as $pattern) {
                    if (strpos($message, $pattern) !== false) {
                        return null;
                    }
                }
            }
        }

        // Verificar URL en el contexto (indica recurso Vite)
        if (isset($context['url'])) {
            foreach (self::VITE_PATTERNS as $pattern) {
                if (strpos($context['url'], $pattern) !== false) {
                    return null;
                }
            }
        }

        return $record; // Mantener el registro
    }
}
