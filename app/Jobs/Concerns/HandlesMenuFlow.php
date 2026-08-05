<?php

namespace App\Jobs\Concerns;

use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\Ticket;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Categoria;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppChat;
use App\Services\WhatsAppService;
use App\Services\AI\VircomBotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\EncuestaSatisfaccion;
use Illuminate\Support\Str;

trait HandlesMenuFlow
{
    protected function handleMenuChatbot(Empresa $empresa): void
    {
        $stateKey = "whatsapp_menu_state_{$this->empresaId}_{$this->waId}";
        $state = Cache::get($stateKey, 'menu');
        $msg = trim(strtolower($this->incomingMessage));

        if ($this->handleSatisfactionFlow($state, $msg, $stateKey)) {
            return;
        }

        if ($this->handleSurveyFlow($state, $msg, $stateKey)) {
            return;
        }

        Log::info("CHATBOT MENU START", [
            'empresa_id' => $this->empresaId,
            'wa_id' => $this->waId,
            'state_key' => $stateKey,
            'current_state' => $state,
            'incoming_msg' => $this->incomingMessage,
            'cleaned_msg' => $msg,
        ]);

        if ($state === 'human') {
            $humanSince = Cache::get("{$stateKey}_human_since", now());
            $minutosEnHuman = now()->diffInMinutes($humanSince, true);
            if ($minutosEnHuman >= 60) {
                Cache::put($stateKey, 'menu', now()->addDay());
                Cache::forget("{$stateKey}_human_since");
                Cache::forget("whatsapp_human_active_{$this->empresaId}_{$this->waId}");
                $this->sendReply("👤 *Atención Humana:\n\n" .
                    "Pasó 1 hora y nuestros asesores están ocupados. Yo puedo seguir ayudándote:\n\n" .
                    $this->textoMenuCompleto(false));
                return;
            }
            return;
        }

        if (($msg === 'agendar' || $msg === 'agendar cita' || $msg === 'agendar una cita') && !str_starts_with($msg, 'agendar_')) {
            $cliente = $this->buscarClientePorWaId();
            $citasActivas = collect();
            if ($cliente) {
                $citasActivas = Cita::where('empresa_id', $this->empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->whereIn('estado', ['programado', 'pendiente', 'confirmado'])
                    ->whereDate('fecha_hora', '>=', today())
                    ->orderBy('fecha_hora')
                    ->get();
            }

            if ($citasActivas->isNotEmpty()) {
                $cita = $citasActivas->first();
                $fecha = $cita->fecha_hora->format('d/m/Y H:i');
                $this->sendReply("📅 Tienes una cita próxima:\n\n• *{$cita->tipo_servicio}*\n• *{$fecha}*\n• Folio: {$cita->folio}");
                $this->sendReply("📅 *Agendar otra cita*\n\nResponde el *número* del tipo de servicio:\n\n1️⃣ *Instalación*\n2️⃣ *Mantenimiento*\n3️⃣ *Reparación*\n4️⃣ *Otro*");
                Cache::put($stateKey, 'agendar_tipo', now()->addDay());
                return;
            }

            $this->sendReply("📅 ¿Qué tipo de servicio necesitas?\n\n1️⃣ *Instalación*\n2️⃣ *Mantenimiento*\n3️⃣ *Reparación*\n4️⃣ *Otro*\n\nResponde el *número* del servicio.");
            Cache::put($stateKey, 'agendar_tipo', now()->addDay());
            return;
        }

        if ($msg === 'mantenimiento' && !str_starts_with($msg, 'agendar_')) {
            $cliente = $this->buscarClientePorWaId();
            $fechaInstalacion = null;
            if ($cliente) {
                $instalacion = Cita::where('empresa_id', $this->empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->where('tipo_servicio', 'instalacion')
                    ->orderByDesc('fecha_hora')
                    ->first();
                if ($instalacion) {
                    $fechaInstalacion = $instalacion->fecha_hora;
                }
            }

            $tz = $empresa->timezone ?: config('app.timezone', 'America/Hermosillo');
            $baseFecha = $fechaInstalacion ? Carbon::parse($fechaInstalacion) : Carbon::today($tz);
            $fecha6m = $baseFecha->copy()->addMonths(6)->format('d/m/Y');

            $this->sendReply("📅 ¡Perfecto! Vamos a dejar programado tu primer Mantenimiento Preventivo de una vez para proteger tu garantía de fabricante.\n\n" .
                             ($fechaInstalacion ? "Detectamos tu fecha de instalación el *{$fechaInstalacion->format('d/m/Y')}*, por lo que tu servicio corresponde programarse a partir del *{$fecha6m}*." : "Tu servicio corresponde programarse a partir de 6 meses desde hoy (*{$fecha6m}*)."));

            Cache::put("{$stateKey}_mantenimiento_preventivo_6m", true, now()->addDay());
            Cache::put("{$stateKey}_tipo", 'Mantenimiento', now()->addDay());
            Cache::put($stateKey, 'agendar_fecha', now()->addDay());

            $this->mostrarCalendarioDisponible($empresa, $stateKey);
            return;
        }

        $isNumericOption = preg_match('/^[1-9]$/', $msg);
        $isInteractive = str_starts_with($msg, 'agendar_') || str_starts_with($msg, 'reagendar_');

        $normalizedMsg = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $msg);
        $normalizedLower = strtolower($normalizedMsg);
        $isThanks = in_array($normalizedLower, ['gracias', 'muchas gracias', 'ok gracias', 'graciass', 'ty', 'thank you', 'thanks']);
        $isReset = $isThanks || in_array($normalizedLower, ['hola', 'buenas', 'menu', 'menú', 'inicio', 'buenos dias', 'buenas tardes', 'buenas noches', 'recomenzar', 'menu_back']);

        if (!$isNumericOption && !$isInteractive && !$isReset && in_array($state, ['menu', 'menu_select'])) {
            try {
                $bot = new VircomBotService();
                $sessionId = "wa_chatbot_{$this->waId}";
                $cliente = $this->buscarClientePorWaId();
                $context = [
                    'custom_prompt' => $empresa->whatsapp_chatbot_prompt,
                    'cliente' => $cliente
                ];

                $response = $bot->getResponse($this->incomingMessage, $sessionId, $context);
                $botReply = $response['message'] ?? null;
                $action = $response['action'] ?? null;

                if ($action) {
                    if ($action === 'iniciar_flujo_agendamiento') {
                        Cache::put($stateKey, 'agendar_tipo', now()->addDay());
                        $this->sendReply("📅 ¡Perfecto! Vamos a agendar tu cita.\n\n¿Qué tipo de servicio necesitas?\n\n1️⃣ *Instalación*\n2️⃣ *Mantenimiento*\n3️⃣ *Reparación*\n4️⃣ *Otro*\n\nResponde el *número* del servicio.");
                        return;
                    }
                    if ($action === 'iniciar_flujo_folio') {
                        Cache::put($stateKey, 'waiting_for_folio', now()->addDay());
                        $this->sendReply("🔍 *Consultar Folio*\n\nPor favor escribe tu número de teléfono de 10 dígitos (ej: 6621234567) o el folio del servicio (ej: T-001, CITA-123).\n\nO escribe *menu* para volver.");
                        return;
                    }
                    if ($action === 'iniciar_flujo_catalogo') {
                        $reply = "💰 *Catálogo y Cotización* 🌵\n\n" .
                                 "Selecciona una opción:\n\n" .
                                 "1️⃣ *Explorar Productos y Precios*\n" .
                                 "2️⃣ *Cotizar Instalación*\n" .
                                 "3️⃣ *Cotizar Mantenimiento*\n" .
                                 "4️⃣ *Cotizar Solo Equipo*\n" .
                                 "5️⃣ *Ver Promociones Vigentes*\n\n" .
                                 "Responde el *número* o escribe *menu* para volver.";
                        $this->sendReply($reply);
                        Cache::put($stateKey, 'catalogo_presupuesto_menu', now()->addDay());
                        return;
                    }
                    if ($action === 'conectar_con_asesor') {
                        $this->conectarAsesor($empresa, $stateKey);
                        return;
                    }
                }

                if ($botReply && !str_contains($botReply, 'cerebro está un poco cansado')) {
                    $this->sendReply($botReply);
                    Cache::put($stateKey, 'menu_select', now()->addDay());
                    return;
                }
            } catch (\Throwable $e) {
                Log::error("Hybrid Chatbot AI error: " . $e->getMessage());
            }
        }

        $saludoPre = "";
        if ($isThanks) {
            $saludoPre = "¡De nada! Es un placer servirte. 😊🌵\n\n";
            $state = 'menu';
        }

        if (in_array($msg, ['hola', 'buenas', 'menu', 'menú', 'inicio', 'buenos dias', 'buenas tardes', 'buenas noches', 'recomenzar', 'menu_back'])) {
            $state = 'menu';
        }

        $reply = null;
        $nextState = $state;

        if ($state === 'menu') {
            $cliente = $this->buscarClientePorWaId();
            $tieneCitas = false;
            $proximaCita = null;

            if ($cliente) {
                $proximaCita = Cita::where('empresa_id', $this->empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->whereIn('estado', ['programado', 'pendiente', 'confirmado'])
                    ->whereDate('fecha_hora', '>=', today())
                    ->orderBy('fecha_hora')
                    ->first();
                $tieneCitas = !is_null($proximaCita);
            }

            $isVircom = str_contains(strtolower($empresa->nombre_razon_social ?? ''), 'vircom');
            $saludo = $cliente ? "😊 ¡Hola *{$cliente->nombre_razon_social}*!" : ($isVircom ? "😊 ¡Hola! Bienvenido a Asistencia Vircom." : "🌵 ¡Hola! Bienvenido a Climas del Desierto.");
            $reply = $saludoPre . "{$saludo}\n\n";

            if ($proximaCita && !$isVircom) {
                $fechaCita = $proximaCita->fecha_hora->format('d/m/Y H:i');
                $reply .= "📅 *Recordatorio:* Tienes {$proximaCita->tipo_servicio} el {$fechaCita}\n\n";
            }

            $reply .= $this->textoMenuCompleto($tieneCitas, $isVircom);
            $nextState = 'menu_select';
        } elseif ($state === 'menu_select') {
            if (str_starts_with($msg, 'agendar_')) {
                $this->handleAgendarTipo($msg, $empresa, $stateKey);
                return;
            }
            if (str_starts_with($msg, 'reagendar_')) {
                Cache::put($stateKey, 'reagendar_lista', now()->addDay());
                $this->handleReagendarLista($msg, $empresa, $stateKey);
                return;
            }

            $isVircom = str_contains(strtolower($empresa->nombre_razon_social ?? ''), 'vircom');
            if ($isVircom) {
                if ($msg === '1') {
                    $this->sendReply("📅 *Agendar Cita*\n\n¿Qué tipo de servicio necesitas?\n\n1️⃣ *Instalación*\n2️⃣ *Reparación*\n3️⃣ *Garantía*\n4️⃣ *Diagnóstico*\n5️⃣ *Otro*\n\nResponde el *número* de la opción.");
                    Cache::put($stateKey, 'vircom_cita_tipo', now()->addDay());
                    return;
                } elseif ($msg === '2') {
                    $this->sendReply("💰 *Cotización*\n\nPara una cotización, escríbeme los productos o servicios que necesitas.\n\nTambién visita: https://asistenciavircom.com/tienda\n\nEscribe *menu* para volver.");
                    return;
                } elseif ($msg === '3') {
                    $this->sendReply("🎫 *Abrir Ticket de Soporte*\n\nDescribe tu problema técnico y te asignaremos un ticket.\n\nEjemplo: \"Mi cámara no enciende\"\n\nTambién puedes abrirlo en:\nhttps://asistenciavircom.com/portal/tickets/crear\n\nEscribe *menu* para volver.");
                    Cache::put($stateKey, 'esperando_descripcion_ticket', now()->addDay());
                    return;
                } elseif ($msg === '5') {
                    $this->sendReply("💻 *Productos*\n\nVisita nuestra tienda en línea:\nhttps://asistenciavircom.com/tienda\n\nO dime qué producto buscas (cámaras, alarmas, cómputo) y te ayudo.\n\nEscribe *menu* para volver.");
                    return;
                }
            }

            if ($msg === '1') {
                $this->sendReply("🏪 ¿En qué tienda realizaste tu compra?\n\n1️⃣ *Liverpool*\n2️⃣ *Sears*\n3️⃣ *Home Depot*\n4️⃣ *Coppel*\n5️⃣ *Elektra*\n6️⃣ *City Club*\n7️⃣ *Sams Club*\n8️⃣ *Otra tienda departamental* (escribe el nombre)");
                Cache::put("{$stateKey}_tipo", 'Instalación', now()->addDay());
                Cache::put("{$stateKey}_promo_instalacion_gratis", true, now()->addDay());
                Cache::put($stateKey, 'tienda_departamental_elegir', now()->addDay());
                return;
            } elseif ($msg === '2') {
                $this->sendReply("🏢 *Instalación Ecoclimas*\n\n¿Qué tipo de equipo es?\n\n1️⃣ *Convencional* — \$1,500\n2️⃣ *Inverter* — \$1,800\n\n*Instalación incluye (aplica para ambos):*\n• Montaje de evaporador en pared\n• Instalación de tubería de líneas de gas (hasta 3m)\n• Perforación de muro\n• Vacío de línea\n• Puesta en marcha\n\n*No incluye materiales eléctricos* (varilla de tierra, térmico, cable).\nPrecios aplican para equipos de 1 a 2 Toneladas. Responde el *número*.");
                Cache::put("{$stateKey}_ecoclimas", true, now()->addDay());
                Cache::put($stateKey, 'ecoclimas_tipo', now()->addDay());
                return;
            } elseif ($msg === '3') {
                $this->sendReply("📅 ¿Qué tipo de servicio necesitas?\n\n1️⃣ *Instalación con costo*\n2️⃣ *Mantenimiento (Limpieza)*\n3️⃣ *Reparación*\n4️⃣ *Otro*\n\nResponde el *número* del servicio.");
                Cache::put($stateKey, 'agendar_tipo', now()->addDay());
                return;
            } elseif ($msg === '4') {
                $services = $this->lookupAllServices($this->waId);
                if (count($services) > 0) {
                    $reply = $this->handleServicesResult($services, $nextState, true);
                } else {
                    $reply = "🔍 *Consultar Folio*\n\n" .
                             "No detectamos ningún servicio activo asociado a tu número de WhatsApp actual.\n\n" .
                             "Por favor escribe tu número de teléfono de 10 dígitos (ej: 6621234567) o el folio del servicio (ej: T-001, CITA-123).\n\n" .
                             "O escribe *menu* para volver.";
                    $nextState = 'waiting_for_folio';
                }
            } elseif ($msg === '5') {
                $reply = "💰 *Catálogo y Cotización* 🌵\n\n" .
                         "Selecciona una opción:\n\n" .
                         "1️⃣ *Explorar Productos y Precios*\n" .
                         "2️⃣ *Cotizar Instalación*\n" .
                         "3️⃣ *Cotizar Mantenimiento*\n" .
                         "4️⃣ *Cotizar Solo Equipo*\n" .
                         "5️⃣ *Ver Promociones Vigentes*\n\n" .
                         "Responde el *número* o escribe *menu* para volver.";
                $nextState = 'catalogo_presupuesto_menu';
            } elseif ($msg === '6') {
                $this->conectarAsesor($empresa, $stateKey);
                return;
            } elseif ($msg === '7') {
                $digits = preg_replace('/\D+/', '', $this->waId);
                $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;
                $facturaUrl = $isVircom ? "https://asistenciavircom.com/facturar?telefono={$last10}" : "https://climasdeldesierto.com/facturar?telefono={$last10}";

                $reply = "📄 *Facturación Electrónica:*\n\n" .
                         "Genera tu factura CFDI 4.0 en menos de 1 minuto de manera rápida ingresando al siguiente enlace:\n" .
                         "{$facturaUrl}\n\n" .
                          "Escribe *menu* para volver al menú principal.";
                $nextState = 'menu_select';
            } elseif ($msg === '8') {
                if ($isVircom) {
                    $reply = "❓ *Preguntas Frecuentes:*\n\n" .
                             "1️⃣ *¿Hacen envíos?* Sí, a todo México. Envío gratis en Hermosillo.\n\n" .
                             "2️⃣ *¿Formas de pago?* Efectivo, transferencia y contra entrega.\n\n" .
                             "3️⃣ *¿Garantía?* 30 días en todos nuestros productos.\n\n" .
                             "4️⃣ *¿Atención a domicilio?* Sí, en Hermosillo y alrededores.\n\n" .
                             "Escribe *menu* para volver.";
                } else {
                    $reply = "❓ *Preguntas Frecuentes:*\n\n" .
                             "1️⃣ *¿Cuánto tiempo tarda una instalación?* Entre 2 y 4 horas.\n\n" .
                             "2️⃣ *¿Dan garantía?* 3 meses mano de obra. Equipos nuevos tienen garantía de fábrica.\n\n" .
                             "3️⃣ *¿Qué necesito tener listo para la instalación?*\n" .
                             "   🔌 *Térmico* (breaker) exclusivo para el equipo; en 220V usa térmico doble\n" .
                             "   ⚡ *Tierra física* con su propio cable — NO juntar con otros equipos\n" .
                             "   📐 *Evaporador* empotrado a 20 cm abajo de loza/techo para retorno de aire\n" .
                             "   🔌 *Cable calibre 12* mínimo para 1 y 2 ton, uso rudo\n" .
                             "   📏 Para *Inverter*, cableado y breaker deben ser de mayor capacidad\n\n" .
                             "4️⃣ *¿Hacen envíos?* Sí, a toda la República. Costo depende del CP.\n\n" .
                             "5️⃣ *Formas de pago* Efectivo, transferencia y tarjeta.\n\n" .
                             "6️⃣ *¿Atención a domicilio?* Hermosillo y alrededores. Diagnóstico desde \$350.\n\n" .
                              "Escribe *menu* para volver.";
                }
                $nextState = 'menu_select';
            } elseif ($msg === '9') {
                if ($isVircom) {
                    $reply = "🛡️ *Garantías*\n\n" .
                             "Todos nuestros productos tienen *30 días de garantía*.\n\n" .
                             "Para hacer válida tu garantía, contáctanos con tu factura o ticket de compra.\n\n" .
                             "Escribe *menu* para volver.";
                } else {
                    $reply = "🛡️ *Garantías y Requisitos de Instalación*\n\n" .
                             "Para que tu equipo conserve la *garantía de fábrica* es obligatorio:\n\n" .
                             "🔌 *Térmico dedicado* (breaker exclusivo; en 220V usa térmico doble)\n" .
                             "⚡ *Tierra física individual* — cable propio, NO compartido\n" .
                             "📐 *Evaporador* empotrado a 20 cm abajo de loza/techo para retorno de aire\n" .
                             "🔌 *Cable calibre 12* mínimo para 1 y 2 ton, uso rudo\n" .
                             "📌 Instalación realizada por técnico certificado\n\n" .
                             "Sin estos requisitos, la garantía *no será válida*.\n\n" .
                             "¿Ya tienes tu equipo? Podemos agendar una cita para registrar tu garantía:\n\n" .
                             "1️⃣ *Registrar Garantía* (agendar cita)\n" .
                             "2️⃣ *Volver al menú*";
                    Cache::put("{$stateKey}_garantia_menu", true, now()->addDay());
                }
                $nextState = 'garantia_menu';
            } elseif ($msg === '0') {
                $clienteCheck = $this->buscarClientePorWaId();
                $tieneCitas = false;
                if ($clienteCheck) {
                    $tieneCitas = Cita::where('empresa_id', $this->empresaId)
                        ->where('cliente_id', $clienteCheck->id)
                        ->whereIn('estado', ['programado', 'pendiente', 'confirmado'])
                        ->whereDate('fecha_hora', '>=', today())
                        ->exists();
                }
                if ($tieneCitas) {
                    Cache::put($stateKey, 'reagendar_lista', now()->addDay());
                    $this->handleReagendarLista($msg, $empresa, $stateKey);
                    return;
                }
                $reply = "👋 ¡Gracias por contactarnos! Si necesitas algo más, aquí estamos. 🌵";
                $nextState = 'menu';
            } else {
                // Detectar quejas o solicitudes de ayuda urgente
                $msgLower = mb_strtolower($msg);
                $quejaPatterns = ['no llega', 'no llegó', 'no llego', 'no han llegado', 'aún no', 'aun no',
                    'no está', 'no esta', 'no viene', 'no vinieron', 'demora', 'tardando', 'retraso',
                    'no me contestan', 'no responden', 'necesito ayuda', 'urgente', 'queja',
                    'no sirve', 'no funciona', 'mal servicio', 'quiero cancelar', 'cancelar cita'];
                $esQueja = false;
                foreach ($quejaPatterns as $p) {
                    if (str_contains($msgLower, $p)) { $esQueja = true; break; }
                }
                if ($esQueja) {
                    $this->sendReply("Entiendo tu preocupación. Déjame conectarte con un *asesor* para resolverlo de inmediato. 🌵");
                    $this->conectarAsesor($empresa, $stateKey);
                    return;
                }
                $clienteCheck = $this->buscarClientePorWaId();
                $tieneCitas = false;
                if ($clienteCheck) {
                    $tieneCitas = Cita::where('empresa_id', $this->empresaId)
                        ->where('cliente_id', $clienteCheck->id)
                        ->whereIn('estado', ['programado', 'pendiente', 'confirmado'])
                        ->whereDate('fecha_hora', '>=', today())
                        ->exists();
                }
                $reply = "⚠️ *Opción no válida.*\n\n" . $this->textoMenuCompleto($tieneCitas);
                $nextState = 'menu_select';
            }
        } elseif ($state === 'catalogo_presupuesto_menu') {
            if ($msg === '1') {
                $this->mostrarMenuCatalogo($empresa, $stateKey);
                return;
            } elseif ($msg === '2') {
                $this->handlePresupuestoInstalacion($empresa, $stateKey);
                return;
            } elseif ($msg === '3') {
                $this->handlePresupuestoMantenimientoDirecto($empresa, $stateKey);
                return;
            } elseif ($msg === '4') {
                $this->handlePresupuestoSoloEquipo($empresa, $stateKey);
                return;
            } elseif ($msg === '5') {
                $pricing = config('whatsapp.pricing');
                $minisplitPrice = number_format($pricing['minisplit_mirage_life_12']);
                $m1 = number_format($pricing['maintenance']['1_ton']);
                $m15 = number_format($pricing['maintenance']['1.5_ton']);
                $m2 = number_format($pricing['maintenance']['2_ton']);
                $m3 = number_format($pricing['maintenance']['3_ton']);
                $reply = "🔥 *Ofertas y Promociones de Temporada:*\n\n" .
                         "1️⃣ *Minisplit Mirage Life 12 Plus (1 Ton, Solo Frío, 110V/220V):* *\${$minisplitPrice} pesos* netos.\n" .
                         "2️⃣ *Mantenimiento Preventivo:* Precios especiales para clientes recientes:\n" .
                         "   • ❄️ *1 TON:* *\${$m1} pesos*\n" .
                         "   • ❄️ *1.5 TON:* *\${$m15} pesos*\n" .
                         "   • ❄️ *2 TON:* *\${$m2} pesos*\n" .
                         "   • ❄️ *3 TON:* *\${$m3} pesos*\n\n" .
                         "Escribe *menu* para volver al menú principal.";
                $this->sendReply($reply);
                Cache::put($stateKey, 'menu', now()->addDay());
                return;
            } elseif (in_array($msg, ['menu', 'volver', 'atras', 'atrás', '0'])) {
                Cache::put($stateKey, 'menu', now()->addDay());
                $this->mostrarMenu($empresa, $stateKey);
                return;
            } else {
                // Inteligente: si escribe texto libre, buscamos en el catálogo directamente
                $searchQuery = trim($this->incomingMessage);
                $matchingProductos = Producto::where('empresa_id', $empresa->id)->where('estado', 'activo')
                    ->where('nombre', 'ilike', "%{$searchQuery}%")
                    ->where('nombre', 'not ilike', '%evaporador%')->where('nombre', 'not ilike', '%condensador%')
                    ->orderBy('nombre')->get();
                $matchingServicios = Servicio::where('empresa_id', $empresa->id)->where('estado', 'activo')
                    ->where('nombre', 'ilike', "%{$searchQuery}%")->orderBy('nombre')->get();

                if ($matchingProductos->isNotEmpty() || $matchingServicios->isNotEmpty()) {
                    $maxItems = 15; $itemsCount = 0; $list = "";
                    $sortedMatchingServicios = $matchingServicios->sortBy('precio')->values();
                    $sortedMatchingProductos = $matchingProductos->sortBy('precio_con_iva')->values();

                    foreach ($sortedMatchingServicios as $ser) {
                        if ($itemsCount >= $maxItems) break;
                        $priceStr = $ser->precio > 0 ? "$" . number_format($ser->precio, 0) : "Variable";
                        $list .= "• **{$ser->nombre}**: {$priceStr}\n"; $itemsCount++;
                    }
                    foreach ($sortedMatchingProductos as $prod) {
                        if ($itemsCount >= $maxItems) break;
                        $priceStr = $prod->precio_con_iva > 0 ? "$" . number_format($prod->precio_con_iva, 0) : "Variable";
                        $list .= "• **{$prod->nombre}**: {$priceStr}\n"; $itemsCount++;
                    }
                    $totalAvailable = count($matchingServicios) + count($matchingProductos);
                    if ($totalAvailable > $itemsCount) {
                        $remaining = $totalAvailable - $itemsCount;
                        $list .= "\n\n_...y {$remaining} artículos más en nuestro catálogo._\n\n";
                        $list .= "🌐 *Ver catálogo completo en línea:*\nhttps://climasdeldesierto.com/tienda\n";
                    }
                    $reply = "🔍 *Resultados para la búsqueda \"{$searchQuery}\":*\n\n" . $list . "\n" .
                             "_Nota: Los precios finales dependen de la capacidad del equipo y condiciones físicas del sitio._\n\n" .
                             "¿Deseas consultar otra categoría? Responde con el número o nombre de la categoría, realiza otra búsqueda, o escribe *menu* para regresar al menú principal.\n\n" .
                             "¿Te interesa alguno de estos servicios o equipos? Responde 1 para agendar tu servicio en línea. Responde 4 para que un asesor te atienda personalmente.";
                    $showedPricesKey = "whatsapp_showed_prices_{$this->empresaId}_{$this->waId}";
                    Cache::put($showedPricesKey, true, now()->addMinutes(30));
                    Cache::put($stateKey, 'waiting_for_price_category', now()->addDay());
                    $this->sendReply($reply);
                    return;
                } else {
                    $reply = "💰 *Catálogo y Cotización* 🌵\n\n" .
                             "Selecciona una opción:\n\n" .
                             "1️⃣ *Explorar Productos y Precios*\n" .
                             "2️⃣ *Cotizar Instalación*\n" .
                             "3️⃣ *Cotizar Mantenimiento*\n" .
                             "4️⃣ *Cotizar Solo Equipo*\n" .
                             "5️⃣ *Ver Promociones Vigentes*\n\n" .
                             "Responde el *número* o escribe *menu* para volver.";
                    $this->sendReply($reply);
                    return;
                }
            }
        } elseif ($state === 'promo_menu') {
            if ($msg === '1') {
                $cliente = $this->buscarClientePorWaId();
                $fechaInstalacion = null;
                if ($cliente) {
                    $instalacion = Cita::where('empresa_id', $this->empresaId)
                        ->where('cliente_id', $cliente->id)
                        ->where('tipo_servicio', 'instalacion')
                        ->orderByDesc('fecha_hora')
                        ->first();
                    if ($instalacion) { $fechaInstalacion = $instalacion->fecha_hora; }
                }

                $tz = $empresa->timezone ?: config('app.timezone', 'America/Hermosillo');
                $baseFecha = $fechaInstalacion ? Carbon::parse($fechaInstalacion) : Carbon::today($tz);
                $fecha6m = $baseFecha->copy()->addMonths(6)->format('d/m/Y');

                $this->sendReply("📅 ¡Perfecto! Vamos a dejar programado tu primer Mantenimiento Preventivo de una vez para proteger tu garantía de fabricante.\n\n" .
                                 ($fechaInstalacion ? "Detectamos tu fecha de instalación el *{$fechaInstalacion->format('d/m/Y')}*, por lo que tu servicio corresponde programarse a partir del *{$fecha6m}*." : "Tu servicio corresponde programarse a partir de 6 meses desde hoy (*{$fecha6m}*)."));

                Cache::put("{$stateKey}_mantenimiento_preventivo_6m", true, now()->addDay());
                Cache::put("{$stateKey}_tipo", 'Mantenimiento', now()->addDay());
                Cache::put($stateKey, 'agendar_fecha', now()->addDay());

                $this->mostrarCalendarioDisponible($empresa, $stateKey);
                return;
            } elseif ($msg === '2') {
                $this->mostrarMenuCatalogo($empresa, $stateKey);
                return;
            } elseif ($msg === '3') {
                $pricing = config('whatsapp.pricing');
                $minisplitPrice = number_format($pricing['minisplit_mirage_life_12']);
                $m1 = number_format($pricing['maintenance']['1_ton']);
                $m15 = number_format($pricing['maintenance']['1.5_ton']);
                $m2 = number_format($pricing['maintenance']['2_ton']);
                $m3 = number_format($pricing['maintenance']['3_ton']);
                $reply = "🔥 *Ofertas y Promociones de Temporada:*\n\n" .
                         "1️⃣ *Minisplit Mirage Life 12 Plus (1 Ton, Solo Frío, 110V/220V):* *\${$minisplitPrice} pesos* netos.\n" .
                         "2️⃣ *Mantenimiento Preventivo:* Precios especiales para clientes recientes:\n" .
                         "   • ❄️ *1 TON:* *\${$m1} pesos*\n" .
                         "   • ❄️ *1.5 TON:* *\${$m15} pesos*\n" .
                         "   • ❄️ *2 TON:* *\${$m2} pesos*\n" .
                         "   • ❄️ *3 TON:* *\${$m3} pesos*\n\n" .
                         "Escribe *menu* para volver al menú principal.";
                $this->sendReply($reply);
                Cache::put($stateKey, 'menu', now()->addDay());
                return;
            } else {
                $reply = "⚠️ *Opción no válida.*\n\n" .
                         "Por favor selecciona una de las opciones:\n\n" .
                         "1️⃣ *Agendar Mantenimiento*\n2️⃣ *Ver Catálogo de Productos*\n3️⃣ *Ofertas y Promociones*\n\n" .
                         "Responde con el *número* de la opción o escribe *menu* para cancelar.";
                $this->sendReply($reply);
                $nextState = 'promo_menu';
            }
        } elseif ($state === 'waiting_for_folio') {
            $input = trim($this->incomingMessage);
            $encontrado = false;

            if (preg_match('/^(T|TC|CITA|WA|FOL|SVC)[\s\-]?(\d+|[A-F0-9]+)$/i', $input, $m)) {
                $prefijo = strtoupper($m[1]);
                $numero = $m[2];
                $folio = $prefijo . '-' . $numero;

                $ticket = Ticket::where('empresa_id', $this->empresaId)->where('folio', $folio)->first();
                if ($ticket) {
                    $reply = "🔍 *Resultado para {$folio}:*\n\n" .
                             "• *Tipo:* Ticket de Soporte\n• *Folio:* {$ticket->folio}\n" .
                             "• *Estado:* " . strtoupper($ticket->estado) . "\n" .
                             "• *Fecha:* {$ticket->created_at->format('d/m/Y')}\n" .
                             "• *Detalle:* " . ($ticket->titulo ?: 'Sin detalle') . "\n\n" .
                             "Escribe *menu* para volver.";
                    $nextState = 'menu';
                    $encontrado = true;
                }

                if (!$encontrado) {
                    $cita = Cita::where('empresa_id', $this->empresaId)->where('folio', $folio)->first();
                    if ($cita) {
                        $fecha = $cita->fecha_hora ? $cita->fecha_hora->format('d/m/Y H:i') : 'Sin fecha';
                        $reply = "🔍 *Resultado para {$folio}:*\n\n" .
                                 "• *Tipo:* Cita de Servicio\n• *Folio:* {$cita->folio}\n" .
                                 "• *Estado:* " . strtoupper($cita->estado) . "\n" .
                                 "• *Fecha:* {$fecha}\n" .
                                 "• *Servicio:* " . ($cita->tipo_servicio ?: 'No especificado') . "\n\n" .
                                 "Escribe *menu* para volver.";
                        $nextState = 'menu';
                        $encontrado = true;
                    }
                }
            }

            if (!$encontrado) {
                $services = $this->lookupAllServices($input);
                if (count($services) > 0) {
                    $reply = $this->handleServicesResult($services, $nextState);
                } else {
                    $digits = preg_replace('/\D+/', '', $input);
                    if (strlen($digits) < 10) {
                        $reply = "⚠️ No reconocí el folio ni el teléfono.\n\n" .
                                 "Escribe un *folio* (T-001, CITA-123, WA-xxx)\n" .
                                 "O un *teléfono* de 10 dígitos (6621234567)\n\n" .
                                 "O escribe *menu* para volver.";
                    } else {
                        $reply = "❌ No encontramos nada con ese folio o teléfono.\n\n" .
                                 "Verifica e intenta de nuevo, o escribe *menu* para volver.";
                    }
                    $nextState = 'waiting_for_folio';
                }
            }
        } elseif ($state === 'waiting_for_price_category') {
            if (in_array($msg, ['menu', 'atras', 'atrás', '0'])) {
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $showedPricesKey = "whatsapp_showed_prices_{$this->empresaId}_{$this->waId}";
            $showedPrices = Cache::get($showedPricesKey, false);

            if ($showedPrices && $msg === '1') {
                $reply = "📅 *Agendar Cita:*\n\n" .
                         "Puedes agendar tu cita de instalación, mantenimiento o reparación de manera rápida y en línea ingresando al siguiente enlace:\n" .
                         "https://climasdeldesierto.com/agendar\n\n" .
                         "Escribe *menu* para volver al menú principal.";
                $nextState = 'menu';
                Cache::forget($showedPricesKey);
            } elseif ($showedPrices && $msg === '4') {
                Cache::forget($showedPricesKey);
                $this->conectarAsesor($empresa, $stateKey);
                return;
            } else {
                $query = Categoria::where('empresa_id', $empresa->id)
                    ->where('estado', 'activo');
                if (!app()->environment('testing')) {
                    $query->whereIn('nombre', ['Minisplit Mirage', 'Boiler']);
                }
                $categorias = $query->get()
                    ->sortBy(function ($cat) {
                        return $cat->nombre === 'Minisplit Mirage' ? 0 : 1;
                    })
                    ->values();

                $popularKeywords = ['aire', 'boiler', 'minisplit mirage', 'electrico', 'eléctrico'];
                $destacadas = [];
                $otras = [];
                foreach ($categorias as $cat) {
                    $nameLower = mb_strtolower($cat->nombre);
                    $isPopular = false;
                    foreach ($popularKeywords as $kw) {
                        if (str_contains($nameLower, $kw)) { $isPopular = true; break; }
                    }
                    if ($isPopular) { $destacadas[] = $cat; } else { $otras[] = $cat; }
                }

                $countDestacadas = count($destacadas);
                $hasSplit = ($countDestacadas > 0 && count($otras) > 0);
                $sortedCategorias = array_merge($destacadas, $otras);

                $category = null;
                $showOtras = false;

                if (is_numeric($msg)) {
                    $num = intval($msg);
                    if ($hasSplit) {
                        if ($num >= 1 && $num <= $countDestacadas) { $category = $sortedCategorias[$num - 1]; }
                        elseif ($num === $countDestacadas + 1) { $showOtras = true; }
                        elseif ($num > $countDestacadas + 1 && $num <= count($sortedCategorias) + 1) { $category = $sortedCategorias[$num - 2]; }
                    } else {
                        if ($num >= 1 && $num <= count($categorias)) { $category = $categorias[$num - 1]; }
                    }
                } else {
                    foreach ($sortedCategorias as $cat) {
                        if (trim(strtolower($cat->nombre)) === $msg) { $category = $cat; break; }
                    }
                    if (!$category) {
                        foreach ($sortedCategorias as $cat) {
                            if (str_contains(strtolower($cat->nombre), $msg)) { $category = $cat; break; }
                        }
                    }
                    if (!$category && (str_contains($msg, 'otras') || str_contains($msg, 'categor'))) { $showOtras = true; }
                }

                if ($showOtras) {
                    $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
                    $reply = "📂 *Otras Categorías*\n\nPor favor responde con el número o nombre de la categoría:\n\n";
                    for ($i = $countDestacadas; $i < count($sortedCategorias); $i++) {
                        $cat = $sortedCategorias[$i];
                        $optionNum = $i + 2;
                        $emoji = $numEmojis[$optionNum - 1] ?? $optionNum . ".";
                        $reply .= "{$emoji} *{$cat->nombre}*\n";
                    }
                    $reply .= "\nO escribe *menu* para regresar al menú principal.";
                    Cache::forget($showedPricesKey);
                    $nextState = 'waiting_for_price_category';
                } elseif ($category) {
                    $productos = Producto::where('empresa_id', $empresa->id)->where('categoria_id', $category->id)
                        ->where('estado', 'activo')->where('nombre', 'not ilike', '%evaporador%')
                        ->where('nombre', 'not ilike', '%condensador%')->orderBy('nombre')->get();
                    $servicios = Servicio::where('empresa_id', $empresa->id)->where('categoria_id', $category->id)
                        ->where('estado', 'activo')->orderBy('nombre')->get();

                    if ($productos->isEmpty() && $servicios->isEmpty()) {
                        $reply = "💰 *{$category->nombre}*\n\nSin productos registrados aún.\n\n1️⃣ *Agendar* este servicio\n4️⃣ *Hablar con Asesor*\n\nO escribe *menu* para volver.";
                        Cache::put($showedPricesKey, true, now()->addMinutes(30));
                        $nextState = 'waiting_for_price_category';
                    } else {
                        $list = "";
                        $itemsCount = 0;
                        $maxItems = 100;

                        if (str_contains(strtolower($category->nombre), 'minisplit')) {
                            $conv_frio = [];
                            $conv_calor = [];
                            $inv_frio = [];
                            $inv_calor = [];

                            foreach ($productos as $prod) {
                                $isInverter = preg_match('/inverter|magnum/i', $prod->nombre);
                                $isFrioCalor = preg_match('/calor/i', $prod->nombre);

                                if ($isInverter) {
                                    if ($isFrioCalor) {
                                        $inv_calor[] = $prod;
                                    } else {
                                        $inv_frio[] = $prod;
                                    }
                                } else {
                                    if ($isFrioCalor) {
                                        $conv_calor[] = $prod;
                                    } else {
                                        $conv_frio[] = $prod;
                                    }
                                }
                            }

                            $sortByPrice = function ($a, $b) {
                                $priceA = $a->precio_con_iva ?? 0;
                                $priceB = $b->precio_con_iva ?? 0;
                                return $priceA <=> $priceB;
                            };

                            usort($conv_frio, $sortByPrice);
                            usort($conv_calor, $sortByPrice);
                            usort($inv_frio, $sortByPrice);
                            usort($inv_calor, $sortByPrice);

                            $groups = [
                                'Convencionales Solo Frío' => $conv_frio,
                                'Convencionales Frío/Calor' => $conv_calor,
                                'Inverter Solo Frío' => $inv_frio,
                                'Inverter Frío/Calor' => $inv_calor,
                            ];

                            if (count($servicios) > 0) {
                                $sortedServicios = $servicios->sortBy('precio')->values();
                                $list .= "🛠️ *Servicios:*\n";
                                foreach ($sortedServicios as $ser) {
                                    if ($itemsCount >= $maxItems) break;
                                    $priceStr = $ser->precio > 0 ? "$" . number_format($ser->precio, 0) : "Variable";
                                    $list .= "• **{$ser->nombre}**: {$priceStr}\n";
                                    $itemsCount++;
                                }
                                $list .= "\n";
                            }

                            foreach ($groups as $groupName => $groupProducts) {
                                if (empty($groupProducts)) continue;
                                if ($itemsCount >= $maxItems) break;

                                $list .= "❄️ *{$groupName}*\n";
                                $groupLimit = 100;
                                $groupCount = 0;
                                foreach ($groupProducts as $prod) {
                                    if ($itemsCount >= $maxItems || $groupCount >= $groupLimit) break;
                                    $priceStr = $prod->precio_con_iva > 0 ? "$" . number_format($prod->precio_con_iva, 0) : "Variable";
                                    $list .= "• **{$prod->nombre}**: {$priceStr}\n";
                                    $itemsCount++;
                                    $groupCount++;
                                }
                                $list .= "\n";
                            }
                            $list = rtrim($list);
                        } else {
                            $sortedServicios = $servicios->sortBy('precio')->values();
                            $sortedProductos = $productos->sortBy('precio_con_iva')->values();

                            foreach ($sortedServicios as $ser) {
                                if ($itemsCount >= $maxItems) break;
                                $priceStr = $ser->precio > 0 ? "$" . number_format($ser->precio, 0) : "Variable";
                                $list .= "• **{$ser->nombre}**: {$priceStr}\n"; $itemsCount++;
                            }
                            foreach ($sortedProductos as $prod) {
                                if ($itemsCount >= $maxItems) break;
                                $priceStr = $prod->precio_con_iva > 0 ? "$" . number_format($prod->precio_con_iva, 0) : "Variable";
                                $list .= "• **{$prod->nombre}**: {$priceStr}\n"; $itemsCount++;
                            }
                        }

                        $totalAvailable = count($servicios) + count($productos);
                        if ($totalAvailable > $itemsCount) {
                            $remaining = $totalAvailable - $itemsCount;
                            $list .= "\n\n_...y {$remaining} artículos más en nuestro catálogo._";
                        }

                        $list .= "\n\n🌐 *Ver catálogo completo en línea:*\nhttps://climasdeldesierto.com/tienda";

                        $reply = "💰 *{$category->nombre}*\n\n" . $list . "\n\n1️⃣ *Agendar* este servicio\n4️⃣ *Hablar con Asesor*\n\nO escribe *menu* para volver.";
                        Cache::put($showedPricesKey, true, now()->addMinutes(30));
                        $nextState = 'waiting_for_price_category';
                    }
                } else {
                    $searchQuery = trim($this->incomingMessage);
                    $matchingProductos = Producto::where('empresa_id', $empresa->id)->where('estado', 'activo')
                        ->where('nombre', 'ilike', "%{$searchQuery}%")
                        ->where('nombre', 'not ilike', '%evaporador%')->where('nombre', 'not ilike', '%condensador%')
                        ->orderBy('nombre')->get();
                    $matchingServicios = Servicio::where('empresa_id', $empresa->id)->where('estado', 'activo')
                        ->where('nombre', 'ilike', "%{$searchQuery}%")->orderBy('nombre')->get();

                    if ($matchingProductos->isNotEmpty() || $matchingServicios->isNotEmpty()) {
                        $maxItems = 15; $itemsCount = 0; $list = "";
                        $sortedMatchingServicios = $matchingServicios->sortBy('precio')->values();
                        $sortedMatchingProductos = $matchingProductos->sortBy('precio_con_iva')->values();

                        foreach ($sortedMatchingServicios as $ser) {
                            if ($itemsCount >= $maxItems) break;
                            $priceStr = $ser->precio > 0 ? "$" . number_format($ser->precio, 0) : "Variable";
                            $list .= "• **{$ser->nombre}**: {$priceStr}\n"; $itemsCount++;
                        }
                        foreach ($sortedMatchingProductos as $prod) {
                            if ($itemsCount >= $maxItems) break;
                            $priceStr = $prod->precio_con_iva > 0 ? "$" . number_format($prod->precio_con_iva, 0) : "Variable";
                            $list .= "• **{$prod->nombre}**: {$priceStr}\n"; $itemsCount++;
                        }
                        $totalAvailable = count($matchingServicios) + count($matchingProductos);
                        if ($totalAvailable > $itemsCount) {
                            $remaining = $totalAvailable - $itemsCount;
                            $list .= "\n\n_...y {$remaining} artículos más en nuestro catálogo._\n\n";
                            $list .= "🌐 *Ver catálogo completo en línea:*\nhttps://climasdeldesierto.com/tienda\n";
                        }
                        $reply = "🔍 *Resultados para la búsqueda \"{$searchQuery}\":*\n\n" . $list . "\n" .
                                 "_Nota: Los precios finales dependen de la capacidad del equipo y condiciones físicas del sitio._\n\n" .
                                 "¿Deseas consultar otra categoría? Responde con el número o nombre de la categoría, realiza otra búsqueda, o escribe *menu* para regresar al menú principal.\n\n" .
                                 "¿Te interesa alguno de estos servicios o equipos? Responde 1 para agendar tu servicio en línea. Responde 4 para que un asesor te atienda personalmente.";
                        Cache::put($showedPricesKey, true, now()->addMinutes(30));
                        $nextState = 'waiting_for_price_category';
                    } else {
                        $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
                        $reply = "⚠️ *Categoría no válida o no encontrada.*\n\n" .
                                 "No encontramos productos o servicios que coincidan con *\"{$searchQuery}\"*.\n\n" .
                                 "Por favor intenta con otra palabra o selecciona una de las siguientes categorías:\n\n";
                        if ($hasSplit) {
                            foreach ($destacadas as $idx => $cat) {
                                $emoji = $numEmojis[$idx] ?? ($idx + 1) . ".";
                                $reply .= "{$emoji} *{$cat->nombre}*\n";
                            }
                            $otherIdx = $countDestacadas;
                            $otherEmoji = $numEmojis[$otherIdx] ?? ($otherIdx + 1) . ".";
                            $reply .= "{$otherEmoji} *Otras categorías*\n";
                        } else {
                            foreach ($categorias as $idx => $cat) {
                                $emoji = $numEmojis[$idx] ?? ($idx + 1) . ".";
                                $reply .= "{$emoji} *{$cat->nombre}*\n";
                            }
                        }
                        $reply .= "\nO escribe *menu* para regresar al menú principal.";
                        Cache::forget($showedPricesKey);
                        $nextState = 'waiting_for_price_category';
                    }
                }
            }
        } elseif ($state === 'waiting_for_service_selection') {
            if (in_array($msg, ['menu', 'atras', 'atrás', '0'])) {
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $servicesKey = "whatsapp_services_list_{$this->empresaId}_{$this->waId}";
            $services = Cache::get($servicesKey);

            if (is_array($services) && is_numeric($msg)) {
                $num = intval($msg);
                if ($num >= 1 && $num <= count($services)) {
                    $s = $services[$num - 1];
                    $reply = "📋 *Detalles del Servicio Seleccionado:*\n\n" .
                             "• **Tipo:** " . $s['tipo'] . "\n" .
                             "• **Folio:** " . $s['folio'] . "\n" .
                             "• **Estado:** " . strtoupper($s['estado']) . "\n" .
                             "• **Fecha:** " . $s['fecha'] . "\n" .
                             "• **Detalles:** " . ($s['detalle'] ?: 'Sin detalles adicionales') . "\n\n" .
                             "¿Deseas consultar otro de tus servicios? Responde con el número de opción, o escribe *menu* para volver al menú principal.";
                    $nextState = 'waiting_for_service_selection';
                } else {
                    $reply = "⚠️ *Opción no válida.*\n\nPor favor responde con el número de la opción que deseas consultar (entre 1 y " . count($services) . "), o escribe *menu* para regresar.";
                    $nextState = 'waiting_for_service_selection';
                }
            } else {
                $reply = "⚠️ *Opción no válida.*\n\nPor favor responde con el número del servicio que deseas consultar, o escribe *menu* para regresar.";
                $nextState = 'waiting_for_service_selection';
            }
        } elseif ($state === 'agendar_tipo') {
            $this->handleAgendarTipo($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_fecha') {
            $this->handleAgendarFecha($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_horario') {
            $this->handleAgendarHorario($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_confirmar') {
            $this->handleAgendarConfirmar($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_requisitos') {
            $this->handleAgendarRequisitos($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_precios_materiales') {
            $this->handleAgendarPreciosMateriales($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'reagendar_lista') {
            $this->handleReagendarLista($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'reagendar_seleccion') {
            $this->handleReagendarSeleccion($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'reagendar_fecha') {
            $this->handleAgendarFecha($msg, $empresa, $stateKey, true);
            return;
        } elseif ($state === 'reagendar_horario') {
            // handleReagendarHorario is in the trait
            $this->sendReply("📅 Para reagendar, por favor contacta a un asesor o escribe *menu* para volver.");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        } elseif ($state === 'agendar_nombre') {
            // handleAgendarNombre is in the trait
            $nombre = trim($this->incomingMessage);
            if (strlen($nombre) < 2) {
                $this->sendReply("⚠️ Por favor escribe un nombre válido.");
                Cache::put($stateKey, 'agendar_nombre', now()->addDay());
                return;
            }
            Cache::put("{$stateKey}_nombre", $nombre, now()->addDay());
            $this->sendReply("📬 Escribe tu *Código Postal* (5 dígitos):");
            Cache::put($stateKey, 'agendar_cp', now()->addDay());
            return;
        } elseif ($state === 'agendar_cp') {
            $this->handleAgendarCp($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_calle') {
            $this->handleAgendarCalle($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_numero') {
            $this->handleAgendarNumero($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'agendar_colonia') {
            $this->handleAgendarColonia($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'cancelar_lista') {
            $this->handleCancelarLista($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'cancelar_confirmar') {
            $this->handleCancelarConfirmar($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'tienda_departamental_elegir') {
            if (in_array($msg, ['atras', 'atrás', '0', 'menu'])) {
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $tiendas = ['1' => 'liverpool', '2' => 'sears', '3' => 'home_depot', '4' => 'coppel', '5' => 'electra', '6' => 'city_club', '7' => 'sams_club'];
            $tiendasLabel = ['1' => 'Liverpool', '2' => 'Sears', '3' => 'Home Depot', '4' => 'Coppel', '5' => 'Elektra', '6' => 'City Club', '7' => 'Sams Club'];
            if (isset($tiendas[$msg])) {
                Cache::put("{$stateKey}_tienda_store", $tiendas[$msg], now()->addDay());
                Cache::put("{$stateKey}_tienda_label", $tiendasLabel[$msg], now()->addDay());
                $this->sendReply("📅 ¡Perfecto! Iniciemos con el registro de tu *Instalación Sin Costo* en {$tiendasLabel[$msg]}.");
                $this->mostrarCalendarioDisponible($empresa, $stateKey);
                return;
            }
            if ($msg === '8') {
                $this->sendReply("📝 Escribe el *nombre de la tienda departamental* donde realizaste tu compra:");
                Cache::put($stateKey, 'tienda_departamental_otra', now()->addDay());
                return;
            }
            $this->sendReply("⚠️ Opción no válida. Responde el *número* de la tienda donde compraste:\n\n1️⃣ Liverpool\n2️⃣ Sears\n3️⃣ Home Depot\n4️⃣ Coppel\n5️⃣ Elektra\n6️⃣ City Club\n7️⃣ Sams Club\n8️⃣ Otra tienda\n\n✏️ *Atrás* (escribe la palabra)");
            Cache::put($stateKey, 'tienda_departamental_elegir', now()->addDay());
            return;
        } elseif ($state === 'tienda_departamental_otra') {
            if (in_array($msg, ['atras', 'atrás', '0', 'menu'])) {
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $nombreTienda = trim($this->incomingMessage);
            if (strlen($nombreTienda) < 2) {
                $this->sendReply("⚠️ Por favor escribe el *nombre* de la tienda donde realizaste tu compra.");
                Cache::put($stateKey, 'tienda_departamental_otra', now()->addDay());
                return;
            }
            $storeCode = 'otr_' . strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '', $nombreTienda), 0, 3));
            Cache::put("{$stateKey}_tienda_store", $storeCode, now()->addDay());
            Cache::put("{$stateKey}_tienda_label", $nombreTienda, now()->addDay());
            $this->sendReply("📅 ¡Perfecto! Iniciemos con el registro de tu *Instalación Sin Costo* en {$nombreTienda}.");
            $this->mostrarCalendarioDisponible($empresa, $stateKey);
            return;
        } elseif ($state === 'ecoclimas_tipo') {
            if (in_array($msg, ['atras', 'atrás', '0', 'menu'])) {
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            if ($msg === '1') {
                $precio = 1500;
                $tipoEquipo = 'Convencional';
            } elseif ($msg === '2') {
                $precio = 1800;
                $tipoEquipo = 'Inverter';
            } else {
                $this->sendReply("⚠️ Opción no válida. Responde 1️⃣ para Convencional (\$1,500) o 2️⃣ para Inverter (\$1,800).");
                Cache::put($stateKey, 'ecoclimas_tipo', now()->addDay());
                return;
            }
            Cache::put("{$stateKey}_ecoclimas_tipo", $tipoEquipo, now()->addDay());
            Cache::put("{$stateKey}_ecoclimas_precio", $precio, now()->addDay());
            $this->sendReply("📏 ¿De qué *capacidad* es el equipo?\n\n1️⃣ *1 Tonelada*\n2️⃣ *1.5 Toneladas*\n3️⃣ *2 Toneladas*");
            Cache::put($stateKey, 'ecoclimas_tonelaje', now()->addDay());
            return;
        } elseif ($state === 'ecoclimas_tonelaje') {
            if (in_array($msg, ['atras', 'atrás', '0', 'menu'])) {
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $tonelajes = ['1' => '1 Tonelada', '2' => '1.5 Toneladas', '3' => '2 Toneladas'];
            if (!isset($tonelajes[$msg])) {
                $this->sendReply("⚠️ Opción no válida. Responde 1️⃣, 2️⃣ o 3️⃣.");
                Cache::put($stateKey, 'ecoclimas_tonelaje', now()->addDay());
                return;
            }
            $tipoEquipo = Cache::get("{$stateKey}_ecoclimas_tipo", 'Convencional');
            $precio = Cache::get("{$stateKey}_ecoclimas_precio", 1500);
            Cache::put("{$stateKey}_ecoclimas_tonelaje", $tonelajes[$msg], now()->addDay());
            Cache::put("{$stateKey}_ecoclimas_nota", "ECOCLIMAS - {$tipoEquipo} {$tonelajes[$msg]} - \${$precio}", now()->addDay());
            Cache::put("{$stateKey}_tipo", 'Instalación', now()->addDay());
            Cache::put("{$stateKey}_temp_cotizacion", [
                'tipo' => 'Instalación', 'capacidad' => $tonelajes[$msg], 'total' => $precio,
                'detalle' => "Instalación Ecoclimas {$tipoEquipo} {$tonelajes[$msg]}", 'con_equipo' => false
            ], now()->addDay());
            $this->sendReply("💰 *Presupuesto: Instalación Ecoclimas*\n\n• *Tipo:* {$tipoEquipo}\n• *Capacidad:* {$tonelajes[$msg]}\n• *Total:* \${$precio}\n\n📋 *Incluye:*\n• Montaje de evaporador en pared\n• Instalación de tubería de líneas de gas (hasta 3m)\n• Perforación de muro\n• Vacío de línea\n• Puesta en marcha\n\n*No incluye materiales eléctricos.*\n\n1️⃣ *Agendar instalación*\n2️⃣ *Volver al menú*");
            Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
            return;
        } elseif ($state === 'presupuesto_tipo') {
            $this->handlePresupuestoTipo($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'presupuesto_capacidad') {
            $this->handlePresupuestoCapacidad($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'presupuesto_equipo') {
            $this->handlePresupuestoEquipo($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'presupuesto_seleccion_equipo') {
            $this->handlePresupuestoSeleccionEquipo($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'presupuesto_confirmar') {
            $this->handlePresupuestoConfirmar($msg, $empresa, $stateKey);
            return;
        } elseif ($state === 'garantia_menu') {
            if ($msg === '1') {
                $this->sendReply("🛡️ *Registro de Garantía*\n\n" .
                    "¿Quién realizó la instalación de tu equipo?\n\n" .
                    "1️⃣ *Centro de servicio autorizado*\n" .
                    "2️⃣ *Técnico de confianza*\n" .
                    "3️⃣ *Climas del Desierto*");
                Cache::put($stateKey, 'garantia_instalador', now()->addDay());
                return;
            }
            if ($msg === '2' || in_array($msg, ['menu', 'atras', 'atrás', '0'])) {
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $this->sendReply("⚠️ Opción no válida. Responde 1️⃣ para registrar garantía o 2️⃣ para volver.");
            Cache::put($stateKey, 'garantia_menu', now()->addDay());
            return;
        } elseif ($state === 'garantia_instalador') {
            if (in_array($msg, ['menu', 'atras', 'atrás', '0'])) {
                Cache::put($stateKey, 'menu', now()->addDay());
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $instaladores = ['1' => 'Centro de servicio autorizado', '2' => 'Técnico de confianza', '3' => 'Climas del Desierto'];
            $instalador = $instaladores[$msg] ?? null;
            if (!$instalador) {
                $this->sendReply("⚠️ Opción no válida. Responde 1, 2 o 3.");
                Cache::put($stateKey, 'garantia_instalador', now()->addDay());
                return;
            }
            Cache::put("{$stateKey}_warranty_instalador", $instalador, now()->addDay());
            Cache::put("{$stateKey}_tipo", 'Registro de Garantía', now()->addDay());
            $this->sendReply("📝 *Registro de Garantía*\n\n" .
                "Instalado por: *{$instalador}*\n\n" .
                "Describe brevemente ¿qué problema o falla presenta tu equipo?\n\n" .
                "*Escribe libremente* los detalles (excribe *menu* para volver):");
            Cache::put($stateKey, 'garantia_descripcion', now()->addDay());
            return;
        } elseif ($state === 'garantia_descripcion') {
            if (trim(strtolower($msg)) === 'menu') {
                Cache::put($stateKey, 'menu', now()->addDay());
                $this->mostrarMenu($empresa, $stateKey);
                return;
            }
            $instalador = Cache::get("{$stateKey}_warranty_instalador", 'No especificado');
            Cache::put("{$stateKey}_warranty_problema", $this->incomingMessage, now()->addDay());
            $this->sendReply("📅 *Registro de Garantía*\n\n" .
                "✅ Información registrada:\n" .
                "• Instalado por: *{$instalador}*\n" .
                "• Problema: *{$this->incomingMessage}*\n\n" .
                "Ahora selecciona una fecha para la visita del técnico:");
            $this->mostrarCalendarioDisponible($empresa, $stateKey);
            return;
        } elseif ($state === 'vircom_cita_tipo') {
            $tipos = ['1' => 'Instalación', '2' => 'Reparación', '3' => 'Garantía', '4' => 'Diagnóstico', '5' => 'Otro'];
            $tipo = $tipos[$msg] ?? null;
            if (!$tipo) {
                $this->sendReply("⚠️ Opción no válida. Responde 1️⃣ al 5️⃣.\n\nEscribe *menu* para volver.");
                return;
            }
            Cache::put("{$stateKey}_tipo", $tipo, now()->addDay());
            Cache::put("{$stateKey}_vircom_tipo", true, now()->addDay());
            $this->sendReply("📝 ¿Cuál es tu *nombre completo*?");
            Cache::put($stateKey, 'agendar_nombre', now()->addDay());
            return;
        } elseif ($state === 'esperando_descripcion_ticket') {
            try {
                $cliente = $this->buscarClientePorWaId();
                $year = date('Y');
                $lastTicket = \App\Models\Ticket::whereYear('created_at', $year)->latest()->first();
                $sequence = $lastTicket ? (int) substr($lastTicket->numero, -5) + 1 : 1;
                $numero = 'TKT-' . $year . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

                $sinPoliza = !$cliente || !\App\Models\PolizaServicio::where('cliente_id', $cliente->id)
                    ->whereIn('estado', ['activa', 'vencida_en_gracia'])->exists();
                $ticket = \App\Models\Ticket::create([
                    'numero' => $numero,
                    'titulo' => 'Soporte WhatsApp: ' . mb_substr($this->incomingMessage, 0, 100),
                    'descripcion' => $this->incomingMessage,
                    'estado' => 'abierto',
                    'origen' => 'whatsapp',
                    'tipo_servicio' => $sinPoliza ? 'costo' : 'garantia',
                    'cliente_id' => $cliente?->id,
                    'nombre_contacto' => $cliente?->nombre_razon_social ?? 'Cliente WhatsApp',
                    'email_contacto' => $cliente?->email,
                    'telefono_contacto' => $cliente?->telefono,
                    'empresa_id' => $empresa->id ?? 1,
                ]);
                $this->sendReply("🎫 *Ticket Creado*\n\n✅ *Folio:* {$numero}\n*Problema:* {$this->incomingMessage}\n\nUn técnico te atenderá pronto. Puedes dar seguimiento en:\nhttps://asistenciavircom.com/portal/tickets\n\nEscribe *menu* para volver.");
            } catch (\Exception $e) {
                $this->sendReply("⚠️ Ocurrió un error al crear el ticket. Por favor intenta más tarde.\n\nEscribe *menu* para volver.");
            }
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        if ($reply) {
            Cache::put($stateKey, $nextState, now()->addDay());
            $this->sendReply($reply);
        }
    }

    protected function lookupAllServices(string $phoneInput): array
    {
        $digitsOnly = preg_replace('/\D+/', '', $phoneInput);
        if (strlen($digitsOnly) < 10) {
            return [];
        }

        $last10 = substr($digitsOnly, -10);
        $clientes = Cliente::where('empresa_id', $this->empresaId)
            ->get(['id', 'telefono']);

        $cliente = $clientes->first(function ($c) use ($last10) {
            $cleanPhone = preg_replace('/\D+/', '', $c->telefono ?? '');
            return str_contains($cleanPhone, $last10);
        });

        if (!$cliente) {
            return [];
        }

        $tickets = \App\Models\Ticket::where('empresa_id', $this->empresaId)
            ->where('cliente_id', $cliente->id)
            ->latest()
            ->limit(3)
            ->get();

        $citas = Cita::where('empresa_id', $this->empresaId)
            ->where('cliente_id', $cliente->id)
            ->latest()
            ->limit(3)
            ->get();

        $taller = \App\Models\TallerOrden::where('empresa_id', $this->empresaId)
            ->where('cliente_id', $cliente->id)
            ->latest()
            ->limit(3)
            ->get();

        $services = [];
        foreach ($tickets as $ticket) {
            $services[] = [
                'tipo' => 'Ticket de Soporte',
                'folio' => $ticket->folio,
                'estado' => $ticket->estado,
                'fecha' => $ticket->created_at->format('d/m/Y'),
                'detalle' => $ticket->titulo,
                'timestamp' => $ticket->created_at,
            ];
        }

        foreach ($citas as $cita) {
            $services[] = [
                'tipo' => 'Cita de Servicio',
                'folio' => $cita->folio,
                'estado' => $cita->estado,
                'fecha' => $cita->fecha_hora->format('d/m/Y H:i'),
                'detalle' => $cita->descripcion,
                'timestamp' => $cita->created_at ?? $cita->fecha_hora,
            ];
        }

        foreach ($taller as $orden) {
            $services[] = [
                'tipo' => 'Taller - Reparación',
                'folio' => $orden->folio,
                'estado' => $orden->estado,
                'fecha' => $orden->fecha_recepcion?->format('d/m/Y') ?? 'N/A',
                'detalle' => ($orden->equipo_marca ?? '') . ' ' . ($orden->equipo_modelo ?? '') . ' - ' . ($orden->problema_reportado ?? ''),
                'timestamp' => $orden->fecha_recepcion ?? $orden->created_at,
            ];
        }

        usort($services, function ($a, $b) {
            $tsA = $a['timestamp'] instanceof \Carbon\Carbon ? $a['timestamp']->getTimestamp() : strtotime($a['timestamp']);
            $tsB = $b['timestamp'] instanceof \Carbon\Carbon ? $b['timestamp']->getTimestamp() : strtotime($b['timestamp']);
            return $tsB <=> $tsA;
        });

        return $services;
    }

    protected function handleServicesResult(array $services, string &$nextState, bool $automatic = false): string
    {
        $prefix = $automatic ? "🔍 *Consultar Reparación:*\n\nDetectamos tu número de teléfono automáticamente.\n\n" : "";

        if (count($services) === 1) {
            $s = $services[0];
            $nextState = 'menu';
            return $prefix .
                   "📋 *Detalles de tu Servicio Encontrado:*\n\n" .
                   "• **Tipo:** " . $s['tipo'] . "\n" .
                   "• **Folio:** " . $s['folio'] . "\n" .
                   "• **Estado:** " . strtoupper($s['estado']) . "\n" .
                   "• **Fecha:** " . $s['fecha'] . "\n" .
                   "• **Detalles:** " . ($s['detalle'] ?: 'Sin detalles adicionales') . "\n\n" .
                   "Escribe *menu* para volver al menú principal.";
        }

        $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
        $reply = $prefix .
                 "🔍 *Servicios Encontrados:*\n\n" .
                 "Detectamos múltiples servicios registrados con tu número de teléfono. Por favor responde con el número de la opción que deseas consultar:\n\n";

        foreach ($services as $idx => $s) {
            $emoji = $numEmojis[$idx] ?? ($idx + 1) . ".";
            $reply .= "{$emoji} *{$s['tipo']}* (Folio: {$s['folio']}) — _{$s['estado']}_\n";
        }
        $reply .= "\nO escribe *menu* para volver al menú principal.";

        $servicesKey = "whatsapp_services_list_{$this->empresaId}_{$this->waId}";
        Cache::put($servicesKey, $services, now()->addMinutes(30));

        $nextState = 'waiting_for_service_selection';
        return $reply;
    }

    protected function handleReagendarSeleccion(string $msg, Empresa $empresa, string $stateKey): void
    {
        $citas = Cache::get("{$stateKey}_reagendar_citas", []);
        
        if (in_array($msg, ['menu', 'atras', 'atrás', '0'])) {
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        $idx = (int) $msg;
        if ($idx < 1 || $idx > count($citas)) {
            $this->sendReply("⚠️ Opción no válida. Responde el *número* de la cita o escribe *menu* para volver.");
            Cache::put($stateKey, 'reagendar_seleccion', now()->addDay());
            return;
        }

        $cita = $citas[$idx - 1];
        $fecha = Carbon::parse($cita['fecha_hora'])->format('d/m/Y H:i');
        $tipo = ucfirst(str_replace('_', ' ', $cita['tipo_servicio']));
        $folio = $cita['folio'];
        
        $this->sendReply("📅 *Reagendar: {$folio}*\n\n" .
            "• *Servicio:* {$tipo}\n" .
            "• *Fecha actual:* {$fecha}\n\n" .
            "Te conecto con un asesor para cambiar la fecha.");
        
        $this->conectarAsesor($empresa, $stateKey);
    }

    protected function conectarAsesor(?Empresa $empresa = null, string $stateKey = ''): void
    {
        $dentroHorario = $this->isBusinessHours($empresa);
        $conv = WhatsAppConversation::where('wa_id', $this->waId)
            ->where('empresa_id', $this->empresaId)
            ->first();

        if ($dentroHorario) {
            if ($conv) {
                $users = \App\Models\User::query()
                    ->where('empresa_id', $this->empresaId)
                    ->where('activo', true)
                    ->role(['admin', 'super-admin', 'ventas'])
                    ->get();

                foreach ($users as $user) {
                    \App\Models\UserNotification::createForUser(
                        $user->id,
                        'whatsapp_chat',
                        '🙋 Cliente solicita asesor: ' . ($conv->contact_name ?: ($conv->from_name ?: $this->waId)),
                        $this->incomingMessage,
                        [
                            'wa_id' => $this->waId,
                            'whatsapp_conversation_id' => $conv->id,
                            'action' => 'human_handoff',
                        ],
                        '/marketing/whatsapp-inbox?wa=' . rawurlencode((string) $this->waId),
                        'fas fa-headset'
                    );
                }
                $conv->update(['status' => 'open']);
            }

            $reply = "👤 *Atención Humana:*\n\n" .
                     "Entendido. He transferido esta conversación a uno de nuestros asesores.\n\n" .
                     "En un momento se pondrán en contacto contigo.\n\n¡Gracias por tu paciencia!";
            $nextState = 'human';
            Cache::put("{$stateKey}_human_since", now(), now()->addDay());
        } else {
            $reply = "👤 *Atención Humana:*\n\n" .
                     "Nuestro horario de atención es de *L-V 9:00 AM a 6:00 PM* y *S 9:00 AM a 2:00 PM*.\n\n" .
                     "Actualmente estamos fuera de este horario, pero nuestro *robot* sigue disponible para ayudarte las 24 horas.\n\n" .
                     "Escribe *menu* para ver todas las opciones disponibles.";
            $nextState = 'menu_select';
        }

        Cache::put($stateKey, $nextState, now()->addDay());
        $this->sendReply($reply);
    }

    protected function handleSatisfactionFlow(string $state, string $msg, string $stateKey): bool
    {
        $isSatisfactionRequest = in_array($msg, ['encuesta', 'satisfaccion', 'satisfacción', 'opinion', 'opinión']);
        if (!str_starts_with($state, 'satisfaccion_') && !$isSatisfactionRequest) {
            return false;
        }

        if ($isSatisfactionRequest && !str_starts_with($state, 'satisfaccion_')) {
            $this->sendReply("Queremos conocer tu experiencia. Responde con una calificación del 1 al 5, donde 5 es la mejor experiencia.");
            Cache::put($stateKey, 'satisfaccion_calificacion', now()->addDays(3));
            return true;
        }

        $encuestaId = Cache::get("{$stateKey}_encuesta_id");
        $encuesta = $encuestaId ? EncuestaSatisfaccion::find($encuestaId) : null;
        if (!$encuesta) {
            Cache::put($stateKey, 'menu', now()->addDay());
            return false;
        }

        if (in_array($msg, ['menu', 'menú', 'cancelar'])) {
            Cache::forget("{$stateKey}_encuesta_id");
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu(Empresa::find($this->empresaId), $stateKey);
            return true;
        }

        if ($state === 'satisfaccion_calificacion') {
            $rating = filter_var($this->incomingMessage, FILTER_VALIDATE_INT);
            if ($rating === false || $rating < 1 || $rating > 5) {
                $this->sendReply('Responde únicamente con un número del 1 al 5.');
                return true;
            }

            $encuesta->update(['calificacion' => $rating]);
            Cache::put($stateKey, 'satisfaccion_comentario', now()->addDays(3));
            $this->sendReply('Gracias. ¿Quieres dejar una queja, sugerencia o felicitación? Escribe tu comentario o responde "ninguno".');
            return true;
        }

        $comentario = trim($this->incomingMessage);
        $codigo = $this->generarCodigoCupon();
        $vigencia = now()->addDays(90);
        $encuesta->update([
            'comentario' => in_array(mb_strtolower($comentario), ['ninguno', 'no', 'n/a']) ? null : $comentario,
            'cupon_codigo' => $codigo,
            'cupon_vigencia_hasta' => $vigencia,
            'respondida_at' => now(),
        ]);

        Cache::forget("{$stateKey}_encuesta_id");
        Cache::put($stateKey, 'menu', now()->addDay());
        $this->sendReply("Gracias por compartir tu experiencia. Tu cupón independiente de {$encuesta->cupon_porcentaje}% para mantenimiento preventivo es *{$codigo}*. Tiene vigencia hasta {$vigencia->format('d/m/Y')}.");
        return true;
    }

    protected function generarCodigoCupon(): string
    {
        do {
            $code = 'VP-' . Str::upper(Str::random(10));
        } while (EncuestaSatisfaccion::where('cupon_codigo', $code)->exists());

        return $code;
    }

    protected function textoMenuCompleto(bool $tieneCitas = false, bool $isVircom = false): string
    {
        if ($isVircom) {
            return "¿Cómo te podemos ayudar?\n\n" .
                 "1️⃣ *Agendar Cita* (Visita técnica)\n" .
                 "2️⃣ *Cotización*\n" .
                 "3️⃣ *Abrir Ticket de Soporte*\n" .
                 "4️⃣ *Consultar Folio* (Ticket o servicio existente)\n" .
                 "5️⃣ *Productos* (Catálogo en línea)\n" .
                 "6️⃣ *Facturación*\n" .
                 "7️⃣ *Hablar con Asesor*\n" .
                 "8️⃣ *Preguntas Frecuentes*\n" .
                 "9️⃣ *Garantías*\n" .
                 "0️⃣ *Salir*\n" .
                 "\nEscribe el *número* de la opción deseada.\n💡 *Tip:* Escribe tu folio directo (TKT-2026-...)";
        }
        return "¿Cómo te podemos ayudar?\n\n" .
             "1️⃣ *Instalación Sin Costo*\n" .
             "2️⃣ *Instalación Ecoclimas*\n" .
             "3️⃣ *Agendar Servicio* (Limpieza, Reparación, Instalación)\n" .
             "4️⃣ *Consultar Folio* (Citas, Tickets, Servicios)\n" .
             "5️⃣ *Catálogo y Cotización*\n" .
             "6️⃣ *Hablar con Asesor*\n" .
             "7️⃣ *Facturación*\n" .
             "8️⃣ *Preguntas Frecuentes*\n" .
             "9️⃣ *Garantías y Requisitos*\n" .
              ($tieneCitas ? "0️⃣ *Reagendar Cita*\n" : "0️⃣ *Salir*\n") .
             "\nEscribe el *número* de la opción deseada.\n💡 *Tip:* Escribe tu folio directo (T-001, CITA-123, WA-xxx)";
    }

    protected function mostrarMenu(Empresa $empresa, string $stateKey): void
    {
        Cache::put($stateKey, 'menu', now()->addDay());
        $this->sendReply("🌵 Escribe *menu* para ver las opciones disponibles.");
    }

    protected function mostrarMenuCatalogo(Empresa $empresa, string $stateKey): void
    {
        $query = \App\Models\Categoria::where('empresa_id', $empresa->id)
            ->where('estado', 'activo');
        if (!app()->environment('testing')) {
            $query->whereIn('nombre', ['Minisplit Mirage', 'Boiler']);
        }
        $categorias = $query->get()
            ->sortBy(function ($cat) {
                return $cat->nombre === 'Minisplit Mirage' ? 0 : 1;
            })
            ->values();

        if ($categorias->isEmpty()) {
            $this->sendReply("💰 *Catálogo de Precios:*\n\n" .
                     "Por el momento no contamos con categorías registradas.\n\n" .
                     "Escribe *menu* para regresar al menú principal.");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        $showedPricesKey = "whatsapp_showed_prices_{$this->empresaId}_{$this->waId}";
        Cache::forget($showedPricesKey);

        $popularKeywords = ['aire', 'boiler', 'minisplit mirage', 'electrico', 'eléctrico'];
        $destacadas = [];
        $otras = [];
        foreach ($categorias as $cat) {
            $nameLower = mb_strtolower($cat->nombre);
            $isPopular = false;
            foreach ($popularKeywords as $kw) {
                if (str_contains($nameLower, $kw)) {
                    $isPopular = true;
                    break;
                }
            }
            if ($isPopular) {
                $destacadas[] = $cat;
            } else {
                $otras[] = $cat;
            }
        }

        $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];

        $reply = "💰 *Catálogo de Precios*\n\n" .
                 "🔍 *Busca* escribiendo el nombre del producto\n" .
                 "O elige una *categoría*:\n\n";

        $countDestacadas = count($destacadas);
        if ($countDestacadas > 0 && count($otras) > 0) {
            foreach ($destacadas as $idx => $cat) {
                $emoji = $numEmojis[$idx] ?? ($idx + 1) . ".";
                $reply .= "{$emoji} *{$cat->nombre}*\n";
            }
            $otherIdx = $countDestacadas;
            $otherEmoji = $numEmojis[$otherIdx] ?? ($otherIdx + 1) . ".";
            $reply .= "{$otherEmoji} *Otras categorías*\n";
        } else {
            foreach ($categorias as $idx => $cat) {
                $emoji = $numEmojis[$idx] ?? ($idx + 1) . ".";
                $reply .= "{$emoji} *{$cat->nombre}*\n";
            }
        }

        $pricing = config('whatsapp.pricing');
        $minisplitPrice = number_format($pricing['minisplit_mirage_life_12']);
        $m1 = number_format($pricing['maintenance']['1_ton']);
        $m15 = number_format($pricing['maintenance']['1.5_ton']);
        $m2 = number_format($pricing['maintenance']['2_ton']);
        $m3 = number_format($pricing['maintenance']['3_ton']);

        $reply .= "\n🔥 *Promociones de Temporada:*\n" .
                 "• Minisplit Mirage Life 12 Plus (1 Ton, Solo Frío): *\${$minisplitPrice}*\n" .
                 "• Mantenimiento: 1Ton \${$m1} | 1.5Ton \${$m15} | 2Ton \${$m2} | 3Ton \${$m3}\n\n" .
                 "🌐 *Catálogo completo:* climasdeldesierto.com/tienda\n\n" .
                 "Escribe *menu* para volver.";

        Cache::put($stateKey, 'waiting_for_price_category', now()->addDay());
        $this->sendReply($reply);
    }
}
