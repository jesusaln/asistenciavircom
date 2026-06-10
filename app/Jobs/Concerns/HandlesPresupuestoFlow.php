<?php

namespace App\Jobs\Concerns;

use App\Models\Empresa;
use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait HandlesPresupuestoFlow
{
    protected function handlePresupuesto(string $msg, Empresa $empresa, string $stateKey): void
    {
        Cache::put($stateKey, 'presupuesto_tipo', now()->addDay());
        $this->sendReply("💰 *Presupuesto*\n\n¿Qué tipo de servicio necesitas cotizar?\n\n" .
            "1️⃣ *Instalación* (con equipo incluido)\n" .
            "2️⃣ *Mantenimiento*\n" .
            "3️⃣ *Reparación*\n" .
            "4️⃣ *Solo equipo* (sin instalación)\n\n" .
            "Responde el *número* de la opción.");
        Cache::put($stateKey, 'presupuesto_tipo', now()->addDay());
    }

    protected function handlePresupuestoInstalacion(Empresa $empresa, string $stateKey): void
    {
        Cache::put("{$stateKey}_presupuesto_tipo", 'Instalación', now()->addDay());
        $this->sendReply("📏 ¿De qué *capacidad* es el equipo?\n\n" .
            "1️⃣ *1 Tonelada* (hasta 12m²)\n" .
            "2️⃣ *1.5 Toneladas* (hasta 18m²)\n" .
            "3️⃣ *2 Toneladas* (hasta 25m²)\n" .
            "4️⃣ *3 Toneladas* (hasta 35m²)\n\n" .
            "Responde el *número*.");
        Cache::put($stateKey, 'presupuesto_capacidad', now()->addDay());
    }

    protected function handlePresupuestoMantenimientoDirecto(Empresa $empresa, string $stateKey): void
    {
        Cache::put("{$stateKey}_presupuesto_tipo", 'Mantenimiento', now()->addDay());
        $this->sendReply("📏 ¿De qué *capacidad* es el equipo?\n\n" .
            "1️⃣ *1 Tonelada* (hasta 12m²)\n" .
            "2️⃣ *1.5 Toneladas* (hasta 18m²)\n" .
            "3️⃣ *2 Toneladas* (hasta 25m²)\n" .
            "4️⃣ *3 Toneladas* (hasta 35m²)\n\n" .
            "Responde el *número*.");
        Cache::put($stateKey, 'presupuesto_capacidad', now()->addDay());
    }

    protected function handlePresupuestoSoloEquipo(Empresa $empresa, string $stateKey): void
    {
        Cache::put("{$stateKey}_presupuesto_tipo", 'Solo equipo', now()->addDay());
        $this->buscarEquipos($empresa, $stateKey);
    }

    protected function handlePresupuestoTipo(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', 'atras', 'atrás', '0'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        $servicios = ['1' => 'Instalación', '2' => 'Mantenimiento', '3' => 'Reparación', '4' => 'Solo equipo'];
        $tipo = $servicios[$msg] ?? null;
        if (!$tipo) {
            $this->sendReply("⚠️ Opción no válida. Responde 1, 2, 3 o 4.");
            return;
        }

        Cache::put("{$stateKey}_presupuesto_tipo", $tipo, now()->addDay());

        if (in_array($tipo, ['Instalación', 'Mantenimiento'])) {
            $this->sendReply("📏 ¿De qué *capacidad* es el equipo?\n\n" .
                "1️⃣ *1 Tonelada* (hasta 12m²)\n" .
                "2️⃣ *1.5 Toneladas* (hasta 18m²)\n" .
                "3️⃣ *2 Toneladas* (hasta 25m²)\n" .
                "4️⃣ *3 Toneladas* (hasta 35m²)\n\n" .
                "Responde el *número*.");
            Cache::put($stateKey, 'presupuesto_capacidad', now()->addDay());
        } elseif ($tipo === 'Solo equipo') {
            $this->buscarEquipos($empresa, $stateKey);
        } else {
            $this->sendReply("🔧 Para *Reparación* el costo varía según la falla. Te recomiendo agendar un diagnóstico.\n\n" .
                "1️⃣ *Agendar diagnóstico* (desde \$350)\n" .
                "2️⃣ *Volver al menú*");
            Cache::put("{$stateKey}_presupuesto_tipo", 'Reparación', now()->addDay());
            Cache::put("{$stateKey}_temp_cotizacion", [
                'tipo' => 'Reparación', 'capacidad' => null, 'total' => 350,
                'detalle' => 'Diagnóstico de reparación', 'con_equipo' => false
            ], now()->addDay());
            Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
        }
    }

    protected function handlePresupuestoCapacidad(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', 'atras', 'atrás', '0'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        $tipo = Cache::get("{$stateKey}_presupuesto_tipo", 'Instalación con equipo');
        $capacidades = [
            '1' => '1 Tonelada',
            '2' => '1.5 Toneladas',
            '3' => '2 Toneladas',
            '4' => '3 Toneladas',
            '5' => 'Otra capacidad',
        ];

        if (isset($capacidades[$msg])) {
            Cache::put("{$stateKey}_presupuesto_capacidad", $capacidades[$msg], now()->addDay());

            if ($tipo === 'Instalación con equipo' || $tipo === 'Instalación') {
                Cache::put($stateKey, 'presupuesto_equipo', now()->addDay());
                $this->sendReply("💰 *Presupuesto: Instalación*\n\n" .
                    "¿Quieres incluir el *equipo* en la cotización?\n\n" .
                    "1️⃣ *Sí, buscar equipo*\n2️⃣ *Solo instalación*\n3️⃣ *Volver al menú*");
            } elseif ($tipo === 'Mantenimiento') {
                $capacidad = $capacidades[$msg];
                $preciosMantenimiento = ['1 Tonelada' => 500, '1.5 Toneladas' => 600, '2 Toneladas' => 700, '3 Toneladas' => 850];
                $precio = $preciosMantenimiento[$capacidad] ?? 500;
                $this->sendReply("💰 *Presupuesto: Mantenimiento {$capacidad}*\n\n" .
                    "• *Mano de obra:* \${$precio}\n" .
                    "• *IVA incluido*\n\n" .
                    "📋 *Incluye:*\n" .
                    "• Lavado a presión de evaporador y condensadora\n" .
                    "• Limpieza de drenaje y desinfección\n" .
                    "• Revisión de presiones de gas\n\n" .
                    "1️⃣ *Agendar este servicio*\n2️⃣ *Volver al menú*");
                Cache::put("{$stateKey}_temp_cotizacion", [
                    'tipo' => 'Mantenimiento', 'capacidad' => $capacidad, 'total' => $precio,
                    'detalle' => "Mantenimiento de minisplit de {$capacidad}", 'con_equipo' => false
                ], now()->addDay());
                Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
            } elseif ($tipo === 'Reparación') {
                $capacidad = $capacidades[$msg];
                $this->sendReply("🔧 *Diagnóstico - Reparación {$capacidad}*\n\n" .
                    "• *Costo de diagnóstico:* desde **\$350**\n" .
                    "• *IVA incluido*\n\n" .
                    "📋 *Incluye:*\n" .
                    "• Visita técnica para diagnóstico\n" .
                    "• Revisión completa del equipo\n" .
                    "• Presupuesto de reparación\n\n" .
                    "1️⃣ *Agendar diagnóstico*\n2️⃣ *Volver al menú*");
                Cache::put("{$stateKey}_temp_cotizacion", [
                    'tipo' => 'Reparación', 'capacidad' => $capacidad, 'total' => 350,
                    'detalle' => "Diagnóstico de reparación de minisplit de {$capacidad}", 'con_equipo' => false
                ], now()->addDay());
                Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
            } else {
                $this->buscarEquipos($empresa, $stateKey, $capacidades[$msg]);
            }
        } else {
            $this->sendReply("📏 Por favor escribe la capacidad que buscas (ej: 2 Toneladas):");
            Cache::put($stateKey, 'presupuesto_capacidad', now()->addDay());
        }
    }

    protected function handlePresupuestoEquipo(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', 'atras', 'atrás', '0'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        $tipo = Cache::get("{$stateKey}_presupuesto_tipo", 'Instalación con equipo');
        $capacidad = Cache::get("{$stateKey}_presupuesto_capacidad", '1 Tonelada');

        if ($msg === '2') {
            $precio = config('whatsapp.pricing.instalacion_basica', 1500);
            $this->sendReply("💰 *Presupuesto: Instalación Básica*\n\n" .
                "• *Instalación básica:* **desde \$" . number_format($precio, 0) . "**\n" .
                "• *Total estimado:* desde **\$" . number_format($precio, 0) . "**\n\n" .
                "📌 *Nota:* El costo final puede variar según la capacidad del equipo y accesorios adicionales.\n\n" .
                "1️⃣ *Agendar instalación*\n2️⃣ *Volver al menú*");

            Cache::put("{$stateKey}_temp_cotizacion", [
                'tipo' => 'Instalación', 'capacidad' => $capacidad, 'total' => $precio,
                'detalle' => "Instalación básica de minisplit de {$capacidad}", 'con_equipo' => false
            ], now()->addDay());
            Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
        } elseif ($msg === '3') {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
        } else {
            $this->buscarEquipos($empresa, $stateKey, $capacidad);
        }
    }

    protected function buscarEquipos(Empresa $empresa, string $stateKey, ?string $capacidad = null): void
    {
        $productos = Producto::where('empresa_id', $empresa->id)
            ->where('estado', 'activo');

        if ($capacidad) {
            $capSearch = str_replace(['tonelada', 'toneladas', 'ton', ' ', 'ó'], ['', '', '', '', 'o'], mb_strtolower($capacidad));
            $capSearch = trim(preg_replace('/[^a-z0-9]/i', '', $capSearch));
            if ($capSearch && $capSearch !== 'otracapacidad') {
                $productos->where(function ($q) use ($capSearch) {
                    $q->where('nombre', 'ilike', "%{$capSearch}%")
                      ->orWhere('descripcion', 'ilike', "%{$capSearch}%");
                });
            }
        }

        $equipos = $productos->orderBy('nombre')->limit(10)->get(['id', 'nombre', 'precio_venta', 'incluye_iva']);

        if ($equipos->isEmpty()) {
            $this->sendReply("😕 No encontramos equipos disponibles en este momento.\n\n" .
                "Te recomiendo visitar nuestra tienda en línea:\n🌐 climasdeldesierto.com/tienda\n\n" .
                "1️⃣ *Volver al menú*");
            Cache::put($stateKey, 'menu', now()->addDay());
            return;
        }

        $numEmojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
        $msg = "📦 *Equipos disponibles" . ($capacidad ? " {$capacidad}" : '') . ":*\n\n";
        foreach ($equipos as $idx => $eq) {
            $emoji = $numEmojis[$idx] ?? (($idx + 1) . '.');
            $precio = $eq->precio_con_iva > 0 ? '$' . number_format($eq->precio_con_iva, 0) : 'Consultar';
            $msg .= "{$emoji} *{$eq->nombre}* - {$precio}\n";
        }
        $msg .= "\nResponde el *número* para ver el presupuesto detallado.\nO escribe *menu* para volver.";

        $equiposMap = [];
        foreach ($equipos as $idx => $eq) {
            $equiposMap[$idx + 1] = ['id' => $eq->id, 'nombre' => $eq->nombre, 'precio' => $eq->precio_con_iva];
        }
        Cache::put("{$stateKey}_equipos_map", $equiposMap, now()->addDay());
        Cache::put($stateKey, 'presupuesto_seleccion_equipo', now()->addDay());
        $this->sendReply($msg);
    }

    protected function handlePresupuestoSeleccionEquipo(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', 'atras', 'atrás', '0'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        $equiposMap = Cache::get("{$stateKey}_equipos_map", []);

        if (is_numeric($msg) && isset($equiposMap[(int) $msg])) {
            $eq = $equiposMap[(int) $msg];
            $tipo = Cache::get("{$stateKey}_presupuesto_tipo", 'Instalación');
            $capacidad = Cache::get("{$stateKey}_presupuesto_capacidad", '1 Tonelada');
            $precioEquipo = (float) ($eq['precio'] ?? 0);

            if ($tipo === 'Instalación') {
                $precioInstalacion = config('whatsapp.pricing.instalacion_basica', 1500);
                $total = $precioEquipo + $precioInstalacion;
                $detalle = "Instalación de minisplit {$capacidad} con equipo {$eq['nombre']}";

                $bodyText = "💰 *Presupuesto: Instalación con Equipo*\n\n" .
                    "• *Equipo:* {$eq['nombre']} — **\$" . number_format($precioEquipo, 2) . "**\n" .
                    "• *Instalación básica:* **\$" . number_format($precioInstalacion, 2) . "**\n" .
                    "• *Total estimado:* **\$" . number_format($total, 2) . "** (IVA incluido)\n\n" .
                    "📋 *La instalación básica incluye:*\n" .
                    "• Montaje de evaporadora y condensadora, hasta 3 metros de tubería de cobre y cable interconexión, perforación de muro, vacío de línea y puesta en marcha.\n\n" .
                    "¿Deseas proceder?";
                $buttons = [
                    ['id' => '1', 'title' => 'Agendar servicio'],
                    ['id' => '2', 'title' => 'Hablar con Asesor'],
                    ['id' => 'menu', 'title' => 'Volver al menú']
                ];
                $this->sendInteractiveButtonsReply($bodyText, $buttons);

                Cache::put("{$stateKey}_temp_cotizacion", [
                    'tipo' => 'Instalación',
                    'capacidad' => $capacidad,
                    'total' => $total,
                    'detalle' => $detalle,
                    'con_equipo' => true
                ], now()->addDay());
                Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
            } else {
                $total = $precioEquipo;
                $detalle = "Compra de equipo minisplit {$capacidad}: {$eq['nombre']}";

                $bodyText = "💰 *Presupuesto: Solo Equipo*\n\n" .
                    "• *Equipo:* {$eq['nombre']} — **\$" . number_format($precioEquipo, 2) . "**\n" .
                    "• *Total estimado:* **\$" . number_format($total, 2) . "** (IVA incluido)\n\n" .
                    "¿Deseas proceder?";
                $buttons = [
                    ['id' => '1', 'title' => 'Hablar con Asesor'],
                    ['id' => '2', 'title' => 'Volver al menú']
                ];
                $this->sendInteractiveButtonsReply($bodyText, $buttons);

                Cache::put("{$stateKey}_temp_cotizacion", [
                    'tipo' => 'Solo equipo',
                    'capacidad' => $capacidad,
                    'total' => $total,
                    'detalle' => $detalle,
                    'con_equipo' => false
                ], now()->addDay());
                Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
            }
        } else {
            $this->sendReply("⚠️ Opción no válida. Responde con el *número* del equipo de la lista o escribe *menu* para volver.");
        }
    }

    protected function handlePresupuestoConfirmar(string $msg, Empresa $empresa, string $stateKey): void
    {
        if (in_array(trim(strtolower($msg)), ['menu', 'atras', 'atrás', '0'])) {
            Cache::put($stateKey, 'menu', now()->addDay());
            $this->mostrarMenu($empresa, $stateKey);
            return;
        }

        $tempCotizacion = Cache::get("{$stateKey}_temp_cotizacion");
        $conEquipo = $tempCotizacion['con_equipo'] ?? true;

        if ($msg === '1') {
            $tipo = $tempCotizacion['tipo'] ?? 'Instalación';

            if (in_array($tipo, ['Instalación', 'Mantenimiento', 'Reparación'])) {
                Cache::put("{$stateKey}_tipo", $tipo, now()->addDay());
                $this->mostrarCalendarioDisponible($empresa, $stateKey);
            } else {
                $this->sendReply("📞 Para realizar la compra de tu equipo, un asesor se pondrá en contacto contigo. Escribe *menu* para volver.");
                Cache::put($stateKey, 'menu', now()->addDay());
            }
        } elseif ($msg === '2') {
            if ($conEquipo) {
                $this->conectarAsesor($empresa, $stateKey);
            } else {
                Cache::put($stateKey, 'menu', now()->addDay());
                $this->mostrarMenu($empresa, $stateKey);
            }
        } else {
            $this->sendReply("⚠️ Opción no válida.");
            Cache::put($stateKey, 'presupuesto_confirmar', now()->addDay());
        }
    }
}
