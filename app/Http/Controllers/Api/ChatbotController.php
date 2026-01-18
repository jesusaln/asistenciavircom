<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\OllamaService;
use App\Services\AI\GroqService;
use App\Models\Cita;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Cliente;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    protected $aiService;
    protected string $provider;

    public function __construct(OllamaService $ollama, GroqService $groq)
    {
        $this->provider = config('services.ai_provider', 'groq');
        $this->aiService = $this->provider === 'groq' ? $groq : $ollama;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|string'
        ]);

        $sessionId = $request->input('session_id', 'default_session');
        $userMessage = $request->input('message');

        // Historial básico (en producción usaría Cache o DB)
        $history = [
            [
                'role' => 'system',
                'content' => "Eres VircomBot, el asistente virtual de Asistencia Vircom.
                Tu objetivo es ayudar a los clientes a agendar citas, consultar precios y revisar el estado de sus reparaciones.
                Hoy es " . Carbon::now('America/Mexico_City')->format('l d \d\e F Y H:i') . ".
                
                Reglas:
                1. Sé amable, profesional y conciso.
                2. Para agendar citas, SIEMPRE verifica disponibilidad primero con la herramienta 'consultar_disponibilidad'.
                3. Si el usuario pregunta por precios, usa 'consultar_precios'.
                4. Si el usuario pregunta por el estado de su equipo, pide el número de folio/ticket y usa 'consultar_estado_reparacion'.
                5. Si no sabes algo, no inventes. Di que conectarás con un humano."
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ];

        // Definir Herramientas
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_disponibilidad',
                    'description' => 'Verifica si hay horarios disponibles para una fecha específica.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'fecha' => ['type' => 'string', 'description' => 'Fecha en formato YYYY-MM-DD'],
                        ],
                        'required' => ['fecha']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'agendar_cita',
                    'description' => 'Agenda una nueva cita de servicio.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'cliente_nombre' => ['type' => 'string', 'description' => 'Nombre del cliente'],
                            'telefono' => ['type' => 'string', 'description' => 'Teléfono del cliente'],
                            'fecha_hora' => ['type' => 'string', 'description' => 'Fecha y hora en formato YYYY-MM-DD HH:mm'],
                            'marca_equipo' => ['type' => 'string', 'description' => 'Marca del equipo (opcional)'],
                            'descripcion_problema' => ['type' => 'string', 'description' => 'Descripción del problema reportado'],
                        ],
                        'required' => ['cliente_nombre', 'telefono', 'fecha_hora', 'descripcion_problema']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_precios',
                    'description' => 'Busca precios de productos o servicios.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'termino' => ['type' => 'string', 'description' => 'Nombre del producto o servicio a buscar (ej. Mantenimiento, Gas R410A)'],
                        ],
                        'required' => ['termino']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_estado_reparacion',
                    'description' => 'Consulta el estado de una reparación por número de ticket o folio.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'folio' => ['type' => 'string', 'description' => 'Número de folio o ticket'],
                        ],
                        'required' => ['folio']
                    ]
                ]
            ]
        ];

        // Groq siempre soporta tools, Ollama depende del modelo
        $supportsTools = true;
        if ($this->provider === 'ollama') {
            $toolSupportedModels = ['llama3.1:8b', 'llama3.1:70b', 'qwen2.5:7b', 'mistral', 'mixtral'];
            $currentModel = config('services.ollama.model', 'llama3');
            $supportsTools = collect($toolSupportedModels)->contains(fn($m) => str_starts_with($currentModel, $m));
        }

        // 1. Llamar al servicio de IA (con o sin tools)
        if ($supportsTools) {
            $response = $this->aiService->chat($history, $tools);
        } else {
            $history[0]['content'] .= "\n\nNOTA: Si el cliente quiere agendar una cita, pídele sus datos y confirma que alguien del equipo lo contactará.";
            $response = $this->aiService->chat($history);
        }

        if (!$response['success']) {
            Log::error('VircomBot Error:', ['response' => $response]);
            return response()->json(['error' => 'No disponible por el momento.'], 503);
        }

        $aiMessage = $response['data']['message'];

        // 2. Verificar si quiere usar una herramienta
        if (isset($aiMessage['tool_calls']) && count($aiMessage['tool_calls']) > 0) {
            foreach ($aiMessage['tool_calls'] as $toolCall) {
                $functionName = $toolCall['function']['name'];
                $arguments = $toolCall['function']['arguments'];

                Log::info("AI ejecutando herramienta: $functionName", $arguments);

                $toolResult = null;

                try {
                    switch ($functionName) {
                        case 'consultar_disponibilidad':
                            $toolResult = $this->toolConsultarDisponibilidad($arguments['fecha']);
                            break;

                        case 'agendar_cita':
                            $toolResult = $this->toolAgendarCita($arguments);
                            break;

                        case 'consultar_precios':
                            $toolResult = $this->toolConsultarPrecios($arguments['termino']);
                            break;

                        case 'consultar_estado_reparacion':
                            $toolResult = $this->toolConsultarEstado($arguments['folio']);
                            break;
                    }

                    // Agregar resultado al historial - formato compatible con OpenAI/Groq
                    // El mensaje del asistente con tool_calls
                    $assistantMessage = [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => array_map(function ($tc) {
                            return [
                                'id' => $tc['id'] ?? 'call_' . uniqid(),
                                'type' => 'function',
                                'function' => [
                                    'name' => $tc['function']['name'],
                                    'arguments' => is_array($tc['function']['arguments'])
                                        ? json_encode($tc['function']['arguments'])
                                        : $tc['function']['arguments']
                                ]
                            ];
                        }, $aiMessage['tool_calls'])
                    ];
                    $history[] = $assistantMessage;

                    // Respuesta de la herramienta
                    $history[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'] ?? 'call_' . uniqid(),
                        'content' => json_encode($toolResult),
                    ];

                    // 3. Volver a llamar al servicio de IA con el resultado de la herramienta
                    $finalResponse = $this->aiService->chat($history);

                    if ($finalResponse['success']) {
                        $content = $finalResponse['data']['message']['content'] ?? 'Listo, he procesado tu solicitud.';
                        return response()->json([
                            'message' => $content,
                            'action_taken' => $functionName
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error("Error ejecutando herramienta $functionName: " . $e->getMessage());
                    return response()->json(['message' => 'Tuve un problema técnico al consultar esa información. ¿Podrías intentar de nuevo?']);
                }
            }
        }

        // Respuesta normal de texto
        return response()->json([
            'message' => $aiMessage['content'] ?? 'Lo siento, no pude procesar tu solicitud.'
        ]);
    }

    // ================== HERRAMIENTAS ==================

    protected function toolConsultarDisponibilidad($fecha)
    {
        $start = Carbon::parse($fecha)->startOfDay();
        $end = Carbon::parse($fecha)->endOfDay();

        $citasCount = Cita::whereBetween('fecha_hora', [$start, $end])
            ->where('estado', '!=', 'cancelado')
            ->count();

        // Lógica simple: si hay menos de 5 citas, hay lugar
        // En un sistema real, verificaríamos por técnico
        if ($citasCount >= 5) {
            return ['disponible' => false, 'mensaje' => 'Lo siento, ese día tenemos la agenda llena.'];
        }

        return ['disponible' => true, 'mensaje' => 'Sí, tenemos disponibilidad para esa fecha.'];
    }

    protected function toolAgendarCita($data)
    {
        // Buscar o crear cliente rápido
        $cliente = Cliente::firstOrCreate(
            ['telefono' => $data['telefono']],
            ['nombre_razon_social' => $data['cliente_nombre']]
        );

        $cita = Cita::create([
            'empresa_id' => 1, // Default por ahora
            'cliente_id' => $cliente->id,
            'fecha_hora' => $data['fecha_hora'],
            'descripcion' => $data['descripcion_problema'],
            'marca_equipo' => $data['marca_equipo'] ?? null,
            'estado' => 'pendiente',
            'origen_tienda' => 'Chatbot', // Identificador
            'es_publica' => true
        ]);

        return [
            'success' => true,
            'folio' => $cita->folio,
            'mensaje' => "Cita agendada con éxito. Tu folio es {$cita->folio}."
        ];
    }

    protected function toolConsultarPrecios($termino)
    {
        $servicios = Servicio::where('nombre', 'ILIKE', "%$termino%")
            ->where('estado', 'activo')
            ->take(3)
            ->get(['nombre', 'precio', 'descripcion']);

        if ($servicios->isEmpty()) {
            return ['encontrado' => false, 'mensaje' => 'No encontré servicios con ese nombre.'];
        }

        return ['encontrado' => true, 'resultados' => $servicios];
    }

    protected function toolConsultarEstado($folio)
    {
        $cita = Cita::where('folio', $folio)->first();
        if ($cita) {
            return ['encontrado' => true, 'tipo' => 'cita', 'estado' => $cita->estado, 'fecha' => $cita->fecha_hora];
        }

        // Si hubiera modelo Ticket:
        // $ticket = Ticket::where('id', $folio)->first();

        return ['encontrado' => false, 'mensaje' => 'No encontré ninguna orden o cita con ese folio.'];
    }
}
