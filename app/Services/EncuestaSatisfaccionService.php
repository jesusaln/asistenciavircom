<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EncuestaSatisfaccion;
use App\Models\WhatsAppChat;
use App\Models\WhatsAppConversation;
use App\Support\Brand;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EncuestaSatisfaccionService
{
    public const DELAY_ENVIO_HORAS = 24;
    public const HORAS_EXPIRACION_SIN_RESPUESTA = 72;
    public const INTENTOS_MAX_ENVIO = 3;

    /**
     * Define las 5 preguntas de la encuesta. Cada una es una función
     * que devuelve el mensaje y opciones para el cliente.
     *
     * Preguntas:
     *  1. Satisfacción general (1-5)
     *  2. Puntualidad del técnico (1-5)
     *  3. NPS - ¿Recomendaría? (1-10)
     *  4. Claridad de explicación (1-5)
     *  5. Comentario libre (texto)
     */
    public static function preguntas(): array
    {
        return [
            1 => [
                'slug' => 'p1_satisfaccion',
                'titulo' => 'Pregunta 1 de 2',
                'prompt' => "📊 *¿Cómo calificarías tu satisfacción general con la instalación?*\n\nCalifica del 1 al 5, donde 5 es excelente y 1 es muy malo.",
                'tipo' => 'rating_1_5',
                'requerido' => true,
            ],
            2 => [
                'slug' => 'p2_comentario',
                'titulo' => 'Pregunta 2 de 2',
                'prompt' => "✍️ *¿Tienes alguna queja, sugerencia o felicitación?*\n\nTu opinión — buena o mala — nos ayuda a mejorar. Si no deseas compartir nada, responde *no*.",
                'tipo' => 'texto_libre',
                'requerido' => false,
            ],
        ];
    }

    /**
     * Crea una encuesta pendiente para una cita de instalación recién completada.
     * Idempotente: si ya existe una encuesta pendiente para la cita, devuelve la existente.
     */
    public function crearParaCita(Cita $cita): ?EncuestaSatisfaccion
    {
        if ($cita->tipo_servicio !== 'instalacion') {
            return null;
        }

        $existente = EncuestaSatisfaccion::where('cita_id', $cita->id)->first();
        if ($existente) {
            return $existente;
        }

        $cliente = $cita->cliente;
        $waId = $this->resolverWaIdCliente($cliente);

        if (! $waId) {
            Log::warning('EncuestaSatisfaccionService: cita sin wa_id resuelto, no se crea encuesta', [
                'cita_id' => $cita->id,
                'cliente_id' => $cliente?->id,
            ]);
            return null;
        }

        $clienteIdSnapshot = $cliente?->id;
        $empresaId = $cita->empresa_id;

        return DB::transaction(function () use ($cita, $waId, $cliente, $clienteIdSnapshot, $empresaId) {
            return EncuestaSatisfaccion::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteIdSnapshot,
                'cita_id' => $cita->id,
                'wa_id' => $waId,
                'nombre_cliente_snapshot' => $cliente?->nombre_razon_social,
                'folio' => 'ENC-'.$cita->folio.'-'.strtoupper(Str::random(4)),
                'estado' => EncuestaSatisfaccion::ESTADO_PENDIENTE,
                'pregunta_actual' => 0,
                'descuento_porcentaje' => 10,
                'servicio_aplicable' => 'preventivo',
                'programada_para' => Carbon::now()->addHours(self::DELAY_ENVIO_HORAS),
            ]);
        });
    }

    public function resolverWaIdCliente(?Cliente $cliente): ?string
    {
        if (! $cliente) {
            return null;
        }

        if ($cliente->wa_user_id) {
            return $cliente->wa_user_id;
        }

        if ($cliente->telefono_canonico) {
            return '52'.$cliente->telefono_canonico;
        }

        if ($cliente->telefono) {
            $digits = preg_replace('/\D+/', '', $cliente->telefono);
            if (strlen($digits) >= 10) {
                return '52'.substr($digits, -10);
            }
        }

        return null;
    }

    /**
     * Envía el mensaje inicial (intro + pregunta 1). Se llama desde el Job con delay 24h.
     */
    public function enviarMensajeInicial(EncuestaSatisfaccion $encuesta): bool
    {
        $empresa = Empresa::find($encuesta->empresa_id);
        if (! $empresa || ! $empresa->whatsapp_enabled) {
            $this->marcarFallida($encuesta, 'WhatsApp no habilitado para la empresa');
            return false;
        }

        try {
            $whatsapp = \App\Services\WhatsAppService::fromEmpresa($empresa);

            $cliente = $encuesta->cliente;
            $nombre = $encuesta->nombre_cliente_snapshot
                ?? $cliente?->nombre_razon_social
                ?? 'cliente';
            $primerNombre = explode(' ', trim($nombre))[0];

            $brandName = (string) config('app.name', 'Climas del Desierto');
            $botNombre = Brand::botName();
            $confidencialidad = "🔒 *Confidencialidad:* Tus respuestas son confidenciales y solo se usan para mejorar nuestro servicio. No compartimos información individual con terceros.";

            $intro = "🌵 ¡Hola *{$primerNombre}*! Soy {$botNombre}, el equipo de {$brandName}.\n\n".
                "Hace poco te realizamos la *instalación de tu equipo de climatización* y nos importa mucho saber cómo te fue.\n\n".
                "¿Nos ayudas con 5 preguntitas rápidas? ⏱️ Solo toma 1 minuto y como agradecimiento tienes un *10% de descuento* en tu próximo *mantenimiento preventivo* 🎁\n\n".
                "{$confidencialidad}\n\n".
                "👉 *Empezamos:*";

            $mensaje = $intro . "\n\n" . self::preguntas()[1]['prompt'];

            $botones = [
                ['id' => 'enc_p1_5', 'title' => '⭐⭐⭐⭐⭐ 5'],
                ['id' => 'enc_p1_4', 'title' => '⭐⭐⭐⭐ 4'],
                ['id' => 'enc_p1_3', 'title' => '⭐⭐⭐ 3'],
            ];

            $respuesta = $whatsapp->sendInteractiveButtons(
                $encuesta->wa_id,
                $mensaje,
                $botones,
                '📊 Satisfacción general',
                'Responde 1-5 (siendo 5 excelente)'
            );

            $this->registrarMensajeSalida($empresa, $encuesta->wa_id, '[Botones] '.$mensaje, $respuesta);

            $encuesta->update([
                'estado' => EncuestaSatisfaccion::ESTADO_EN_PROGRESO,
                'pregunta_actual' => 1,
                'enviada_at' => now(),
                'intentos_envio' => $encuesta->intentos_envio + 1,
                'ultimo_error_envio' => null,
            ]);

            $this->cachearEstado($encuesta);

            return true;
        } catch (\Throwable $e) {
            $this->marcarFallida($encuesta, $e->getMessage());
            Log::error('EncuestaSatisfaccionService::enviarMensajeInicial falló', [
                'encuesta_id' => $encuesta->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Procesa una respuesta del cliente. Avanza la state machine.
     *
     * @return array{handled: bool, reply?: string, finalizada?: bool, error?: string}
     */
    public function procesarRespuesta(EncuestaSatisfaccion $encuesta, string $textoUsuario): array
    {
        $preguntaActual = $encuesta->pregunta_actual;

        if ($preguntaActual < 1 || $preguntaActual > EncuestaSatisfaccion::TOTAL_PREGUNTAS) {
            return ['handled' => false];
        }

        $preguntas = self::preguntas();
        $config = $preguntas[$preguntaActual];

        // Detección de intención de salir / no responder
        $msg = mb_strtolower(trim($textoUsuario));
        if (in_array($msg, ['salir', 'cancelar', 'no gracias', 'no quiero', 'omitir', 'mejor no'])) {
            $encuesta->update(['estado' => EncuestaSatisfaccion::ESTADO_CANCELADA]);
            $this->cachearEstado($encuesta);
            return [
                'handled' => true,
                'finalizada' => true,
                'reply' => "👍 Entendido, sin problema. Si en el futuro necesitas servicio, escríbenos cuando quieras. ¡Gracias!",
            ];
        }

        // Validar y guardar respuesta
        $valor = $this->validarYExtraer($config, $textoUsuario);
        if ($valor === null) {
            return [
                'handled' => true,
                'error' => 'invalid',
                'reply' => "🤔 No entendí tu respuesta. " . $config['prompt'],
            ];
        }

        $respuestas = $encuesta->respuestas ?? [];
        $respuestas[$config['slug']] = $valor;
        if (! isset($respuestas['iniciada_en'])) {
            $respuestas['iniciada_en'] = now()->toIso8601String();
        }
        if (! $encuesta->primera_respuesta_at) {
            $encuesta->primera_respuesta_at = now();
        }

        $encuesta->respuestas = $respuestas;
        $siguiente = $preguntaActual + 1;
        $encuesta->pregunta_actual = $siguiente;
        $encuesta->estado = EncuestaSatisfaccion::ESTADO_EN_PROGRESO;

        if ($siguiente > EncuestaSatisfaccion::TOTAL_PREGUNTAS) {
            // Finalizar
            return $this->finalizar($encuesta);
        }

        $saved = $encuesta->save();
        $this->cachearEstado($encuesta);

        Log::info('EncuestaSatisfaccionService::procesarRespuesta: respuesta guardada', [
            'encuesta_id' => $encuesta->id,
            'folio' => $encuesta->folio,
            'pregunta_procesada' => $preguntaActual,
            'slug' => $config['slug'],
            'valor' => $valor,
            'siguiente_pregunta' => $siguiente,
            'saved' => $saved,
            'estado' => $encuesta->estado,
        ]);

        $siguienteConfig = $preguntas[$siguiente];
        $reply = "✅ Anotado.\n\n" . $siguienteConfig['titulo'] . ":\n\n" . $siguienteConfig['prompt'];

        return [
            'handled' => true,
            'reply' => $reply,
            'esRating' => str_starts_with($siguienteConfig['tipo'], 'rating'),
        ];
    }

    protected function validarYExtraer(array $config, string $texto): mixed
    {
        $msg = mb_strtolower(trim($texto));
        $msgLimpio = preg_replace('/[^0-9]/', '', $msg);

        if ($config['tipo'] === 'rating_1_5') {
            // Aceptar: "5", "⭐⭐⭐⭐⭐", "5 estrellas", "excelente", etc.
            $mapping = [
                '1' => 1, 'uno' => 1, 'malo' => 1, 'pesimo' => 1, 'pésimo' => 1, 'muy malo' => 1,
                '2' => 2, 'dos' => 2, 'regular' => 2, 'malo' => 2,
                '3' => 3, 'tres' => 3, 'normal' => 3, 'aceptable' => 3, 'bien' => 3,
                '4' => 4, 'cuatro' => 4, 'muy bien' => 4, 'bueno' => 4,
                '5' => 5, 'cinco' => 5, 'excelente' => 5, 'perfecto' => 5, 'excellent' => 5,
            ];

            if (isset($mapping[$msg])) {
                return $mapping[$msg];
            }
            if ($msgLimpio !== '' && in_array((int) $msgLimpio, [1, 2, 3, 4, 5])) {
                return (int) $msgLimpio;
            }
            // Aceptar "enc_p1_5", "enc_p1_4" etc.
            if (preg_match('/^enc_p\d_(\d)$/', $msg, $m) && in_array((int) $m[1], [1, 2, 3, 4, 5])) {
                return (int) $m[1];
            }
            return null;
        }

        if ($config['tipo'] === 'rating_1_10') {
            $mapping = [
                '10' => 10, 'diez' => 10, 'definitivamente' => 10,
                '9' => 9, 'nueve' => 9,
                '8' => 8, 'ocho' => 8,
                '7' => 7, 'siete' => 7,
                '6' => 6, 'seis' => 6,
                '5' => 5, 'cinco' => 5,
                '4' => 4, 'cuatro' => 4,
                '3' => 3, 'tres' => 3,
                '2' => 2, 'dos' => 2,
                '1' => 1, 'uno' => 1, 'nunca' => 1,
            ];
            if (isset($mapping[$msg])) {
                return $mapping[$msg];
            }
            if ($msgLimpio !== '' && (int) $msgLimpio >= 1 && (int) $msgLimpio <= 10) {
                return (int) $msgLimpio;
            }
            return null;
        }

        if ($config['tipo'] === 'texto_libre') {
            if (in_array($msg, ['no', 'n', 'omitir', 'nada', '-', '.', ''])) {
                return null; // sin comentario
            }
            return mb_substr(trim($texto), 0, 500);
        }

        return null;
    }

    protected function finalizar(EncuestaSatisfaccion $encuesta): array
    {
        $respuestas = $encuesta->respuestas ?? [];

        $calificacion = null;
        $suma = 0;
        $count = 0;
        foreach (['p1_satisfaccion'] as $slug) {
            if (isset($respuestas[$slug]) && is_numeric($respuestas[$slug])) {
                $suma += $respuestas[$slug];
                $count++;
            }
        }
        if ($count > 0) {
            $calificacion = round($suma / $count, 1);
        }

        $nps = $respuestas['p3_nps'] ?? null;

        $codigo = $this->generarCodigoUnico($encuesta->empresa_id);

        $encuesta->fill([
            'estado' => EncuestaSatisfaccion::ESTADO_COMPLETADA,
            'calificacion_global' => $calificacion,
            'nps_score' => $nps,
            'codigo_promocional' => $codigo,
            'codigo_expires_at' => Carbon::now()->addDays(EncuestaSatisfaccion::DIAS_VALIDEZ_CODIGO),
            'completada_at' => now(),
            'pregunta_actual' => EncuestaSatisfaccion::TOTAL_PREGUNTAS + 1,
        ]);
        $encuesta->save();

        $this->cachearEstado($encuesta);

        $this->enviarMensajeCodigo($encuesta);

        return [
            'handled' => true,
            'finalizada' => true,
            'reply' => $this->buildMensajeCodigoCliente($encuesta, false),
        ];
    }

    public function buildMensajeCodigoCliente(EncuestaSatisfaccion $encuesta, bool $recordatorio = false): string
    {
        $empresa = Empresa::find($encuesta->empresa_id);
        $brandName = Brand::name($empresa);
        $primerNombre = explode(' ', trim($encuesta->nombre_cliente_snapshot ?? 'cliente'))[0];

        $calif = $encuesta->calificacion_global;
        $nps = $encuesta->nps_score;

        $intro = $recordatorio
            ? "🌵 ¡Hola *{$primerNombre}*! Soy {$brandName} y quería recordarte tu encuesta pendiente. Sé que tu tiempo vale, por eso solo toma 1 minuto y al final llevas un *10% de descuento* en tu mantenimiento preventivo 🎁"
            : "🌵 ¡Muchas gracias por tus respuestas, *{$primerNombre}*!";

        $resumen = '';
        if ($calif !== null) {
            $resumen .= "⭐ Tu calificación general: *{$calif}/5*\n";
        }
        if ($nps !== null) {
            $resumen .= "📈 Tu recomendación: *{$nps}/10*\n";
        }

        $codigo = $encuesta->codigo_promocional;
        $vencimiento = $encuesta->codigo_expires_at?->format('d/m/Y');

        $mensaje = $intro . "\n\n" .
            ($resumen ? $resumen . "\n" : '') .
            "Como agradecimiento por tu tiempo y confianza, te regalamos este código de descuento 🎁\n\n" .
            "🎟️ *Código:* `{$codigo}`\n" .
            "📅 *Válido hasta:* {$vencimiento}\n" .
            "💰 *Beneficio:* 10% de descuento en tu próximo *mantenimiento preventivo*\n\n" .
            "📌 *¿Cómo usarlo?*\n".
            "1. Cuando te llamemos a programar tu mantenimiento, menciona el código *{$codigo}*.\n".
            "2. O escríbenos por aquí indicando *mantenimiento* y dinos el código *{$codigo}*.\n".
            "3. El descuento se aplica directamente al servicio técnico.\n\n" .
            "¡Gracias por confiar en {$brandName}! 💚\n\n" .
            "🔒 *Confidencialidad:* Tus respuestas solo se usan internamente para mejorar nuestro servicio.";

        return $mensaje;
    }

    protected function enviarMensajeCodigo(EncuestaSatisfaccion $encuesta): void
    {
        try {
            $empresa = Empresa::find($encuesta->empresa_id);
            if (! $empresa) {
                return;
            }
            $whatsapp = \App\Services\WhatsAppService::fromEmpresa($empresa);
            $mensaje = $this->buildMensajeCodigoCliente($encuesta, false);
            $respuesta = $whatsapp->sendTextMessage($encuesta->wa_id, $mensaje);
            $this->registrarMensajeSalida($empresa, $encuesta->wa_id, $mensaje, $respuesta);
        } catch (\Throwable $e) {
            Log::error('EncuestaSatisfaccionService: no se pudo enviar mensaje con código', [
                'encuesta_id' => $encuesta->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function generarCodigoUnico(int $empresaId): string
    {
        for ($i = 0; $i < 5; $i++) {
            $candidato = 'PROMO-'.strtoupper(Str::random(8));
            $existe = EncuestaSatisfaccion::where('codigo_promocional', $candidato)->exists();
            if (! $existe) {
                return $candidato;
            }
        }
        // Fallback: timestamp + random
        return 'PROMO-'.strtoupper(Str::random(4)).'-'.now()->format('His');
    }

    public function cachearEstado(EncuestaSatisfaccion $encuesta): void
    {
        $key = $this->cacheKey($encuesta->empresa_id, $encuesta->wa_id);
        Cache::put($key, [
            'encuesta_id' => $encuesta->id,
            'estado' => $encuesta->estado,
            'pregunta_actual' => $encuesta->pregunta_actual,
        ], now()->addDays(7));
    }

    public function limpiarCacheEstado(int $empresaId, string $waId): void
    {
        Cache::forget($this->cacheKey($empresaId, $waId));
    }

    public function getEncuestaActiva(int $empresaId, string $waId): ?EncuestaSatisfaccion
    {
        // Intentar primero desde BD por confiabilidad multi-proceso
        $encuesta = EncuestaSatisfaccion::where('empresa_id', $empresaId)
            ->where('wa_id', $waId)
            ->whereIn('estado', [
                EncuestaSatisfaccion::ESTADO_PENDIENTE,
                EncuestaSatisfaccion::ESTADO_EN_PROGRESO,
            ])
            ->orderByDesc('id')
            ->first();

        if ($encuesta) {
            return $encuesta;
        }

        // Fallback al cache (por si el listener y el job están en procesos diferentes)
        $cached = Cache::get($this->cacheKey($empresaId, $waId));
        if ($cached && isset($cached['encuesta_id'])) {
            return EncuestaSatisfaccion::find($cached['encuesta_id']);
        }

        return null;
    }

    protected function cacheKey(int $empresaId, string $waId): string
    {
        return "encuesta_activa_{$empresaId}_{$waId}";
    }

    public function marcarFallida(EncuestaSatisfaccion $encuesta, string $error): void
    {
        $encuesta->update([
            'estado' => EncuestaSatisfaccion::ESTADO_FALLIDA_ENVIO,
            'ultimo_error_envio' => mb_substr($error, 0, 1000),
            'intentos_envio' => $encuesta->intentos_envio + 1,
        ]);
    }

    protected function registrarMensajeSalida(Empresa $empresa, string $waId, string $body, array $respuesta): void
    {
        try {
            $messageId = $respuesta['messages'][0]['id'] ?? 'encuesta_'.now()->timestamp;
            WhatsAppChat::create([
                'empresa_id' => $empresa->id,
                'wa_id' => $waId,
                'body' => mb_substr($body, 0, 1000),
                'direction' => 'outbound',
                'type' => 'text',
                'message_id' => $messageId,
                'status' => 'sent',
                'received_at' => now(),
            ]);
            WhatsAppConversation::where('empresa_id', $empresa->id)
                ->where(function ($q) use ($waId) {
                    $q->where('wa_id', $waId)->orWhere('wa_user_id', $waId);
                })
                ->update(['last_message_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('EncuestaSatisfaccionService: no se pudo registrar mensaje salida', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Valida y aplica un código promocional al agendar una cita de mantenimiento preventivo.
     *
     * @return array{success: bool, codigo?: string, descuento_porcentaje?: int, mensaje?: string}
     */
    public function aplicarCodigo(string $codigo, int $empresaId, string $tipoServicio, int $clienteId): array
    {
        $codigo = mb_strtoupper(trim($codigo));
        if (! str_starts_with($codigo, 'PROMO-')) {
            return [
                'success' => false,
                'mensaje' => 'El código no tiene el formato correcto.',
            ];
        }

        $encuesta = EncuestaSatisfaccion::where('empresa_id', $empresaId)
            ->where('codigo_promocional', $codigo)
            ->first();

        if (! $encuesta) {
            return [
                'success' => false,
                'mensaje' => 'Código no encontrado. Verifica que esté bien escrito.',
            ];
        }

        if (! $encuesta->codigoEsValido()) {
            if ($encuesta->codigo_usado) {
                return [
                    'success' => false,
                    'mensaje' => 'Este código ya fue utilizado.',
                ];
            }
            return [
                'success' => false,
                'mensaje' => 'Este código ha expirado (vence el '.$encuesta->codigo_expires_at?->format('d/m/Y').').',
            ];
        }

        // Validar que aplica al servicio: solo mantenimiento preventivo
        $aplicaPorServicio = str_contains($encuesta->servicio_aplicable, 'preventiv');
        $esMantenimiento = str_contains($tipoServicio, 'mantenimiento') || str_contains($tipoServicio, 'preventiv');

        if (! $aplicaPorServicio || ! $esMantenimiento) {
            return [
                'success' => false,
                'mensaje' => 'Este código aplica solo a *mantenimiento preventivo*. Tu cita es de tipo *'.ucfirst($tipoServicio).'*.',
            ];
        }

        return [
            'success' => true,
            'codigo' => $codigo,
            'descuento_porcentaje' => $encuesta->descuento_porcentaje,
            'encuesta_id' => $encuesta->id,
            'mensaje' => "✅ Código aplicado: {$encuesta->descuento_porcentaje}% de descuento en mantenimiento preventivo.",
        ];
    }

    public function marcarCodigoUsado(EncuestaSatisfaccion $encuesta, int $citaId): void
    {
        $encuesta->update([
            'codigo_usado' => true,
            'codigo_usado_at' => now(),
            'codigo_usado_cita_id' => $citaId,
        ]);
    }
}