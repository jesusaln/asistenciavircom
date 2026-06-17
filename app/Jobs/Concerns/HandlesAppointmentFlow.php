<?php

namespace App\Jobs\Concerns;

use App\Models\Empresa;
use App\Models\WhatsAppConversation;
use App\Models\Cita;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesAppointmentFlow
{
    protected function savePreviousState(string $stateKey, string $currentState): void
    {
        Cache::put("{$stateKey}_previous_state", $currentState, now()->addDay());
    }

    protected function goBack(string $stateKey, Empresa $empresa): bool
    {
        $previous = Cache::get("{$stateKey}_previous_state");
        if (!$previous) {
            // No previous state — fall back to main menu
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->sendReply("🔙 Regresando al menú principal...");
            $this->handleMenuChatbot($empresa);
            return true;
        }

        Cache::put($stateKey, $previous, now()->addDay());

        // Re-display the prompt for known previous states
        switch ($previous) {
            case 'tienda_departamental_elegir':
                $this->sendReply("🔙 *Regresando...*\n\n🏪 ¿En qué tienda realizaste tu compra?\n\n1️⃣ *Liverpool*\n2️⃣ *Sears*\n3️⃣ *Home Depot*\n4️⃣ *Coppel*\n5️⃣ *Elektra*\n6️⃣ *City Club*\n7️⃣ *Sams Club*\n8️⃣ *Otra tienda departamental* (escribe el nombre)");
                break;

            case 'agendar_tipo':
                $this->sendReply("🔙 *Regresando...*\n\n📅 ¿Qué tipo de servicio necesitas?\n\n1️⃣ *Instalación*\n2️⃣ *Mantenimiento*\n3️⃣ *Reparación*\n4️⃣ *Otro*\n\nResponde el *número* del servicio.");
                break;

            case 'ecoclimas_tipo':
                $this->sendReply("🔙 *Regresando...*\n\n🏢 *Instalación Ecoclimas*\n\n¿Qué tipo de equipo es?\n\n1️⃣ *Convencional* — \$1,500\n2️⃣ *Inverter* — \$1,800\n\nResponde el *número*.");
                break;

            case 'ecoclimas_tonelaje':
                $this->sendReply("🔙 *Regresando...*\n\n📏 ¿De qué *capacidad* es el equipo?\n\n1️⃣ *1 Tonelada*\n2️⃣ *1.5 Toneladas*\n3️⃣ *2 Toneladas*");
                break;

            case 'agendar_fecha':
                // Re-show cached calendar dates instead of recalculating
                $fechasMap = Cache::get("{$stateKey}_fechas_map", []);
                if (!empty($fechasMap)) {
                    $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                    $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
                    $reply = "🔙 *Regresando...*\n\n📅 *Selecciona el día:*\n\n";
                    foreach ($fechasMap as $num => $fechaStr) {
                        $fecha = Carbon::parse($fechaStr);
                        $emoji = $numEmojis[$num - 1] ?? $num . '.';
                        $nombreDia = $diasSemana[(int) $fecha->dayOfWeek];
                        $reply .= "{$emoji} {$nombreDia} {$fecha->format('d/m/Y')}\n";
                    }
                    $reply .= "\nResponde con el *número* del día que prefieras.\n✏️ *Atrás* (escribe la palabra)";
                    $this->sendReply($reply);
                } else {
                    $this->mostrarCalendarioDisponible($empresa, $stateKey);
                }
                break;

            case 'menu':
            case 'menu_select':
                Cache::put($stateKey, 'menu', now()->addDay());
                $this->handleMenuChatbot($empresa);
                break;

            default:
                $this->sendReply("🔙 Regresando al paso anterior...");
                break;
        }

        return true;
    }

    protected function mostrarResumenOpciones(string $titulo, array $opciones, string $stateKey, string $nextState, ?string $extra = null): void
    {
        $reply = "{$titulo}\n\n";
        foreach ($opciones as $num => $texto) {
            $reply .= "{$num} {$texto}\n";
        }
        if ($extra) $reply .= "\n{$extra}";
        $reply .= "\n\n✏️ *Atrás* (escribe la palabra)";
        $this->sendReply($reply);
        Cache::put($stateKey, $nextState, now()->addDay());
    }

    protected function handleAgendarNombre(string $msg, Empresa $empresa, string $stateKey): void
    {
        $nombre = trim($this->incomingMessage);
        if (strlen($nombre) < 2) {
            $this->sendReply("⚠️ Por favor escribe un *nombre* válido (al menos 2 caracteres) para registrar la cita.");
            Cache::put($stateKey, 'agendar_nombre', now()->addDay());
            return;
        }
        Cache::put("{$stateKey}_nombre", $nombre, now()->addDay());
        $this->sendReply("📬 *Dirección de la cita:*\n\nEscribe tu *Código Postal* (5 dígitos):");
        Cache::put($stateKey, 'agendar_cp', now()->addDay());
    }

    protected function handleAgendarTipo(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array($msg, ['atras', 'atrás', '0', 'menu'])) {
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }
        $servicioMap = ['1' => 'Instalación', '2' => 'Mantenimiento', '3' => 'Reparación', '4' => 'Otro'];
        $selected = $servicioMap[$msg] ?? null;
        if (!$selected) {
            $this->sendReply("⚠️ Opción no válida. Responde 1️⃣, 2️⃣, 3️⃣ o 4️⃣.");
            Cache::put($stateKey, 'agendar_tipo', now()->addDay());
            return;
        }
        Cache::put("{$stateKey}_tipo", $selected, now()->addDay());
        $this->mostrarCalendarioDisponible($empresa, $stateKey);
    }

    protected function mostrarCalendarioDisponible(Empresa $empresa, string $stateKey, bool $esReagendar = false): void
    {
        $tz = $empresa->timezone ?: config('app.timezone', 'America/Hermosillo');
        $hoy = Carbon::today($tz);
        $ahora = Carbon::now($tz);

        $mantenimiento6m = Cache::get("{$stateKey}_mantenimiento_preventivo_6m", false);
        if ($mantenimiento6m) {
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
            $base = $fechaInstalacion ? Carbon::parse($fechaInstalacion) : $hoy;
            $minimo = $base->copy()->addMonths(6);
        } else {
            $minimo = $ahora->hour >= 12 ? $hoy->copy()->addDay() : $hoy;
        }

        $dispService = app(\App\Services\DisponibilidadService::class);
        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

        $fechasDisponibles = [];
        $fechaActual = $minimo->copy();
        $limite = $minimo->copy()->addDays(14);

        while ($fechaActual->lte($limite) && count($fechasDisponibles) < 7) {
            $diaSemana = (int) $fechaActual->dayOfWeek;
            if ($diaSemana !== 0) {
                $hayDisponibilidad = $dispService->hayDisponibilidad($this->empresaId, $fechaActual->format('Y-m-d'), 'manana')
                    || $dispService->hayDisponibilidad($this->empresaId, $fechaActual->format('Y-m-d'), 'tarde');
                if ($hayDisponibilidad) {
                    $fechasDisponibles[] = $fechaActual->copy();
                }
            }
            $fechaActual->addDay();
        }

        if (empty($fechasDisponibles)) {
            $this->sendReply("😕 Lo sentimos, no encontramos días disponibles en los próximos 14 días. Por favor intenta más tarde o contacta a un asesor.");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
        $reply = "📅 *Selecciona el día:*\n\n";

        $fechasMap = [];
        foreach ($fechasDisponibles as $idx => $fecha) {
            $emoji = $numEmojis[$idx] ?? ($idx + 1) . '.';
            $nombreDia = $diasSemana[(int) $fecha->dayOfWeek];
            $reply .= "{$emoji} {$nombreDia} {$fecha->format('d/m/Y')}\n";
            $fechasMap[$idx + 1] = $fecha->format('Y-m-d');
            if ($idx >= 9) break;
        }

        $reply .= "\nResponde con el *número* del día que prefieras.\n✏️ *Atrás* (escribe la palabra)";

        $previous = Cache::get($stateKey, 'menu');
        Cache::put("{$stateKey}_previous_state", $previous, now()->addDay());
        Cache::put("{$stateKey}_fechas_map", $fechasMap, now()->addDay());
        Cache::put($stateKey, 'agendar_fecha', now()->addDay());

        $this->sendReply($reply);
    }

    protected function handleAgendarFecha(string $msg, Empresa $empresa, string $stateKey, bool $esReagendar = false): void
    {
        $fechasMap = Cache::get("{$stateKey}_fechas_map", []);
        $idx = (int) $msg;

        if (trim(strtolower($msg)) === 'menu') {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        if ($msg === '0' || $msg === 'atras' || $msg === 'atrás') {
            $this->goBack($stateKey, $empresa);
            return;
        }

        if (!$idx || !isset($fechasMap[$idx])) {
            $this->sendReply("⚠️ Opción no válida. Por favor selecciona un *número* de la lista.");
            Cache::put($stateKey, 'agendar_fecha', now()->addDay());
            return;
        }

        $fechaSel = $fechasMap[$idx];
        Cache::put("{$stateKey}_fecha", $fechaSel, now()->addDay());

        $dispService = app(\App\Services\DisponibilidadService::class);
        $horarios = array_values(array_filter(
            $dispService->getHorariosDisponibles($this->empresaId, $fechaSel),
            fn($h) => !empty($h['disponible'])
        ));

        if (empty($horarios)) {
            $this->sendReply("😕 Lo sentimos, ya no hay horarios disponibles para esa fecha. Por favor elige otro día.");
            $this->mostrarCalendarioDisponible($empresa, $stateKey, $esReagendar);
            return;
        }

        Cache::put("{$stateKey}_horarios", $horarios, now()->addDay());

        $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
        $reply = "⏰ *Horarios Disponibles para {$fechaSel}:*\n\n";
        foreach ($horarios as $hIdx => $slot) {
            $emoji = $numEmojis[$hIdx] ?? ($hIdx + 1) . '.';
            $slotEmoji = $slot['emoji'] ?? '';
            $nombre = $slot['nombre'] ?? '';
            $horaInicio12 = isset($slot['hora_inicio']) ? Carbon::parse($slot['hora_inicio'])->format('g:i A') : '—';
            $horaFin12 = isset($slot['hora_fin']) ? Carbon::parse($slot['hora_fin'])->format('g:i A') : '—';
            $reply .= "{$emoji} {$slotEmoji} {$nombre} ({$horaInicio12} - {$horaFin12})\n";
        }
        $reply .= "\nResponde el *número* del horario que prefieras.\n✏️ *Atrás* (escribe la palabra)";

        $previous = Cache::get($stateKey, 'menu');
        Cache::put("{$stateKey}_previous_state", $previous, now()->addDay());
        Cache::put($stateKey, 'agendar_horario', now()->addDay());
        $this->sendReply($reply);
    }

    protected function handleAgendarHorario(string $msg, Empresa $empresa, string $stateKey): void
    {
        $horarios = Cache::get("{$stateKey}_horarios", []);
        $idx = (int) $msg;

        if (trim(strtolower($msg)) === 'menu') {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        if ($msg === '0' || $msg === 'atras' || $msg === 'atrás') {
            $this->goBack($stateKey, $empresa);
            return;
        }

        if (!$idx || !isset($horarios[$idx - 1])) {
            $this->sendReply("⚠️ Opción no válida. Por favor selecciona un *número* de horario.");
            Cache::put($stateKey, 'agendar_horario', now()->addDay());
            return;
        }

        $slot = $horarios[$idx - 1];
        Cache::put("{$stateKey}_horario_inicio", $slot['hora_inicio'], now()->addDay());
        Cache::put("{$stateKey}_horario_fin", $slot['hora_fin'], now()->addDay());
        Cache::put("{$stateKey}_tecnico_id", $slot['tecnico_id'] ?? null, now()->addDay());

        $cliente = $this->buscarClientePorWaId();
        $tieneDireccionCompleta = $cliente && $cliente->calle && $cliente->codigo_postal && $cliente->colonia && $cliente->numero_exterior;

        if ($tieneDireccionCompleta) {
            Cache::put("{$stateKey}_cp", $cliente->codigo_postal, now()->addDay());
            Cache::put("{$stateKey}_calle", $cliente->calle, now()->addDay());
            Cache::put("{$stateKey}_numero", $cliente->numero_exterior, now()->addDay());
            Cache::put("{$stateKey}_colonia", $cliente->colonia, now()->addDay());
            $this->mostrarResumenConfirmacion($empresa, $stateKey);
            return;
        }

        $this->sendReply("📬 Para registrar la dirección de tu cita, escribe tu *Código Postal* (5 dígitos):");
        Cache::put($stateKey, 'agendar_cp', now()->addDay());
    }

    protected function handleAgendarConfirmar(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (trim(strtolower($msg)) === 'menu') {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        if ($msg === '0' || $msg === 'atras' || $msg === 'atrás') {
            $this->goBack($stateKey, $empresa);
            return;
        }

        if ($msg === '1') {
            Cache::put($stateKey, 'survey_plantas', now()->addDay());
            $this->sendReply("1️⃣ *¿Su casa es de una planta o de dos plantas?*\n\n" .
                "1️⃣ Una planta\n" .
                "2️⃣ Dos plantas\n" .
                "3️⃣ Es local comercial\n" .
                "4️⃣ Otros (por favor especifica cuál)");
        } elseif ($msg === '2') {
            $this->sendReply("📬 Editemos la dirección.\n\nEscribe tu *Código Postal* (5 dígitos):");
            Cache::put($stateKey, 'agendar_cp', now()->addDay());
        } else {
            $this->sendReply("⚠️ Opción no válida. Responde 1 para confirmar o 2 para editar dirección.");
            Cache::put($stateKey, 'agendar_confirmar', now()->addDay());
        }
    }

    protected function handleAgendarRequisitos(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', '0', 'atras', 'atrás'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        if ($msg === '1') {
            Cache::put($stateKey, 'agendar_confirmar', now()->addDay());
            $this->sendReply("✅ ¡Perfecto! Procedamos con tu cita.\n\n" .
                "Solo unas preguntas rápidas para preparar la visita:");
            $this->handleAgendarConfirmar('1', $empresa, $stateKey);
        } elseif ($msg === '2') {
            $mat = config('whatsapp.materiales');
            $empProd = \App\Models\Producto::where('empresa_id', $this->empresaId);
            $pTermico = $empProd->clone()->find($mat['producto_termico_2p']);
            $pCableTierra = $empProd->clone()->find($mat['producto_cable_tierra']);
            $pKitTierra = $empProd->clone()->find($mat['producto_kit_tierra']);
            $pCable12 = $empProd->clone()->find($mat['producto_cable_calibre12']);
            $pCentroCarga = $empProd->clone()->find($mat['producto_centro_carga']);
            $pBasePared1 = $empProd->clone()->find($mat['producto_base_pared_1']);
            $pBasePared2 = $empProd->clone()->find($mat['producto_base_pared_2']);
            $pLineaGas1 = $mat['producto_linea_gas_1ton'] ? $empProd->clone()->find($mat['producto_linea_gas_1ton']) : null;
            $pLineaGas2 = $mat['producto_linea_gas_2ton'] ? $empProd->clone()->find($mat['producto_linea_gas_2ton']) : null;
            $precioTermico = $pTermico?->precio_con_iva ?? $mat['termico_sencillo'] ?? 250;
            $precioTermicoDoble = $pTermico?->precio_con_iva ? round($pTermico->precio_con_iva * 1.15) : ($mat['termico_doble'] ?? 500);
            $precioCableTierra = $pCableTierra?->precio_con_iva ?? $mat['tierra_metro'] ?? 75;
            $precioKitTierra = $pKitTierra?->precio_con_iva ?? $mat['tierra_inverter_instalada'] ?? 650;
            $precioCable12 = $pCable12?->precio_con_iva ?? $mat['cable_calibre12_metro'] ?? 75;
            $precioCentroCarga = $pCentroCarga?->precio_con_iva ?? 259;
            $precioBasePared = $pBasePared1?->precio_con_iva ?? 950;
            $precioLineaGas1 = $pLineaGas1?->precio_con_iva ?? 950;
            $precioLineaGas2 = $pLineaGas2?->precio_con_iva ?? 1250;
            $telAsesor = $mat['telefono_asesor'];
            $this->sendReply("🛠️ *Costos estimados de materiales:*\n\n" .
                "🔌 *Térmico/breaker:* desde \$" . number_format($precioTermico) . " (sencillo 110V) / \$" . number_format($precioTermicoDoble) . " (doble 220V)\n" .
                "⚡ *Cable de tierra física:* \$" . number_format($precioCableTierra) . " x metro 100% cobre (calibre 8, color verde)\n" .
                "🌍 *Tierra para equipo Inverter (instalada):* \$" . number_format($precioKitTierra) . " (incluye varilla y terminal)\n" .
                "🔌 *Cable eléctrico calibre 12 (3 hilos):* \$" . number_format($precioCable12) . " x metro uso rudo 100% cobre\n" .
                "📦 *Centro de Carga 2 Polos:* \$" . number_format($precioCentroCarga) . "\n" .
                "🧱 *Base de pared para condensador:* \$" . number_format($precioBasePared) . " (instalación incluida)\n" .
                "🔧 *Línea excedente de gas 1 Ton:* \$" . number_format($precioLineaGas1) . " (instalada)\n" .
                "🔧 *Línea excedente de gas 2 Ton:* \$" . number_format($precioLineaGas2) . " (instalada)\n\n" .
                "⚠️ *Esto no la compromete a comprar.* Solo en caso de que usted requiera materiales " .
                "y desee comprarlos con nosotros. Aquí desglosamos los precios.\n\n" .
                "Si usted desea comprarlo aparte, es necesario que cuando llegue el técnico usted " .
                "ya los tenga a la mano para dárselos y que empiece la instalación, para agilizarla.\n\n" .
                "Si no estás seguro, puedes comunicarte al *{$telAsesor}* con un asesor.\n\n" .
                "¿Quieres agendar la cita?\n\n" .
                "1️⃣ *Sí, agendar* (el técnico llevará los materiales)\n" .
                "2️⃣ *No, volver al menú*");
            Cache::put($stateKey, 'agendar_precios_materiales', now()->addDay());
        } elseif ($msg === '3') {
            $this->sendReply("📬 Editemos la dirección.\n\nEscribe tu *Código Postal* (5 dígitos):");
            Cache::put($stateKey, 'agendar_cp', now()->addDay());
        } else {
            $this->sendReply("⚠️ Opción no válida. Responde 1️⃣ (confirmar), 2️⃣ (materiales) o 3️⃣ (editar dirección).");
            Cache::put($stateKey, 'agendar_requisitos', now()->addDay());
        }
    }

    protected function handleAgendarPreciosMateriales(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', '0', 'atras', 'atrás'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        if ($msg === '1') {
            Cache::put($stateKey, 'agendar_confirmar', now()->addDay());
            $this->sendReply("✅ ¡Perfecto! El técnico llevará los materiales el día de la visita. " .
                "Si decides comprarlos aparte, recuerda tenerlos a la mano.\n\n" .
                "Solo unas preguntas rápidas para preparar la visita:");
            $this->handleAgendarConfirmar('1', $empresa, $stateKey);
        } else {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->sendReply("👌 No hay problema. Si cambias de opinión, aquí estamos. 🌵");
            $this->mostrarMenu($empresa, $stateKey);
        }
    }

    protected function handleAgendarCp(string $msg, Empresa $empresa, string $stateKey): void
    {
        $cp = trim($this->incomingMessage);
        if (!preg_match('/^\d{5}$/', $cp)) {
            $this->sendReply("⚠️ Código Postal inválido. Debe ser de 5 dígitos (ej: 83000).");
            Cache::put($stateKey, 'agendar_cp', now()->addDay());
            return;
        }

        Cache::put("{$stateKey}_cp", $cp, now()->addDay());

        try {
            $url = "https://api-sepomex.hckdrk.mx/query/info_cp/{$cp}";
            $response = @file_get_contents($url);
            if ($response !== false) {
                $data = json_decode($response, true);
                $municipio = $data['response']['municipio'] ?? '';
                $estado = $data['response']['estado'] ?? '';

                // Validar cobertura: solo Hermosillo, Sonora
                if (!empty($municipio) && mb_strtolower($municipio) !== 'hermosillo') {
                    $this->sendReply("⚠️ Lo sentimos, el código postal *{$cp}* corresponde a *{$municipio}, {$estado}*.\n\n" .
                        "Actualmente solo damos cobertura en *Hermosillo, Sonora*.\n\n" .
                        "Escribe otro *Código Postal* dentro de Hermosillo o *menu* para volver.");
                    Cache::put($stateKey, 'agendar_cp', now()->addDay());
                    return;
                }

                Cache::put("{$stateKey}_municipio", $municipio, now()->addDay());
                Cache::put("{$stateKey}_estado", $estado, now()->addDay());
                if (!empty($data['response']['asentamientos'])) {
                    $colonias = array_map(fn($a) => $a['asentamiento'], $data['response']['asentamientos']);
                    sort($colonias);
                    Cache::put("{$stateKey}_colonias", $colonias, now()->addDay());
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error consultando Sepomex para CP {$cp}: " . $e->getMessage());
        }

        $this->sendReply("📬 Escribe el nombre de tu *calle*:");
        Cache::put($stateKey, 'agendar_calle', now()->addDay());
    }

    protected function handleAgendarCalle(string $msg, Empresa $empresa, string $stateKey): void
    {
        $calle = trim($this->incomingMessage);
        if (strlen($calle) < 2) {
            $this->sendReply("⚠️ Por favor escribe un nombre de *calle* válido.");
            Cache::put($stateKey, 'agendar_calle', now()->addDay());
            return;
        }
        Cache::put("{$stateKey}_calle", $calle, now()->addDay());
        $this->sendReply("📬 Escribe el *número exterior* de tu domicilio (ej: 123, S/N):");
        Cache::put($stateKey, 'agendar_numero', now()->addDay());
    }

    protected function handleAgendarNumero(string $msg, Empresa $empresa, string $stateKey): void
    {
        $numero = trim($this->incomingMessage);
        Cache::put("{$stateKey}_numero", $numero, now()->addDay());
        $this->sendReply("📬 Escribe el nombre de tu *colonia*:");
        Cache::put($stateKey, 'agendar_colonia', now()->addDay());
    }

    protected function handleAgendarColonia(string $msg, Empresa $empresa, string $stateKey): void
    {
        $colonia = trim($this->incomingMessage);
        if (strlen($colonia) < 2) {
            $this->sendReply("⚠️ Por favor escribe un nombre de *colonia* válido.");
            Cache::put($stateKey, 'agendar_colonia', now()->addDay());
            return;
        }
        Cache::put("{$stateKey}_colonia", $colonia, now()->addDay());
        $this->mostrarResumenConfirmacion($empresa, $stateKey);
    }

    private function mostrarResumenConfirmacion(Empresa $empresa, string $stateKey): void
    {
        $tipo = Cache::get("{$stateKey}_tipo", 'Mantenimiento');
        $fecha = Cache::get("{$stateKey}_fecha");
        $hInicio = Cache::get("{$stateKey}_horario_inicio");
        $hFin = Cache::get("{$stateKey}_horario_fin");
        $cp = Cache::get("{$stateKey}_cp", '—');
        $calle = Cache::get("{$stateKey}_calle", '—');
        $numero = Cache::get("{$stateKey}_numero", '—');
        $colonia = Cache::get("{$stateKey}_colonia", '—');

        $esGarantia = $tipo === 'Registro de Garantía';
        $requisitos = $esGarantia ? "" :
            "\n\n🛡️ *Requisitos de instalación (garantía):*\n" .
            "🔌 Térmico dedicado (en 220V usa térmico doble)\n" .
            "⚡ Tierra física individual — NO compartido\n" .
            "📐 Evaporador a 20 cm bajo loza/techo\n" .
            "🔌 Cable calibre 12 mínimo (1 y 2 ton, uso rudo)\n" .
            "📌 Instalación por técnico certificado\n\n" .
            "Sin estos requisitos la garantía *no será válida*.\n\n" .
            "1️⃣ *Sí, cuento con todo — Confirmar cita*\n" .
            "2️⃣ *No, necesito materiales*\n" .
            "3️⃣ *Editar dirección*";

        $opciones = $esGarantia
            ? "1️⃣ *Confirmar cita*\n2️⃣ *Editar dirección*"
            : "";

        $this->sendReply("📋 *Resumen de tu Cita:*\n\n" .
            "• *Servicio:* {$tipo}\n" .
            "• *Fecha:* {$fecha}\n" .
            "• *Horario:* {$hInicio} - {$hFin}\n" .
            "• *Dirección:* {$calle} #{$numero}, Col. {$colonia}, CP {$cp}" .
            ($esGarantia ? "\n\n{$opciones}" : $requisitos) .
            "\n\n✏️ *Atrás* (escribe la palabra)\n\n" .
            "Responde el *número* de tu elección.");

        Cache::put($stateKey, $esGarantia ? 'agendar_confirmar' : 'agendar_requisitos', now()->addDay());
    }

    protected function crearCitaFinal(Empresa $empresa, string $stateKey): void
    {
        $tipo = Cache::get("{$stateKey}_tipo", 'Mantenimiento');
        $fecha = Cache::get("{$stateKey}_fecha");
        $horarioInicio = Cache::get("{$stateKey}_horario_inicio");
        $horarioFin = Cache::get("{$stateKey}_horario_fin");
        $tecnicoId = Cache::get("{$stateKey}_tecnico_id");
        // Para Vircom, si no hay técnico asignado, usar Alan Aranda Esquer (ID 3)
        if (!$tecnicoId && Cache::get("{$stateKey}_vircom_tipo")) {
            $tecnicoId = 3;
        }
        $cp = Cache::get("{$stateKey}_cp");
        $calle = Cache::get("{$stateKey}_calle");
        $numero = Cache::get("{$stateKey}_numero");
        $colonia = Cache::get("{$stateKey}_colonia");
        $isPromoInstalacionGratis = Cache::get("{$stateKey}_promo_instalacion_gratis", false);

        if (!$fecha || !$horarioInicio) {
            $this->sendReply("⚠️ Lo sentimos, la sesión de agendamiento expiró. Por favor intenta de nuevo.");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        $tz = $empresa->timezone ?: config('app.timezone', 'America/Hermosillo');
        $cliente = $this->buscarClientePorWaId();

        // Si el cliente no existe, crearlo con el nombre de WhatsApp y su teléfono
        if (!$cliente) {
            $nombre = Cache::get("{$stateKey}_nombre");
            if (!$nombre || $nombre === 'Cliente WhatsApp') {
                // Intentar obtener el nombre real del chat de WhatsApp
                $lastChat = \App\Models\WhatsAppChat::where('wa_id', $this->waId)
                    ->whereNotNull('from_name')
                    ->latest()
                    ->first();
                $nombre = $lastChat?->from_name ?: $this->waId;
            }
            $cliente = \App\Models\Cliente::create([
                'empresa_id' => $this->empresaId,
                'nombre_razon_social' => $nombre,
                'telefono' => $this->waId,
            ]);
        }

        if (!$cp) $cp = $cliente?->codigo_postal;
        if (!$calle) $calle = $cliente?->calle;
        if (!$numero) $numero = $cliente?->numero_exterior;
        if (!$colonia) $colonia = $cliente?->colonia;

        try {
            DB::beginTransaction();

            // Lock the technician's slots for this date to prevent race conditions
            if ($tecnicoId) {
                $slotInicio = Carbon::parse($fecha . ' ' . $horarioInicio, $tz);
                $slotFin = Carbon::parse($fecha . ' ' . $horarioFin, $tz);
                $duracionSlot = $slotInicio->diffInMinutes($slotFin);

                // Pessimistic lock: any concurrent transaction trying to book this tech on this date will wait
                Cita::where('tecnico_id', (int)$tecnicoId)
                    ->whereDate('fecha_hora', $fecha)
                    ->whereNotIn('estado', [Cita::ESTADO_CANCELADO, 'cancelada', Cita::ESTADO_COMPLETADO])
                    ->lockForUpdate()
                    ->exists();

                if (Cita::hayConflictoHorario((int)$tecnicoId, $slotInicio->toDateTimeString(), null, $duracionSlot)) {
                    DB::rollBack();
                    $this->sendReply("⚠️ El horario seleccionado ya no está disponible. Por favor selecciona otra fecha u horario.");
                    $this->mostrarCalendarioDisponible($empresa, $stateKey);
                    return;
                }
            }

            $fechaHora = Carbon::parse($fecha . ' ' . $horarioInicio, $tz);
            $fechaHoraFin = Carbon::parse($fecha . ' ' . $horarioFin, $tz);

            // Generar folio según el tipo
            $isEcoclimas = Cache::get("{$stateKey}_ecoclimas", false);
            $tiendaStore = Cache::get("{$stateKey}_tienda_store");
            try {
                $folioService = app(\App\Services\Folio\FolioService::class);
                if ($isEcoclimas) {
                    $folio = $folioService->getNextFolio('ecoclimas');
                } elseif ($tiendaStore) {
                    $folio = $folioService->getNextFolio($tiendaStore);
                } else {
                    $folio = 'WA-' . now()->format('d') . strtoupper(substr(uniqid(), -4));
                }
            } catch (\Exception $e) {
                $folio = 'WA-' . now()->format('d') . strtoupper(substr(uniqid(), -4));
            }

            $tempCotizacion = Cache::get("{$stateKey}_temp_cotizacion");
            $tiendaLabel = Cache::get("{$stateKey}_tienda_label");
            $descripcion = $tiendaLabel ? "Agendado desde WhatsApp — Tienda: {$tiendaLabel}" : "Agendado desde WhatsApp";
            $subtotal = 0;
            $total = 0;
            $iva = 0;

            if ($tempCotizacion) {
                $descripcion = ($tiendaLabel ? "[{$tiendaLabel}] " : "") . "Cotización: " . ($tempCotizacion['detalle'] ?? '') . " | Total estimado: $" . number_format($tempCotizacion['total'] ?? 0, 2);
                $total = (float) ($tempCotizacion['total'] ?? 0);
                $subtotal = round($total / 1.16, 2);
                $iva = round($total - $subtotal, 2);
            }

            $costoSubir = (float) (Cache::get("{$stateKey}_survey_costo_subir", 0));
            if ($costoSubir > 0) {
                $total += $costoSubir;
                $subtotal = round($total / 1.16, 2);
                $iva = round($total - $subtotal, 2);
            }

            $plantas = Cache::get("{$stateKey}_survey_plantas");
            $surveyText = null;
            if ($plantas) {
                $equipo = Cache::get("{$stateKey}_survey_equipo", 'No especificado');
                $accesoDomicilio = Cache::get("{$stateKey}_survey_acceso_domicilio", 'No especificado');
                $accesoTrabajo = Cache::get("{$stateKey}_survey_acceso_trabajo", 'No especificado');
                $especial = Cache::get("{$stateKey}_survey_especial", 'No especificado');
                $persona = Cache::get("{$stateKey}_survey_persona", 'No especificado');
                $subirCondensadora = Cache::get("{$stateKey}_survey_subir_condensadora");

                $surveyText = "📋 *Levantamiento de Información (WhatsApp):*\n" .
                    "• Tipo de inmueble: {$plantas}\n" .
                    "• Equipo a retirar: {$equipo}\n" .
                    "• Acceso al domicilio: {$accesoDomicilio}\n" .
                    "• Acceso área de trabajo: {$accesoTrabajo}\n" .
                    "• Situación especial: {$especial}\n" .
                    "• Persona que atiende: {$persona}";
                if ($subirCondensadora) {
                    $surveyText .= "\n• Subida condensadora: {$subirCondensadora}";
                }
            }

            $citaData = [
                'empresa_id' => $this->empresaId,
                'folio' => $folio,
                'cliente_id' => $cliente?->id,
                'tipo_servicio' => $tipo,
                'descripcion' => $descripcion,
                'fecha_hora' => $fechaHora,
                'fecha_hora_fin' => $fechaHoraFin,
                'direccion' => "{$calle} #{$numero}, Col. {$colonia}, CP {$cp}",
                'codigo_postal' => $cp,
                'calle' => $calle,
                'numero_exterior' => $numero,
                'colonia' => $colonia,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'estado' => 'programado',
                'origen' => 'whatsapp',
                'notas' => $surveyText,
                'notas_internas' => $surveyText,
            ];

            $warrantyInstalador = Cache::get("{$stateKey}_warranty_instalador");
            $warrantyProblema = Cache::get("{$stateKey}_warranty_problema");
            if ($warrantyInstalador || $warrantyProblema) {
                $warrantyNotes = "🛡️ *Registro de Garantía (WhatsApp):*\n" .
                    ($warrantyInstalador ? "• Instalado por: {$warrantyInstalador}\n" : "") .
                    ($warrantyProblema ? "• Problema reportado: {$warrantyProblema}" : "");
                $citaData['notas_internas'] = ($surveyText ? $surveyText . "\n\n" : "") . $warrantyNotes;
                $citaData['problema_reportado'] = $warrantyProblema ?: null;
            }

            if ($tecnicoId) {
                $citaData['tecnico_id'] = $tecnicoId;
            }

            $cita = Cita::create($citaData);
            DB::commit();

            // Limpiar caché del flujo de agendamiento
            foreach (['_tipo', '_fecha', '_horario_inicio', '_horario_fin', '_cp', '_calle', '_numero', '_colonia', '_colonias', '_municipio', '_estado', '_dir_confirmada', '_tecnico_id', '_horas', '_citas_map', '_cita_id', '_fechas', '_temp_cotizacion', '_mantenimiento_preventivo_6m', '_promo_instalacion_gratis', '_warranty_instalador', '_warranty_problema'] as $key) {
                Cache::forget("{$stateKey}{$key}");
            }

            $mantenimiento6m = Cache::get("{$stateKey}_mantenimiento_preventivo_6m");
            if ($mantenimiento6m) {
                Cache::forget("{$stateKey}_mantenimiento_preventivo_6m");
            }

            // Nombre para despedida personalizada
            $nombrePersona = Cache::get("{$stateKey}_survey_persona");
            $nombrePrimer = '';
            if ($nombrePersona && !in_array($nombrePersona, ['El cliente', 'Ninguna', 'No especificado'])) {
                $nombrePrimer = explode(' ', trim($nombrePersona))[0];
            } elseif ($cliente) {
                $nombrePrimer = explode(' ', trim($cliente->nombre_razon_social ?? ''))[0];
            }
            $despedida = $nombrePrimer
                ? "\n\n🙌 *Fue un placer atenderle {$nombrePrimer}!* Que tenga un excelente día.\n\n" .
                  "Para su mayor tranquilidad, lo invitamos a conocer los detalles de su instalación aquí:\n" .
                  "🔗 https://climasdeldesierto.com/instalacion-gratis-mirage"
                : '';

            if ($isPromoInstalacionGratis) {
                if ($cliente && $cliente->telefono) {
                    $this->sendReply("✅ *Cita Confirmada*\n\n" .
                        "• *Servicio:* {$tipo}\n" .
                        "• *Fecha:* " . $fechaHora->format('d/m/Y') . "\n" .
                        "• *Hora:* {$horarioInicio} - {$horarioFin}\n" .
                        "• *Dirección:* {$calle} #{$numero}, Col. {$colonia}, CP {$cp}\n" .
                        "• *Folio:* {$cita->folio}\n\n" .
                        "¡Gracias por confiar en nosotros! 🌵\n\n" .
                        "📸 Por favor envíanos por este medio la **foto de tu ticket de compra** para validar tu promoción." .
                        $despedida);
                } else {
                    $this->sendReply("✅ *Cita Confirmada*\n\n" .
                        "• *Servicio:* {$tipo}\n" .
                        "• *Fecha:* " . $fechaHora->format('d/m/Y') . "\n" .
                        "• *Hora:* {$horarioInicio} - {$horarioFin}\n" .
                        "• *Folio:* {$cita->folio}\n\n" .
                        "¡Gracias por confiar en nosotros! 🌵\n\n" .
                        "📸 Por favor envíanos por este medio la **foto de tu ticket de compra** para validar tu promoción." .
                        $despedida);
                }
            } else {
                if ($cliente && $cliente->telefono) {
                    $this->sendReply("✅ *Cita Confirmada!*\n\n" .
                        "• *Folio:* {$cita->folio}\n" .
                        "• *Servicio:* {$tipo}\n" .
                        "• *Fecha:* " . $fechaHora->format('d/m/Y') . "\n" .
                        "• *Horario:* {$horarioInicio} - {$horarioFin}\n" .
                        "• *Dirección:* {$calle} #{$numero}, Col. {$colonia}" .
                        $despedida);
                } else {
                    $this->sendReply("✅ *Cita Confirmada!*\n\n" .
                        "• *Folio:* {$cita->folio}\n" .
                        "• *Servicio:* {$tipo}\n" .
                        "• *Fecha:* " . $fechaHora->format('d/m/Y') . "\n" .
                        "• *Horario:* {$horarioInicio} - {$horarioFin}" .
                        $despedida);
                }
            }

            $this->forgetSurveyCache($stateKey);
            Cache::put($stateKey, 'menu', now()->addDay());
        } catch (\Exception $e) {
            try { DB::rollBack(); } catch (\Exception $rollbackErr) {}
            Log::error("Error al crear cita desde WhatsApp: " . $e->getMessage());
            $this->sendReply("⚠️ Ocurrió un error al crear la cita. No perdiste tus respuestas, intenta de nuevo.");
            Cache::put($stateKey, 'survey_persona', now()->addDay());
        }
    }

    public function handleSurveyFlow(string $state, string $msg, string $stateKey): bool
    {
        if (!str_starts_with($state, 'survey_')) {
            return false;
        }

        $msgClean = trim(strtolower($this->incomingMessage));
        if (in_array($msgClean, ['hola', 'buenas', 'menu', 'menú', 'inicio', 'buenos dias', 'buenas tardes', 'buenas noches', 'recomenzar', 'menu_back', 'cancelar'])) {
            $this->forgetSurveyCache($stateKey);
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu(Empresa::find($this->empresaId), $stateKey);
            return true;
        }

        switch ($state) {
            case 'survey_plantas':
                $val = trim($this->incomingMessage);
                if ($val === '1') {
                    $ans = "Una planta";
                } elseif ($val === '2') {
                    $ans = "Dos plantas";
                } elseif ($val === '3') {
                    $ans = "Es local comercial";
                } else {
                    $ans = preg_replace('/^4[\s\-\.]*/i', '', $val);
                    if (empty($ans)) $ans = $val;
                }
                Cache::put("{$stateKey}_survey_plantas", $ans, now()->addDay());
                $tipo = Cache::get("{$stateKey}_tipo", '');
                $esGarantia = $tipo === 'Registro de Garantía';
                if ($val === '2' && !$esGarantia) {
                    Cache::put($stateKey, 'survey_segundo_piso', now()->addDay());
                    $this->sendReply("🏗️ *Subida de condensadora a segundo nivel*\n\nEl equipo se instalará en segunda planta.\n\n*¿Por qué contratar la subida?*\nIncluye un *seguro*: si la condensadora llega a caerse durante la subida y mientras su instalación, se *repondrá la condensadora sin costo* para usted.\n\n1️⃣ *Sí, contratar subida* — \$150 (incluye seguro)\n2️⃣ *No, queda en planta baja*\n3️⃣ *Yo lo subiré* (sin seguro)");
                } elseif ($esGarantia) {
                    Cache::put($stateKey, 'survey_acceso_domicilio', now()->addDay());
                    $this->sendReply("3️⃣ *¿El acceso a su domicilio es libre o es privada/cerrada?*\n" .
                        "1️⃣ Libre (calle abierta, sin restricciones)\n" .
                        "2️⃣ Cerrada con clave de acceso\n" .
                        "3️⃣ Cerrada (se debe llamar/tocar para que abran)");
                } else {
                    Cache::put($stateKey, 'survey_equipo', now()->addDay());
                    $this->sendReply("2️⃣ *¿Actualmente cuenta con algún equipo que deba retirarse?*\n" .
                        "1️⃣ Sí\n" .
                        "2️⃣ No");
                }
                break;

            case 'survey_segundo_piso':
                $tipo = Cache::get("{$stateKey}_tipo", '');
                if ($tipo === 'Registro de Garantía') {
                    Cache::put($stateKey, 'survey_acceso_domicilio', now()->addDay());
                    $this->sendReply("3️⃣ *¿El acceso a su domicilio es libre o es privada/cerrada?*\n" .
                        "1️⃣ Libre (calle abierta, sin restricciones)\n" .
                        "2️⃣ Cerrada con clave de acceso\n" .
                        "3️⃣ Cerrada (se debe llamar/tocar para que abran)");
                    break;
                }
                $val = trim($this->incomingMessage);
                $valClean = strtolower($val);
                if ($val === '1' || $valClean === 'si' || $valClean === 'sí') {
                    Cache::put("{$stateKey}_survey_subir_condensadora", "Sí (contrató subida con seguro — \$150)", now()->addDay());
                    Cache::put("{$stateKey}_survey_costo_subir", 150, now()->addDay());
                } elseif ($val === '3') {
                    Cache::put("{$stateKey}_survey_subir_condensadora", "El cliente lo subirá por su cuenta (sin seguro)", now()->addDay());
                } else {
                    Cache::put("{$stateKey}_survey_subir_condensadora", "No, queda en planta baja", now()->addDay());
                }
                Cache::put($stateKey, 'survey_equipo', now()->addDay());
                $this->sendReply("2️⃣ *¿Actualmente cuenta con algún equipo que deba retirarse?*\n" .
                    "1️⃣ Sí\n" .
                    "2️⃣ No");
                break;

            case 'survey_equipo':
                $val = trim($this->incomingMessage);
                $valClean = strtolower($val);
                if ($val === '2' || $valClean === 'no') {
                    Cache::put("{$stateKey}_survey_equipo", "No", now()->addDay());
                    Cache::put($stateKey, 'survey_acceso_domicilio', now()->addDay());
                    $this->sendReply("3️⃣ *¿El acceso a su domicilio es libre o es privada/cerrada?*\n" .
                        "1️⃣ Libre (calle abierta, sin restricciones)\n" .
                        "2️⃣ Cerrada con clave de acceso\n" .
                        "3️⃣ Cerrada (se debe llamar/tocar para que abran)");
                } else {
                    Cache::put("{$stateKey}_survey_equipo", "Sí", now()->addDay());
                    Cache::put($stateKey, 'survey_desinstalacion', now()->addDay());
                    $this->sendReply("¿Le gustaría desinstalar el equipo actual con nosotros?\n" .
                        "El costo de desinstalación es de $500 pesos en primera planta o $600 en segunda planta (hasta 2 toneladas, ya incluye bajar condensadora del techo).\n\n" .
                        "1️⃣ Sí, deseo contratar la desinstalación\n" .
                        "2️⃣ No, gracias");
                }
                break;

            case 'survey_desinstalacion':
                $val = trim($this->incomingMessage);
                $valClean = strtolower($val);
                $prev = Cache::get("{$stateKey}_survey_equipo", "Sí");
                if ($val === '1' || $valClean === 'si' || $valClean === 'sí') {
                    $ans = "{$prev} (Desea contratar desinstalación con nosotros: $500/$600)";
                } else {
                    $ans = "{$prev} (No desea contratar desinstalación)";
                }
                Cache::put("{$stateKey}_survey_equipo", $ans, now()->addDay());
                Cache::put($stateKey, 'survey_acceso_domicilio', now()->addDay());
                $this->sendReply("3️⃣ *¿El acceso a su domicilio es libre o es privada/cerrada?*\n" .
                    "1️⃣ Libre (calle abierta, sin restricciones)\n" .
                    "2️⃣ Cerrada con clave de acceso\n" .
                    "3️⃣ Cerrada (se debe llamar/tocar para que abran)");
                break;

            case 'survey_acceso_domicilio':
                $val = trim($this->incomingMessage);
                if ($val === '1') {
                    Cache::put("{$stateKey}_survey_acceso_domicilio", "Libre (sin restricciones)", now()->addDay());
                    Cache::put($stateKey, 'survey_acceso_trabajo', now()->addDay());
                    $this->sendReply("4️⃣ *¿El acceso al área de trabajo es sencillo?*\n" .
                        "1️⃣ Sí, es sencillo (patio libre, pasillo amplio)\n" .
                        "2️⃣ No, es complejo (techo alto, escalera marina, espacio reducido, etc.)");
                } elseif ($val === '2') {
                    Cache::put("{$stateKey}_survey_acceso_domicilio", "Cerrada con clave", now()->addDay());
                    Cache::put($stateKey, 'survey_acceso_clave', now()->addDay());
                    $this->sendReply("🔑 Por favor escribe la *clave de acceso* para ingresar a la privada/cerrada:");
                } elseif ($val === '3') {
                    Cache::put("{$stateKey}_survey_acceso_domicilio", "Cerrada (llamar para acceso)", now()->addDay());
                    Cache::put($stateKey, 'survey_acceso_instrucciones', now()->addDay());
                    $this->sendReply("📝 Por favor escribe las *instrucciones para accesar* (¿a quién llamar?, ¿qué timbre tocar?, etc.):");
                } else {
                    $this->sendReply("⚠️ Opción no válida. Responde 1️⃣, 2️⃣ o 3️⃣.");
                    Cache::put($stateKey, 'survey_acceso_domicilio', now()->addDay());
                }
                break;

            case 'survey_acceso_clave':
                $val = trim($this->incomingMessage);
                if (strlen($val) < 1) {
                    $this->sendReply("⚠️ Por favor escribe la *clave de acceso*.");
                    Cache::put($stateKey, 'survey_acceso_clave', now()->addDay());
                    break;
                }
                $prev = Cache::get("{$stateKey}_survey_acceso_domicilio", "Cerrada con clave");
                Cache::put("{$stateKey}_survey_acceso_domicilio", "{$prev} (Clave: {$val})", now()->addDay());
                Cache::put($stateKey, 'survey_acceso_trabajo', now()->addDay());
                $this->sendReply("4️⃣ *¿El acceso al área de trabajo es sencillo?*\n" .
                    "1️⃣ Sí, es sencillo (patio libre, pasillo amplio)\n" .
                    "2️⃣ No, es complejo (techo alto, escalera marina, espacio reducido, etc.)");
                break;

            case 'survey_acceso_instrucciones':
                $val = trim($this->incomingMessage);
                if (strlen($val) < 2) {
                    $this->sendReply("⚠️ Por favor escribe las *instrucciones de acceso*.");
                    Cache::put($stateKey, 'survey_acceso_instrucciones', now()->addDay());
                    break;
                }
                $prev = Cache::get("{$stateKey}_survey_acceso_domicilio", "Cerrada (llamar para acceso)");
                Cache::put("{$stateKey}_survey_acceso_domicilio", "{$prev} (Instrucciones: {$val})", now()->addDay());
                Cache::put($stateKey, 'survey_acceso_trabajo', now()->addDay());
                $this->sendReply("4️⃣ *¿El acceso al área de trabajo es sencillo?*\n" .
                    "1️⃣ Sí, es sencillo (patio libre, pasillo amplio)\n" .
                    "2️⃣ No, es complejo (techo alto, escalera marina, espacio reducido, etc.)");
                break;

            case 'survey_acceso_trabajo':
                $val = trim($this->incomingMessage);
                $valClean = strtolower($val);
                if ($val === '1' || $valClean === 'si' || $valClean === 'sí' || str_contains($valClean, 'sencillo')) {
                    $ans = "Sí, es sencillo";
                } elseif ($val === '2' || $valClean === 'no' || str_contains($valClean, 'complejo')) {
                    $ans = "No, es complejo";
                } else {
                    $ans = $val;
                }
                Cache::put("{$stateKey}_survey_acceso_trabajo", $ans, now()->addDay());
                Cache::put($stateKey, 'survey_especial', now()->addDay());
                $this->sendReply("5️⃣ *¿Hay alguna situación especial que debamos considerar antes de la visita?* (Ej: mascotas sueltas, horario restringido, zona de difícil estacionamiento, etc.)\n" .
                    "1️⃣ No, ninguna situación especial\n" .
                    "2️⃣ Sí (por favor descríbela)");
                break;

            case 'survey_especial':
                $val = trim($this->incomingMessage);
                $valClean = strtolower($val);
                if ($val === '1' || $valClean === 'no' || str_contains($valClean, 'ninguna') || str_contains($valClean, 'ningun')) {
                    $ans = "Ninguna";
                } else {
                    $ans = preg_replace('/^2[\s\-\.]*/i', '', $val);
                    if (empty($ans)) $ans = $val;
                }
                Cache::put("{$stateKey}_survey_especial", $ans, now()->addDay());
                Cache::put($stateKey, 'survey_persona', now()->addDay());
                $this->sendReply("6️⃣ *¿Quién estará en el domicilio para atender al técnico?*\n" .
                    "Ejemplo: *1 Maricela* (yo mismo) o *2 Juan Pérez* (otra persona)\n\n" .
                    "Responde con el *número* y el *nombre* en un solo mensaje:");
                break;

            case 'survey_persona':
                $val = trim($this->incomingMessage);
                $valClean = strtolower($val);
                $yaTieneNombre = Cache::get("{$stateKey}_survey_persona_nombre");
                if ($yaTieneNombre) {
                    Cache::forget("{$stateKey}_survey_persona_nombre");
                    $empresa = Empresa::find($this->empresaId);
                    $this->crearCitaFinal($empresa, $stateKey);
                    break;
                }
                // Detectar "1 Nombre" o "2 Nombre" en un solo mensaje
                if (preg_match('/^1[\s\-\.\,]+(.+)$/i', $val, $m)) {
                    $nombre = trim($m[1]);
                    Cache::put("{$stateKey}_survey_persona", strlen($nombre) > 1 ? $nombre : 'El cliente', now()->addDay());
                    $empresa = Empresa::find($this->empresaId);
                    $this->crearCitaFinal($empresa, $stateKey);
                } elseif (preg_match('/^2[\s\-\.\,]+(.+)$/i', $val, $m)) {
                    $nombre = trim($m[1]);
                    if (strlen($nombre) > 1) {
                        Cache::put("{$stateKey}_survey_persona", $nombre, now()->addDay());
                        $empresa = Empresa::find($this->empresaId);
                        $this->crearCitaFinal($empresa, $stateKey);
                    } else {
                        Cache::put("{$stateKey}_survey_persona_nombre", true, now()->addDay());
                        $this->sendReply("📝 Por favor escribe el *nombre completo* de la persona que atenderá al técnico:");
                        Cache::put($stateKey, 'survey_persona', now()->addDay());
                    }
                } elseif ($val === '1' || str_contains($valClean, 'yo') || str_contains($valClean, 'si estar')) {
                    Cache::put("{$stateKey}_survey_persona", 'El cliente', now()->addDay());
                    Cache::put("{$stateKey}_survey_persona_nombre", true, now()->addDay());
                    $this->sendReply("📝 Por favor escribe tu *nombre completo* para que el técnico sepa a quién buscar:");
                    Cache::put($stateKey, 'survey_persona', now()->addDay());
                } elseif ($val === '2' || str_starts_with($valClean, '2')) {
                    Cache::put("{$stateKey}_survey_persona_nombre", true, now()->addDay());
                    $this->sendReply("📝 Por favor escribe el *nombre completo* de la persona que atenderá al técnico:");
                    Cache::put($stateKey, 'survey_persona', now()->addDay());
                } else {
                    Cache::put("{$stateKey}_survey_persona", $val, now()->addDay());
                    $empresa = Empresa::find($this->empresaId);
                    $this->crearCitaFinal($empresa, $stateKey);
                }
                break;
        }

        return true;
    }

    public function forgetSurveyCache(string $stateKey): void
    {
        Cache::forget("{$stateKey}_survey_cita_id");
        Cache::forget("{$stateKey}_survey_plantas");
        Cache::forget("{$stateKey}_survey_equipo");
        Cache::forget("{$stateKey}_survey_acceso");
        Cache::forget("{$stateKey}_survey_acceso_domicilio");
        Cache::forget("{$stateKey}_survey_acceso_trabajo");
        Cache::forget("{$stateKey}_survey_especial");
        Cache::forget("{$stateKey}_survey_persona");
        Cache::forget("{$stateKey}_survey_subir_condensadora");
        Cache::forget("{$stateKey}_survey_costo_subir");
    }
}
