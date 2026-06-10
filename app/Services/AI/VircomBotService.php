<?php

namespace App\Services\AI;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class VircomBotService
{
    protected ?string $currentSessionId = null;

    protected function resolveEmpresaId(): int
    {
        return \App\Support\EmpresaResolver::resolveId() ?? 1;
    }

    public function getResponse(string $message, string $sessionId, array $context = [])
    {
        $this->currentSessionId = $sessionId;

        // 1. Obtener Historial de Caché
        $historyKey = "chatbot_history_{$sessionId}";
        $history = Cache::get($historyKey, []);

        if (empty($history)) {
            $history[] = [
                'role' => 'system',
                'content' => $this->getSystemPrompt($context, $message)
            ];
        }

        $history[] = ['role' => 'user', 'content' => $message];

        // Detección pasiva de feedback del usuario
        $this->detectSentiment($sessionId, $message);

        // 2. Definir Herramientas
        $tools = $this->getTools();

        // 3. Llamar a la IA
        $response = AI::chat($history, $tools);

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
                $finalAiResponse = AI::chat($history);
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

    protected function getSystemPrompt(array $context = [], string $userMessage = ''): string
    {
        // 1. Usar prompt personalizado de la empresa si existe
        if (!empty($context['custom_prompt'])) {
            return $context['custom_prompt'];
        }

        // Resolver el nombre de la empresa contextualmente
        $empresaName = "nuestra empresa";
        try {
            $empresaId = \App\Support\EmpresaResolver::resolveId();
            $empresa = \App\Models\Empresa::find($empresaId);
            if ($empresa) {
                $empresaName = $empresa->nombre_razon_social;
            }
        } catch (\Throwable $e) {}

        $now = Carbon::now('America/Mexico_City')->format('l d \d\e F Y H:i');
        $prompt = "Eres el experto asistente virtual de {$empresaName}, una empresa de climatización.
        Tu misión es ser el brazo derecho de los clientes para:
        1. Agendar citas de mantenimiento, reparación e instalación.
        2. Consultar precios de servicios.
        3. Verificar el estado de sus reparaciones y saldo de pólizas de servicio.
        4. **CREAR COTIZACIONES**: Puedes buscar productos, preguntar qué necesita, y armar una cotización completa.

        CONTEXTO ACTUAL: Hoy es $now.
        
        REGLAS DE ORO:
        - Sé extremadamente amable y servicial. Usa Emojis 🌵
        - HORARIOS: Lun-Vie 9AM-6PM, Sáb 9AM-2PM, Dom cerrado.
        - Para agendar: pide nombre, teléfono y descripción del problema.
        - **PARA COTIZACIONES**: 
          1. Primero usa consultar_cliente para ver si ya existe
          2. Pregunta qué producto/servicio necesita (usa buscar_producto para mostrar opciones)
          3. Por cada item que elija, usa agregar_a_cotizacion
          4. Cuando termine, usa finalizar_cotizacion para crearla en el sistema
          5. Muestra el total al cliente
        - NO inventes información técnica. Si no sabes algo, ofrécele hablar con un asesor.
        - Si el cliente quiere agendar, consultar folio, ver catálogo o hablar con asesor, usa la herramienta de transición correspondiente.

        EJEMPLOS DE CÓMO RESPONDER:
        
        Ejemplo 1 — Consulta de precio:
        Cliente: \"¿Cuánto cuesta un mantenimiento?\"
        Tú: \"🌵 ¡Claro! Nuestro servicio de *Mantenimiento Preventivo* incluye:\n\n• Lavado a presión de evaporador y condensadora\n• Limpieza de drenaje y desinfección\n• Revisión de presiones de gas\n\n💰 *Precios:*\n• 1 Tonelada: \$500\n• 1.5 Toneladas: \$600\n• 2 Toneladas: \$700\n\n¿De qué capacidad es tu equipo? ¿Te agendo la cita?\"
        
        Ejemplo 2 — Agendar cita:
        Cliente: \"Quiero agendar un mantenimiento\"
        Tú: \"📅 ¡Con gusto! Para agendar tu cita necesito algunos datos:\n\n¿Me compartes tu *nombre completo* y *teléfono*? Así busco tu cuenta y programamos tu visita. 🌵\"
        
        Ejemplo 3 — No se puede ayudar:
        Cliente: \"¿Tienen refrigeración para cuarto frío industrial?\"
        Tú: \"Entiendo, necesitas algo muy específico. Déjame conectarte con un *asesor especializado* que puede darte una cotización exacta para ese proyecto. 🌵\" [usa la herramienta de transición para hablar con asesor]
        
        Ejemplo 4 — Queja o problema:
        Cliente: \"Ya van 2 veces que vienen y no arreglan mi minisplit\"
        Tú: \"Lamento mucho esa experiencia 😔. Déjame revisar tu historial y escalar esto de inmediato con un *supervisor* para que tengas una solución prioritaria. ¿Me das tu nombre o folio de servicio? 🌵\"";

        // Inyectar ejemplos de conversaciones exitosas relevantes (RAG semántico)
        $recentExamples = $this->getSemanticExamples($userMessage);
        if (!empty($recentExamples)) {
            $prompt .= "\n\nEJEMPLOS RECIENTES DE CONVERSACIONES EXITOSAS (aprende de estos):\n" . $recentExamples;
        }

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
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'iniciar_flujo_agendamiento',
                    'description' => 'Ejecutar si el cliente expresa la intención clara de agendar o programar una nueva cita de servicio.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'iniciar_flujo_folio',
                    'description' => 'Ejecutar si el cliente expresa interés en consultar el estado de su cita, ticket, reparación o folio de servicio.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'iniciar_flujo_catalogo',
                    'description' => 'Ejecutar si el cliente expresa interés en ver nuestro catálogo de precios, productos o tarifas de servicios.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'conectar_con_asesor',
                    'description' => 'Ejecutar si el cliente solicita de forma explícita hablar con un asesor de servicio humano, persona real, o agente real.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object) [],
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'buscar_producto',
                    'description' => 'Busca productos o servicios en el catálogo por nombre o palabra clave. Devuelve ID, nombre y precio.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'termino' => ['type' => 'string', 'description' => 'Palabra clave para buscar (ej: minisplit 1 ton, mantenimiento, instalación)'],
                            'tipo' => ['type' => 'string', 'enum' => ['producto', 'servicio', 'cualquiera'], 'description' => 'Tipo de item a buscar']
                        ],
                        'required' => ['termino']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'agregar_a_cotizacion',
                    'description' => 'Agrega un producto o servicio a la cotización temporal del cliente. Usa esta función DESPUÉS de buscar_producto para ir armando la cotización.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'item_id' => ['type' => 'integer', 'description' => 'ID del producto o servicio obtenido de buscar_producto'],
                            'tipo' => ['type' => 'string', 'enum' => ['producto', 'servicio']],
                            'cantidad' => ['type' => 'integer', 'description' => 'Cantidad a cotizar'],
                            'precio' => ['type' => 'number', 'description' => 'Precio unitario del item']
                        ],
                        'required' => ['item_id', 'tipo', 'cantidad', 'precio']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'finalizar_cotizacion',
                    'description' => 'FINALIZA Y CREA la cotización en el sistema con todos los items agregados previamente con agregar_a_cotizacion. SOLO llamar cuando el cliente haya confirmado todos los items.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'cliente_nombre' => ['type' => 'string', 'description' => 'Nombre del cliente'],
                            'cliente_telefono' => ['type' => 'string', 'description' => 'Teléfono del cliente'],
                            'notas' => ['type' => 'string', 'description' => 'Notas adicionales para la cotización'],
                        ],
                        'required' => ['cliente_nombre', 'cliente_telefono']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_cliente',
                    'description' => 'Busca un cliente en el sistema por teléfono o nombre. Si no existe, devuelve los datos disponibles para crear uno nuevo.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'telefono' => ['type' => 'string', 'description' => 'Número de teléfono del cliente'],
                            'nombre' => ['type' => 'string', 'description' => 'Nombre del cliente (opcional)']
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
                $empresaId = $this->resolveEmpresaId();

                if ($date->isSunday()) {
                    return ['disponible' => false, 'mensaje' => 'Lo sentimos, los domingos no laboramos.'];
                }

                try {
                    $dispService = app(\App\Services\DisponibilidadService::class);
                    $slots = $dispService->getHorariosDisponibles($empresaId, $date->format('Y-m-d'));
                    $disponibles = array_filter($slots, fn($s) => !empty($s['disponible']));
                    $totalSlots = count($slots);
                    $slotsLibres = count($disponibles);
                    return [
                        'disponible' => $slotsLibres > 0,
                        'slots_totales' => $totalSlots,
                        'slots_libres' => $slotsLibres,
                        'mensaje' => $slotsLibres > 0
                            ? "Hay {$slotsLibres} horarios disponibles para este día."
                            : 'Lo sentimos, ya tenemos la agenda llena para ese día.'
                    ];
                } catch (\Throwable $e) {
                    // Fallback simple si el servicio no está disponible
                    $count = Cita::where('empresa_id', $empresaId)->whereDate('fecha_hora', $args['fecha'])->count();
                    return [
                        'disponible' => $count < 6,
                        'cupos_ocupados' => $count,
                        'mensaje' => $count < 6 ? 'Hay disponibilidad para este día.' : 'Lo sentimos, ya tenemos la agenda llena para ese día.'
                    ];
                }

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

                // 3. Validar disponibilidad real
                try {
                    $dispService = app(\App\Services\DisponibilidadService::class);
                    $empresaId = $this->resolveEmpresaId();
                    $fecha = $dateTime->format('Y-m-d');
                    $slots = $dispService->getHorariosDisponibles($empresaId, $fecha);
                    $horaSolicitada = $dateTime->format('H:i:00');
                    $hayDisponibilidad = false;
                    foreach ($slots as $slot) {
                        if (($slot['hora_inicio'] ?? '') === $horaSolicitada && !empty($slot['disponible'])) {
                            $hayDisponibilidad = true;
                            break;
                        }
                    }
                    if (!$hayDisponibilidad) {
                        return [
                            'success' => false,
                            'error' => 'Lo siento, ese horario ya no está disponible. ¿Te sirve otro día u hora?'
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning("VircomBot: No se pudo verificar disponibilidad: " . $e->getMessage());
                }

                $cliente = Cliente::firstOrCreate(
                    ['telefono' => $args['telefono'], 'empresa_id' => $this->resolveEmpresaId()],
                    ['nombre_razon_social' => $args['cliente_nombre']]
                );
                $cita = Cita::create([
                    'empresa_id' => $this->resolveEmpresaId(),
                    'cliente_id' => $cliente->id,
                    'fecha_hora' => $dateTime,
                    'descripcion' => $args['descripcion_problema'],
                    'marca_equipo' => $args['marca_equipo'] ?? 'No especificada',
                    'estado' => 'pendiente',
                ]);
                $cita->refresh();
                return ['success' => true, 'folio' => $cita->folio, 'mensaje' => '¡Excelente! Tu cita ha sido agendada.'];

            case 'consultar_precios':
                $servs = Servicio::where('empresa_id', $this->resolveEmpresaId())
                    ->where('nombre', 'ILIKE', '%' . $args['termino'] . '%')
                    ->take(3)->get(['nombre', 'precio']);
                return ['resultados' => $servs];

            case 'consultar_estado_reparacion':
                $folio = trim($args['folio']);
                $empresaId = $this->resolveEmpresaId();

                $ticket = \App\Models\Ticket::where('empresa_id', $empresaId)
                    ->where('folio', $folio)
                    ->first();
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
                $cita = Cita::where('empresa_id', $empresaId)
                    ->where('folio', $folio)
                    ->first();
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
                $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);
                $empresaId = $this->resolveEmpresaId();

                $cliente = Cliente::where('empresa_id', $empresaId)
                    ->where(function ($q) use ($telefonoLimpio) {
                        $q->where('celular', 'ILIKE', "%{$telefonoLimpio}%")
                          ->orWhere('telefono', 'ILIKE', "%{$telefonoLimpio}%");
                    })
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

            case 'buscar_producto':
                $termino = $args['termino'];
                $tipo = $args['tipo'] ?? 'cualquiera';
                $empresaId = $this->resolveEmpresaId();
                $resultados = [];
                if ($tipo !== 'servicio') {
                    $prods = Producto::where('empresa_id', $empresaId)
                        ->where('estado', 'activo')
                        ->where('nombre', 'ILIKE', "%{$termino}%")
                        ->take(5)->get(['id', 'nombre', 'precio_venta']);
                    foreach ($prods as $p) {
                        $resultados[] = ['id' => $p->id, 'nombre' => $p->nombre, 'precio' => $p->precio_venta, 'tipo' => 'producto'];
                    }
                }
                if ($tipo !== 'producto') {
                    $servs = Servicio::where('empresa_id', $empresaId)
                        ->where('nombre', 'ILIKE', "%{$termino}%")
                        ->take(5)->get(['id', 'nombre', 'precio']);
                    foreach ($servs as $s) {
                        $resultados[] = ['id' => $s->id, 'nombre' => $s->nombre, 'precio' => $s->precio, 'tipo' => 'servicio'];
                    }
                }
                return ['resultados' => $resultados];

            case 'agregar_a_cotizacion':
                $cotKey = "cotizacion_temp_{$this->currentSessionId}";
                $items = Cache::get($cotKey, []);
                $items[] = [
                    'item_id' => $args['item_id'],
                    'tipo' => $args['tipo'],
                    'cantidad' => $args['cantidad'],
                    'precio' => $args['precio'],
                    'subtotal' => $args['cantidad'] * $args['precio'],
                ];
                Cache::put($cotKey, $items, now()->addHours(2));
                return ['success' => true, 'items_count' => count($items), 'mensaje' => 'Item agregado a la cotización.'];

            case 'finalizar_cotizacion':
                $cotKey = "cotizacion_temp_{$this->currentSessionId}";
                $items = Cache::get($cotKey, []);
                if (empty($items)) {
                    return ['error' => 'No hay items en la cotización temporal.'];
                }
                $telefono = preg_replace('/[^0-9]/', '', $args['cliente_telefono']);
                $empresaId = $this->resolveEmpresaId();
                $cliente = \App\Models\Cliente::firstOrCreate(
                    ['telefono' => $telefono, 'empresa_id' => $empresaId],
                    ['nombre_razon_social' => $args['cliente_nombre']]
                );
                if (!$cliente->wasRecentlyCreated && empty($cliente->nombre_razon_social)) {
                    $cliente->update(['nombre_razon_social' => $args['cliente_nombre']]);
                }
                $subtotal = collect($items)->sum('subtotal');
                $descuento = 0;
                $iva = round(($subtotal - $descuento) * 0.16, 2);
                $total = $subtotal - $descuento + $iva;
                $cotizacion = \App\Models\Cotizacion::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'subtotal' => $subtotal,
                    'descuento_general' => $descuento,
                    'iva' => $iva,
                    'total' => $total,
                    'estado' => 'pendiente',
                    'notas' => $args['notas'] ?? 'Creado desde WhatsApp AI',
                ]);
                foreach ($items as $item) {
                    $modelClass = $item['tipo'] === 'producto' ? Producto::class : Servicio::class;
                    \App\Models\CotizacionItem::create([
                        'cotizacion_id' => $cotizacion->id,
                        'cotizable_id' => $item['item_id'],
                        'cotizable_type' => $modelClass,
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'descuento' => 0,
                        'subtotal' => $item['subtotal'],
                        'descuento_monto' => 0,
                    ]);
                }
                Cache::forget($cotKey);
                return ['success' => true, 'folio' => $cotizacion->numero_cotizacion ?? "#{$cotizacion->id}", 'total' => $total, 'mensaje' => "Cotización creada exitosamente. Total: \${$total}"];

            case 'consultar_cliente':
                $telefono = preg_replace('/[^0-9]/', '', $args['telefono']);
                $cliente = \App\Models\Cliente::where('empresa_id', $this->resolveEmpresaId())
                    ->where('telefono', 'ILIKE', "%{$telefono}%")
                    ->first();
                if ($cliente) {
                    return ['existe' => true, 'id' => $cliente->id, 'nombre' => $cliente->nombre_razon_social, 'telefono' => $cliente->telefono];
                }
                return ['existe' => false, 'nombre' => $args['nombre'] ?? null, 'telefono' => $telefono, 'mensaje' => 'Cliente no encontrado. Se usará este teléfono para crear la cotización.'];

            case 'iniciar_flujo_agendamiento':
            case 'iniciar_flujo_folio':
            case 'iniciar_flujo_catalogo':
            case 'conectar_con_asesor':
                return ['success' => true, 'mensaje' => 'Transición iniciada en el sistema.'];

            default:
                return ['error' => 'Herramienta no encontrada'];
        }
    }

    protected function detectSentiment(string $sessionId, string $message): void
    {
        $normalized = mb_strtolower(trim($message));
        
        $positivePatterns = [
            'gracias', 'perfecto', 'excelente', 'te agradezco', 'muy bien',
            'buen servicio', '👍', 'ok gracias', 'vale gracias', 'me ayudaste',
            'resolviste', 'quedó claro', 'sos genial', 'eres genial',
        ];
        
        $negativePatterns = [
            'no entiendo', 'no entendí', 'no sirve', 'no funciona', 'mal',
            'pésimo', 'inútil', 'no me ayudaste', 'no resuelves', 'no sabes',
            'quiero hablar con una persona', 'asesor real', 'humano',
            'no me sirvió', 'no me ayudó', 'esto no funciona',
        ];
        
        $sentiment = null;
        $trigger = null;
        
        foreach ($positivePatterns as $pattern) {
            if (str_contains($normalized, $pattern)) {
                $sentiment = 'positive';
                $trigger = $pattern;
                break;
            }
        }
        
        if (!$sentiment) {
            foreach ($negativePatterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    $sentiment = 'negative';
                    $trigger = $pattern;
                    break;
                }
            }
        }
        
        if ($sentiment) {
            try {
                // Capturar la última respuesta del bot para ejemplos de aprendizaje
                $lastBotReply = null;
                $historyKey = "chatbot_history_{$sessionId}";
                $history = Cache::get($historyKey, []);
                // Buscar el último mensaje del assistant en el historial
                for ($i = count($history) - 1; $i >= 0; $i--) {
                    if (($history[$i]['role'] ?? '') === 'assistant' && !empty($history[$i]['content'])) {
                        $lastBotReply = $history[$i]['content'];
                        break;
                    }
                }
                
                \App\Models\ChatbotFeedback::create([
                    'session_id' => $sessionId,
                    'user_message' => mb_substr($message, 0, 500),
                    'assistant_response' => $lastBotReply ? mb_substr($lastBotReply, 0, 1000) : null,
                    'sentiment' => $sentiment,
                    'trigger_phrase' => $trigger,
                    'empresa_id' => \App\Support\EmpresaResolver::resolveId(),
                ]);
            } catch (\Throwable $e) {
                Log::warning("ChatbotFeedback: No se pudo guardar feedback: " . $e->getMessage());
            }
        }
    }

    protected function getRecentPositiveExamples(): string
    {
        return $this->getSemanticExamples(null);
    }

    protected function getSemanticExamples(?string $userMessage = null): string
    {
        try {
            $query = \App\Models\ChatbotFeedback::where('sentiment', 'positive')
                ->whereNotNull('assistant_response')
                ->whereRaw("length(assistant_response) > 20")
                ->orderByDesc('created_at')
                ->limit(3);

            $examples = $query->get();

            if ($examples->isEmpty()) {
                return '';
            }

            $text = '';
            foreach ($examples as $ex) {
                $userMsg = mb_substr($ex->user_message, 0, 150);
                $botReply = mb_substr($ex->assistant_response, 0, 300);
                $text .= "\nCliente: \"{$userMsg}\"\nTú: \"{$botReply}\"\n";
            }
            return $text;
        } catch (\Throwable $e) {
            return '';
        }
    }
}
