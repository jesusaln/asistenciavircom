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

        // Ejecutar clawdbot agent
        $command = [
            '/home/vircom/.nvm/versions/node/v24.13.0/bin/clawdbot',
            'agent',
            '--message',
            $message,
            '--agent',
            'main',
            '--session-id',
            $sessionId
        ];

        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo obtener respuesta de la IA.'
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
        // Dividir por líneas
        $lines = explode("\n", $output);
        $foundDiamond = false;
        $resultLines = [];

        foreach ($lines as $line) {
            if (str_contains($line, '◇')) {
                $foundDiamond = true;
                // Intentar quitar el diamante y espacios si están en la misma línea
                $content = trim(str_replace('◇', '', $line));
                if ($content !== '') {
                    $resultLines[] = $content;
                }
                continue;
            }

            if ($foundDiamond) {
                $trimmed = trim($line);
                // Si encontramos códigos de salida o líneas vacías al final, paramos o filtramos
                if (str_contains($line, 'Exit code:'))
                    break;

                $resultLines[] = $line;
            }
        }

        $response = trim(implode("\n", $resultLines));

        if (empty($response)) {
            return "Lo siento, tuve un problema procesando tu mensaje. ¿Puedes intentar de nuevo?";
        }

        return $response;
    }
}
