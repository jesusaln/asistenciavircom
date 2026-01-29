<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class ChatController extends Controller
{
    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string'
        ]);

        $message = $request->message;
        $sessionId = $request->session_id ?? 'web-visitor-' . time();

        // Intentar rutas comunes de binarios
        $binary = 'clawdbot';
        if (file_exists('/usr/local/bin/clawdbot'))
            $binary = '/usr/local/bin/clawdbot';
        if (file_exists('/usr/bin/clawdbot'))
            $binary = '/usr/bin/clawdbot';

        $command = [
            $binary,
            'agent',
            '--message',
            $message,
            '--agent',
            'main',
            '--session-id',
            $sessionId,
        ];

        $process = new Process($command, base_path(), [
            'CLAWDBOT_WORKSPACE' => base_path('clawd'),
            'CLAWDBOT_CONFIG_DIR' => base_path('.clawdbot'),
            'HOME' => '/var/www' // Para que node/npm no intenten escribir en /root o similar
        ]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            \Illuminate\Support\Facades\Log::error('Clawdbot Error: ' . $process->getErrorOutput());
            return response()->json([
                'success' => false,
                'error' => 'No se pudo obtener respuesta de la IA.',
                'debug' => $process->getErrorOutput()
            ], 500);
        }

        $output = $process->getOutput();

        // Limpiar el output para obtener solo el mensaje de la IA
        // Clawdbot imprime varias líneas, el mensaje viene después del diamante ◇
        $cleanMessage = $this->parseClawdbotOutput($output);

        return response()->json([
            'success' => true,
            'message' => $cleanMessage,
            'session_id' => $sessionId
        ]);
    }

    private function parseClawdbotOutput($output)
    {
        $lines = explode("\n", $output);
        $resultLines = [];
        $foundDiamond = false;
        $inMessage = false;

        foreach ($lines as $line) {
            // Si hay diamante, es la marca oficial de inicio de mensaje
            if (str_contains($line, '◇')) {
                $foundDiamond = true;
                $inMessage = true;
                $content = trim(str_replace('◇', '', $line));
                if ($content !== '')
                    $resultLines[] = $content;
                continue;
            }

            // Si no hay diamante pero vemos que ya pasamos los logs de diagnóstico
            if (!$foundDiamond && !$inMessage) {
                $trimmed = trim($line);
                if (empty($trimmed))
                    continue;
                if (str_starts_with($trimmed, '🦞'))
                    continue;
                if (str_starts_with($trimmed, 'Gateway'))
                    continue;
                if (str_starts_with($trimmed, 'Source:'))
                    continue;
                if (str_starts_with($trimmed, 'Config:'))
                    continue;
                if (str_starts_with($trimmed, 'Bind:'))
                    continue;
                if (str_starts_with($trimmed, 'Updating'))
                    continue;

                // Empezamos a capturar a partir de la primera línea no decorativa
                $inMessage = true;
            }

            if ($inMessage) {
                if (str_contains($line, 'Exit code:'))
                    break;
                $resultLines[] = $line;
            }
        }

        $response = trim(implode("\n", $resultLines));

        if (empty($response)) {
            return "¡Hola! Tuve un pequeño contratiempo al procesar tu solicitud. Por favor, intenta escribirme de nuevo.";
        }

        return $response;
    }
}
