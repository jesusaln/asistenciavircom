<?php

namespace App\Services\AI;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Services\AI\GroqService;
use App\Services\AI\OllamaService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class VircomBotService
{
    protected $aiService;
    protected string $provider;

    public function __construct()
    {
        $this->provider = config('services.ai_provider', 'groq');
        $this->aiService = $this->provider === 'groq' ? app(GroqService::class) : app(OllamaService::class);
    }

    public function getResponse(string $message, string $sessionId, array $context = [])
    {
        // 1. Obtener Historial de Caché
        $historyKey = "chatbot_history_{$sessionId}";
        $history = Cache::get($historyKey, []);

        if (empty($history)) {
            $history[] = [
                'role' => 'system',
                'content' => $this->getSystemPrompt($context)
            ];
        }

        $history[] = ['role' => 'user', 'content' => $message];

        // 2. Definir Herramientas
        $tools = $this->getTools();

        // 3. Llamar a la IA
        $response = $this->aiService->chat($history, $tools);

        if (!$response['success']) {
            return ['message' => 'Lo siento, mi cerebro está un poco cansado ahora mismo. ¿Podemos intentar en un momento?'];
        }

        $aiMessage = $response['data']['message'];

        // 4. Procesar Tool Calls (si existen)
        if (isset($aiMessage['tool_calls']) && count($aiMessage['tool_calls']) > 0) {
            foreach ($aiMessage['tool_calls'] as $toolCall) {
                $functionName = $toolCall['function']['name'];
                $arguments = $toolCall['function']['arguments'];

                Log::info("VircomBot ejecutando: $functionName", $arguments);

                $toolResult = $this->executeTool($functionName, $arguments);

                // Agregar interacción de herramienta al historial
                $history[] = [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [$toolCall]
                ];
                $history[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'] ?? 'call_' . uniqid(),
                    'content' => json_encode($toolResult),
                ];

                // Segunda llamada para que la IA genere la respuesta final en lenguaje natural
                $finalAiResponse = $this->aiService->chat($history);
                if ($finalAiResponse['success']) {
                    $aiMessage = $finalAiResponse['data']['message'];
                }
            }
        }

        // 5. Guardar texto final en historial y caché
        if (isset($aiMessage['content'])) {
            $history[] = ['role' => 'assistant', 'content' => $aiMessage['content']];
            // Mantener solo los últimos 10 mensajes para no saturar el contexto
            Cache::put($historyKey, array_slice($history, -11), now()->addHours(2));
        }

        return [
            'message' => $aiMessage['content'] ?? 'Entiendo, ¿en qué más puedo ayudarte?',
            'action' => $functionName ?? null
        ];
    }

    protected function getSystemPrompt(array $context = []): string
    {
        $now = Carbon::now('America/Mexico_City')->format('l d \d\e F Y H:i');
        $prompt = "Eres VircomBot 🤖, el experto asistente virtual de Asistencia Vircom.
        Tu misión es ser el brazo derecho de los clientes para:
        1. Agendar citas de mantenimiento y reparación.
        2. Consultar precios de servicios (minisplits, refrigeración, electricidad).
        3. Verificar el estado de sus reparaciones y saldo de pólizas de servicio.

        CONTEXTO ACTUAL: Hoy es $now.
        
        REGLAS DE ORO:
        - Sé extremadamente amable y servicial.
        - Usa Emojis para que la conversación sea amena.
        - HORARIOS DE ATENCIÓN: Lunes a Viernes (9:00 AM - 6:00 PM) y Sábados (9:00 AM - 2:00 PM). Domingos estamos cerrados.
        - Para agendar citas: SIEMPRE pide el nombre, teléfono y descripción del problema. 
        - RESTRICCIÓN DE Citas: Solo puedes agendar dentro del horario de atención. Si el cliente pide algo fuera de ese rango, explícale nuestros horarios y ofrece la opción más cercana disponible.
        - Para precios: Busca en el catálogo. Si no encuentras algo, ofrece que un humano los contacte.
        - NO inventes información técnica. Si no estás seguro, di que conectarás con un técnico especialista.";

        if (!empty($context['cliente'])) {
            $prompt .= "\nEstás hablando con el cliente: " . $context['cliente']->nombre_razon_social;
        }

        return $prompt;
    }

    protected function getTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_disponibilidad',
                    'description' => 'Verifica si hay horarios libres en una fecha determinada (YYYY-MM-DD).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'fecha' => ['type' => 'string']
                        ],
                        'required' => ['fecha']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'agendar_cita',
                    'description' => 'Agenda formalmente una cita en el sistema.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'cliente_nombre' => ['type' => 'string'],
                            'telefono' => ['type' => 'string'],
                            'fecha_hora' => ['type' => 'string', 'description' => 'ISO date time'],
                            'marca_equipo' => ['type' => 'string'],
                            'descripcion_problema' => ['type' => 'string'],
                        ],
                        'required' => ['cliente_nombre', 'telefono', 'fecha_hora', 'descripcion_problema']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_precios',
                    'description' => 'Obtiene precios vigentes del catálogo de servicios.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'termino' => ['type' => 'string', 'description' => 'Ej: Mantenimiento correctivo']
                        ],
                        'required' => ['termino']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_estado_reparacion',
                    'description' => 'Consulta el estado de una reparación o ticket por su número de folio.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'folio' => ['type' => 'string', 'description' => 'Número de folio (ej: TKT-123 o CITA-456)']
                        ],
                        'required' => ['folio']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_saldo_poliza',
                    'description' => 'Consulta las horas disponibles y vigencia de la póliza de servicio del cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'telefono' => ['type' => 'string', 'description' => 'Número de teléfono del cliente para buscar su póliza.']
                        ],
                        'required' => ['telefono']
                    ]
                ]
            ]
        ];
    }

    protected function executeTool(string $name, array $args)
    {
        switch ($name) {
            case 'consultar_disponibilidad':
                $date = Carbon::parse($args['fecha']);

                // Validar día laboral
                if ($date->isSunday()) {
                    return ['disponible' => false, 'mensaje' => 'Lo sentimos, los domingos no laboramos.'];
                }

                $count = Cita::whereDate('fecha_hora', $args['fecha'])->count();
                return [
                    'disponible' => $count < 6,
                    'cupos_ocupados' => $count,
                    'mensaje' => $count < 6 ? 'Hay disponibilidad para este día.' : 'Lo sentimos, ya tenemos la agenda llena para ese día.'
                ];

            case 'agendar_cita':
                $dateTime = Carbon::parse($args['fecha_hora']);

                // 1. Validar Día Laboral
                if ($dateTime->isSunday()) {
                    return ['success' => false, 'error' => 'No podemos agendar citas en domingo.'];
                }

                // 2. Validar Horas Laborales
                $hora = $dateTime->hour;
                $esSabado = $dateTime->isSaturday();

                $fueraDeHorario = false;
                if ($esSabado) {
                    if ($hora < 9 || $hora >= 14)
                        $fueraDeHorario = true;
                } else {
                    if ($hora < 9 || $hora >= 18)
                        $fueraDeHorario = true;
                }

                if ($fueraDeHorario) {
                    return [
                        'success' => false,
                        'error' => 'La hora solicitada está fuera de nuestro horario laboral. Laboramos de L-V 9am-6pm y Sábados 9am-2pm.'
                    ];
                }

                $cliente = Cliente::firstOrCreate(['telefono' => $args['telefono']], ['nombre_razon_social' => $args['cliente_nombre']]);
                $cita = Cita::create([
                    'empresa_id' => 1,
                    'cliente_id' => $cliente->id,
                    'fecha_hora' => $dateTime,
                    'descripcion' => $args['descripcion_problema'],
                    'marca_equipo' => $args['marca_equipo'] ?? 'No especificada',
                    'estado' => 'pendiente',
                    'origen_tienda' => 'VircomBot'
                ]);
                return ['success' => true, 'folio' => $cita->folio, 'mensaje' => '¡Excelente! Tu cita ha sido agendada.'];

            case 'consultar_precios':
                $servs = Servicio::where('nombre', 'ILIKE', '%' . $args['termino'] . '%')->take(3)->get(['nombre', 'precio']);
                return ['resultados' => $servs];

            case 'consultar_estado_reparacion':
                $folio = $args['folio'];

                // Buscar en Tickets primero
                $ticket = \App\Models\Ticket::where('folio', 'ILIKE', "%$folio%")->first();
                if ($ticket) {
                    return [
                        'tipo' => 'Ticket de Soporte',
                        'folio' => $ticket->folio,
                        'estado' => $ticket->estado,
                        'fecha' => $ticket->created_at->format('d/m/Y'),
                        'detalle' => $ticket->titulo
                    ];
                }

                // Buscar en Citas
                $cita = Cita::where('folio', 'ILIKE', "%$folio%")->first();
                if ($cita) {
                    return [
                        'tipo' => 'Cita de Servicio',
                        'folio' => $cita->folio,
                        'estado' => $cita->estado,
                        'fecha' => $cita->fecha_hora->format('d/m/Y H:i'),
                        'detalle' => $cita->descripcion
                    ];
                }

                return ['error' => 'No encontré ninguna reparación o cita con ese folio.'];

            case 'consultar_saldo_poliza':
                $telefono = $args['telefono'];
                // Limpiar teléfono (quitar +, espacios, etc.)
                $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);

                $cliente = Cliente::where('celular', 'ILIKE', "%$telefonoLimpio%")
                    ->orWhere('telefono', 'ILIKE', "%$telefonoLimpio%")
                    ->first();

                if (!$cliente) {
                    return ['error' => 'No encontré ningún cliente vinculado a este número de teléfono.'];
                }

                $poliza = \App\Models\PolizaServicio::where('cliente_id', $cliente->id)
                    ->where('estado', 'activa')
                    ->latest()
                    ->first();

                if (!$poliza) {
                    return ['error' => 'El cliente no tiene una póliza de servicio activa actualmente.'];
                }

                return [
                    'cliente' => $cliente->nombre_razon_social,
                    'poliza_nombre' => $poliza->nombre,
                    'folio' => $poliza->folio,
                    'horas_incluidas' => $poliza->horas_incluidas_mensual,
                    'horas_consumidas' => $poliza->horas_consumidas_mes,
                    'horas_disponibles' => $poliza->horas_disponibles,
                    'vigencia' => $poliza->fecha_fin ? $poliza->fecha_fin->format('d/m/Y') : 'Indefinida',
                    'reinicio' => "El día {$poliza->dia_cobro} de cada mes"
                ];

            default:
                return ['error' => 'Herramienta no encontrada'];
        }
    }
}
