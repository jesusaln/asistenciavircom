<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\WhatsAppChat;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CrmProspecto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\WhatsAppConfigService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppQuickResponse;

class WhatsAppChatController extends Controller
{
    public function index()
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            abort(403, 'No se pudo determinar la empresa.');
        }

        $empresa = \App\Models\Empresa::find($empresaId);

        $chats = WhatsAppConversation::query()
            ->where('empresa_id', $empresaId)
            ->with('assignedAgent:id,name')
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();

        // Unificar por mismo número (wa_id canónico): evita dos filas "JESUS LOPEZ" por formatos distintos en BD
        $chats = $chats->groupBy(function ($chat) {
            try {
                return WhatsAppService::canonicalWaId((string) $chat->wa_id);
            } catch (\Throwable $e) {
                return (string) $chat->wa_id;
            }
        })->map(function (Collection $group) use ($empresaId) {
            $sorted = $group->sortByDesc(function ($c) {
                return $c->last_message_at ? $c->last_message_at->getTimestamp() : 0;
            });
            /** @var WhatsAppConversation $primary */
            $primary = $sorted->first();
            $canonical = WhatsAppService::canonicalWaId((string) $primary->wa_id);
            $primary->setAttribute('wa_id', $canonical);

            $waIds = $group->pluck('wa_id')->unique()->values()->all();
            $telLimpio = strlen($canonical) >= 10 ? substr($canonical, -10) : $canonical;

            $cliente = Cliente::where('telefono', 'like', "%$telLimpio%")->first();
            if ($cliente) {
                $primary->from_name = $cliente->nombre_razon_social;
            } else {
                $prospecto = CrmProspecto::where('telefono', 'like', "%$telLimpio%")->first();
                if ($prospecto) {
                    $primary->from_name = $prospecto->nombre;
                }
            }

            $lastMsg = WhatsAppChat::where('empresa_id', $empresaId)
                ->whereIn('wa_id', $waIds)
                ->where('is_internal', false)
                ->orderByDesc('created_at')
                ->first();

            $primary->last_message = $lastMsg?->body;
            $primary->direction = $lastMsg?->direction;
            $primary->message_status = $lastMsg?->status;

            return $primary;
        })->values();

        if (request()->expectsJson()) {
            return response()->json($chats);
        }

        $pendingCotizacion = null;
        if (request()->filled('cotizacion')) {
            $cot = Cotizacion::query()
                ->where('empresa_id', $empresaId)
                ->whereKey((int) request()->query('cotizacion'))
                ->first();
            if ($cot && request()->user()->can('view', $cot)) {
                $cot->loadMissing('cliente');
                $tel = $cot->cliente?->telefono;
                $telDigits = $tel ? preg_replace('/\D/', '', (string) $tel) : '';
                $pendingCotizacion = [
                    'id' => $cot->id,
                    'numero_cotizacion' => $cot->numero_cotizacion,
                    'total' => $cot->total,
                    'cliente_nombre' => $cot->cliente?->nombre_razon_social,
                    'telefono_ok' => strlen($telDigits) >= 10,
                    'pdf_ready' => (bool) $cot->sharing_token,
                ];
            }
        }

        return Inertia::render('Marketing/WhatsAppInbox', [
            'initialChats' => $chats,
            'agents' => \App\Models\User::query()
                ->where('empresa_id', $empresaId)
                ->role(['admin', 'ventas'])
                ->get(['id', 'name']),
            'quickResponses' => WhatsAppQuickResponse::query()
                ->where('empresa_id', $empresaId)
                ->get(),
            'chatbotConfig' => [
                'enabled' => (bool) ($empresa?->whatsapp_chatbot_enabled ?? false),
                'mode' => (string) ($empresa?->whatsapp_chatbot_mode ?? 'off'),
            ],
            'pendingCotizacion' => $pendingCotizacion,
        ]);

    }

    public function getMessages($waId)
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }

        $waIds = $this->waIdsForSameCanonicalContact($empresaId, $waId);
        $messages = WhatsAppChat::where('empresa_id', $empresaId)
            ->whereIn('wa_id', $waIds)
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function assignAgent(Request $request, $waId)
    {
        $request->validate(['agent_id' => 'nullable|exists:users,id']);

        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }

        $conv = $this->findConversationByCanonical($empresaId, $waId);
        $conv->update(['assigned_to' => $request->agent_id]);
        
        return response()->json(['success' => true]);
    }

    public function toggleStatus(Request $request, $waId)
    {
        $request->validate(['status' => 'required|in:open,closed,archived']);

        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }

        $conv = $this->findConversationByCanonical($empresaId, $waId);
        $conv->update(['status' => $request->status]);
        
        return response()->json(['success' => true]);
    }

    public function sendInternalNote(Request $request)
    {
        $request->validate([
            'to' => 'required',
            'body' => 'required',
        ]);

        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }

        $waId = WhatsAppService::canonicalWaId((string) $request->to);
        
        $message = WhatsAppChat::create([
            'empresa_id' => $empresaId,
            'user_id' => auth()->id(),
            'wa_id' => $waId,
            'body' => $request->body,
            'direction' => 'outbound',
            'type' => 'text',
            'is_internal' => true,
            'message_id' => 'internal_' . time() . '_' . rand(1000, 9999),
            'estado' => 'read',
            'received_at' => now(),
        ]);

        event(new \App\Events\WhatsAppMessageReceived($message));

        return response()->json($message);
    }

    public function updateConversation(Request $request, $waId)
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }

        $conv = $this->findConversationByCanonical($empresaId, $waId);
        $conv->update($request->only(['tags', 'status', 'assigned_to']));
        return response()->json($conv);
    }

    public function getContactContext($waId)
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (! $empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }

        $canonical = WhatsAppService::canonicalWaId((string) $waId);
        $telLimpio = strlen($canonical) >= 10 ? substr($canonical, -10) : $canonical;

        $cliente = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->where('telefono', 'like', '%'.$telLimpio.'%')
            ->first();

        if (! $cliente) {
            return response()->json([
                'ventas' => [],
                'servicios' => [],
                'cotizaciones' => [],
            ]);
        }

        $ventas = \App\Models\Venta::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->orderByDesc('fecha')
            ->limit(5)
            ->get(['id', 'numero_venta', 'total', 'fecha', 'estado', 'sharing_token'])
            ->map(function (\App\Models\Venta $v) {
                $token = $v->sharing_token;

                return [
                    'id' => $v->id,
                    'folio' => $v->numero_venta,
                    'total' => $v->total,
                    'fecha' => $v->fecha,
                    'status' => $v->estado instanceof \BackedEnum ? $v->estado->value : (string) $v->estado,
                    'sharing_token' => $token,
                    'pdf_url' => $token ? url('/share/venta/'.$token.'/pdf') : null,
                ];
            })
            ->values();

        $cotizaciones = \App\Models\Cotizacion::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->orderByDesc('fecha_cotizacion')
            ->limit(5)
            ->get(['id', 'numero_cotizacion', 'total', 'fecha_cotizacion', 'estado', 'sharing_token'])
            ->map(function (\App\Models\Cotizacion $c) {
                $token = $c->sharing_token;
                $estado = $c->estado;

                return [
                    'id' => $c->id,
                    'numero_cotizacion' => $c->numero_cotizacion,
                    'total' => $c->total,
                    'fecha' => $c->fecha_cotizacion,
                    'estado' => $estado instanceof \BackedEnum ? $estado->value : (string) $estado,
                    'sharing_token' => $token,
                    'pdf_url' => $token ? url('/share/cotizacion/'.$token.'/pdf') : null,
                ];
            })
            ->values();

        $servicios = \App\Models\Cita::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->orderByDesc('fecha_hora')
            ->limit(5)
            ->get(['id', 'folio', 'fecha_hora as fecha', 'estado', 'tipo_servicio']);

        return response()->json([
            'ventas' => $ventas,
            'cotizaciones' => $cotizaciones,
            'servicios' => $servicios,
        ]);
    }

    public function uploadAndSendMedia(Request $request)
    {
        $request->validate([
            'to' => 'required',
            'file' => 'required|file|max:10240', // 10MB
            'caption' => 'nullable|string',
        ]);

        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }
        $empresa = \App\Models\Empresa::find($empresaId);

        if (!$empresa || !$empresa->whatsapp_enabled) {
            return response()->json(['error' => 'WhatsApp no está habilitado'], 400);
        }

        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $type = explode('/', $mimeType)[0] === 'image' ? 'image' : 'document';
        
        // WhatsApp solo acepta ciertos mimes. Documentos permitidos: pdf, docx, etc.
        if ($type !== 'image' && $mimeType !== 'application/pdf') {
            return response()->json(['error' => 'Tipo de archivo no soportado (solo imágenes y PDF)'], 400);
        }

        try {
            $whatsappService = WhatsAppService::fromEmpresa($empresa);
            
            // 1. Subir a Meta
            $mediaId = $whatsappService->uploadMedia($file->getRealPath(), $mimeType);
            
            // 2. Enviar por WhatsApp
            $response = $whatsappService->sendMediaById($request->to, $mediaId, $type, $request->caption);
            
            $messageId = $response['messages'][0]['id'] ?? 'out_' . time();
            
            // 3. Registrar en BD
            $body = $type === 'image' ? '🖼️ [Imagen]' : '📄 [Documento]';
            if ($request->caption) $body .= ': ' . $request->caption;

            $waId = WhatsAppService::canonicalWaId((string) $request->to);

            $message = WhatsAppChat::create([
                'empresa_id' => $empresaId,
                'user_id' => auth()->id(),
                'wa_id' => $waId,
                'body' => $body,
                'direction' => 'outbound',
                'type' => $type,
                'message_id' => $messageId,
                'status' => 'sent',
                'metadata' => ['media_id' => $mediaId, 'mime_type' => $mimeType, 'type' => $type],
                'received_at' => now(),
            ]);

            // ACTUALIZAR CONVERSACIÓN
            WhatsAppConversation::updateOrCreate(
                ['empresa_id' => $empresaId, 'wa_id' => $waId],
                [
                    'last_message_at' => now(),
                    'status' => 'open'
                ]
            );

            event(new \App\Events\WhatsAppMessageReceived($message));

            return response()->json($message);
        } catch (\Exception $e) {
            \Log::error("WHATSAPP UPLOAD Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAISuggestion($waId)
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json(['suggestion' => 'No se pudo determinar la empresa.'], 403);
        }

        $waIds = $this->waIdsForSameCanonicalContact($empresaId, $waId);
        $messages = WhatsAppChat::where('empresa_id', $empresaId)
            ->whereIn('wa_id', $waIds)
            ->where('is_internal', false)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->reverse();

        if ($messages->isEmpty()) {
            return response()->json(['suggestion' => 'Hola, ¿en qué puedo ayudarte?']);
        }

        $context = [];
        $lastMessage = $messages->last()->body;
        
        // Preparar historial para el bot
        $history = $messages->map(function($msg) {
            return [
                'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                'content' => $msg->body
            ];
        })->toArray();

        try {
            $bot = new \App\Services\AI\VircomBotService();
            // Necesitamos un método en VircomBotService que acepte historial directo o simular sesión
            $sessionId = "wa_suggest_{$waId}";
            \Illuminate\Support\Facades\Cache::put("chatbot_history_{$sessionId}", $history, now()->addMinutes(10));
            
            $response = $bot->getResponse($lastMessage, $sessionId);
            
            return response()->json(['suggestion' => $response['message']]);
        } catch (\Exception $e) {
            \Log::error("AI Suggestion Error: " . $e->getMessage());
            return response()->json(['suggestion' => 'No se pudo generar una sugerencia en este momento.']);
        }
    }

    public function getAudio($messageId)
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            abort(403, 'No se pudo determinar la empresa.');
        }

        $chat = WhatsAppChat::where('empresa_id', $empresaId)->where('message_id', $messageId)->firstOrFail();
        $meta = $chat->metadata ?? [];
        $audioId = $meta['audio']['id'] ?? null;

        if (! $audioId) {
            abort(404, 'Sin referencia de audio en el mensaje.');
        }

        $empresa = Empresa::find($chat->empresa_id);
        $token = WhatsAppConfigService::resolveGraphAccessToken($empresa);
        if (! $token) {
            abort(503, 'WhatsApp no configurado.');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(45)
                ->get("https://graph.facebook.com/v20.0/{$audioId}");
        } catch (\Throwable $e) {
            \Log::channel('whatsapp')->error('WhatsApp getAudio Graph', ['e' => $e->getMessage(), 'audioId' => $audioId]);

            abort(502, 'No se pudo contactar a Meta.');
        }

        if (! $response->successful()) {
            \Log::channel('whatsapp')->warning('WhatsApp getAudio Graph HTTP', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            abort($response->status() === 404 ? 404 : 502, 'Audio no disponible o expirado en Meta.');
        }

        $url = $response->json()['url'] ?? null;
        if (! $url) {
            abort(404, 'Sin URL de descarga de audio.');
        }

        try {
            $audioData = Http::withToken($token)->timeout(60)->get($url);
        } catch (\Throwable $e) {
            \Log::channel('whatsapp')->error('WhatsApp getAudio download', ['e' => $e->getMessage()]);

            abort(502, 'No se pudo descargar el audio.');
        }

        if (! $audioData->successful()) {
            abort(502, 'Descarga de audio fallida.');
        }

        return response($audioData->body())
            ->header('Content-Type', 'audio/ogg');
    }

    public function getImage($messageId)
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (!$empresaId) {
            abort(403, 'No se pudo determinar la empresa.');
        }

        $chat = WhatsAppChat::where('empresa_id', $empresaId)->where('message_id', $messageId)->firstOrFail();
        $meta = $chat->metadata ?? [];

        // Si es outbound y tenemos la URL guardada (sticker/image enviada), la usamos directamente
        if ($chat->direction === 'outbound' && isset($meta['url'])) {
            return redirect($meta['url']);
        }

        // Entrante: imagen, sticker o documento (PDF); la plantilla usa la misma ruta para "Ver documento"
        $imageId = $meta['image']['id'] ?? $meta['sticker']['id'] ?? $meta['document']['id'] ?? null;

        if (! $imageId) {
            abort(404, 'Sin referencia de medio en el mensaje.');
        }

        $empresa = Empresa::find($chat->empresa_id);
        $token = WhatsAppConfigService::resolveGraphAccessToken($empresa);
        if (! $token) {
            abort(503, 'WhatsApp no configurado.');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(45)
                ->get("https://graph.facebook.com/v20.0/{$imageId}");
        } catch (\Throwable $e) {
            \Log::channel('whatsapp')->error('WhatsApp getImage Graph', ['e' => $e->getMessage(), 'mediaId' => $imageId]);

            abort(502, 'No se pudo contactar a Meta.');
        }

        if (! $response->successful()) {
            \Log::channel('whatsapp')->warning('WhatsApp getImage Graph HTTP', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            abort($response->status() === 404 ? 404 : 502, 'Medio no disponible o expirado en Meta.');
        }

        $url = $response->json()['url'] ?? null;
        if (! $url) {
            abort(404, 'Sin URL de descarga.');
        }

        try {
            $imageData = Http::withToken($token)->timeout(60)->get($url);
        } catch (\Throwable $e) {
            \Log::channel('whatsapp')->error('WhatsApp getImage download', ['e' => $e->getMessage()]);

            abort(502, 'No se pudo descargar el medio.');
        }

        if (! $imageData->successful()) {
            abort(502, 'Descarga del medio fallida.');
        }

        $mimeType = $response->json()['mime_type'] ?? 'image/jpeg';

        return response($imageData->body())
            ->header('Content-Type', $mimeType);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'to' => 'required',
            'type' => 'nullable|in:text,image,sticker',
            'body' => 'required_if:type,text',
            'url' => 'required_if:type,image,sticker',
        ]);

        $type = $request->input('type', 'text');
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        
        if (!$empresaId) {
            return response()->json(['error' => 'No se pudo determinar la empresa'], 403);
        }

        $empresa = \App\Models\Empresa::find($empresaId);

        if (!$empresa || !$empresa->whatsapp_enabled) {
            return response()->json(['error' => 'WhatsApp no está habilitado'], 400);
        }

        $messageId = 'out_' . time() . '_' . rand(1000, 9999);
        $status = 'sent';
        $body = $request->body;
        $metadata = [];

        if ($type !== 'text') {
            $metadata['url'] = $request->url;
        }

        try {
            $whatsappService = WhatsAppService::fromEmpresa($empresa);
            
            if ($type === 'text') {
                $response = $whatsappService->sendTextMessage($request->to, $request->body);
            } elseif ($type === 'image') {
                $response = $whatsappService->sendImage($request->to, $request->url, $request->body);
                $body = "🖼️ [Imagen]";
            } elseif ($type === 'sticker') {
                $response = $whatsappService->sendSticker($request->to, $request->url);
                $body = "🏷️ [Sticker]";
            }
            
            if (isset($response['messages'][0]['id'])) {
                $messageId = $response['messages'][0]['id'];
            }
        } catch (\Exception $e) {
            \Log::error("WHATSAPP SEND Error: " . $e->getMessage());
            $status = 'failed';
        }

        $waId = WhatsAppService::canonicalWaId((string) $request->to);

        $message = WhatsAppChat::create([
            'empresa_id' => $empresaId,
            'user_id' => auth()->id(),
            'wa_id' => $waId,
            'body' => $body,
            'direction' => 'outbound',
            'type' => $type,
            'message_id' => $messageId,
            'status' => $status,
            'metadata' => $metadata,
            'received_at' => now(),
        ]);

        // ACTUALIZAR CONVERSACIÓN
        WhatsAppConversation::updateOrCreate(
            ['empresa_id' => $empresaId, 'wa_id' => $waId],
            [
                'last_message_at' => now(),
                'status' => 'open'
            ]
        );

        event(new \App\Events\WhatsAppMessageReceived($message));

        return response()->json($message);
    }

    /**
     * Envía el enlace al PDF de la cotización por WhatsApp Business API (misma vía que el Inbox),
     * sin abrir WhatsApp Web. Requiere permiso ver cotizaciones y WhatsApp habilitado en la empresa.
     */
    public function sendCotizacionPdfLink(Request $request, Cotizacion $cotizacion)
    {
        $this->authorize('view', $cotizacion);

        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        if (! $empresaId || (int) $cotizacion->empresa_id !== (int) $empresaId) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $cotizacion->loadMissing('cliente');
        if (! $cotizacion->cliente?->telefono) {
            return response()->json(['message' => 'El cliente no tiene teléfono registrado'], 422);
        }

        if (empty($cotizacion->sharing_token)) {
            return response()->json(['message' => 'La cotización no tiene enlace público (token). Guarde de nuevo o contacte soporte.'], 422);
        }

        $urlPdf = url('/share/cotizacion/'.$cotizacion->sharing_token.'/pdf');
        $ref = $cotizacion->numero_cotizacion ?: ('#'.$cotizacion->id);
        $nombre = $cotizacion->cliente->nombre_razon_social ?? 'Cliente';
        $totalFmt = number_format((float) $cotizacion->total, 2, '.', ',');

        $body = "Hola {$nombre}, le envío su cotización {$ref}. Total: \${$totalFmt} MXN\n\n".
            "Puede ver o descargar el PDF aquí:\n{$urlPdf}";

        $dup = $request->duplicate(null, [
            'to' => $cotizacion->cliente->telefono,
            'body' => $body,
            'type' => 'text',
        ]);

        return $this->sendMessage($dup);
    }

    /**
     * wa_id que comparten el mismo número (Meta vs CRM) sin escanear toda la tabla de mensajes.
     *
     * @return array<int, string>
     */
    private function waIdsForSameCanonicalContact(int $empresaId, string $waId): array
    {
        $target = WhatsAppService::canonicalWaId((string) $waId);

        $ids = WhatsAppConversation::query()
            ->where('empresa_id', $empresaId)
            ->pluck('wa_id')
            ->push($waId)
            ->unique()
            ->filter(function ($id) use ($target) {
                try {
                    return WhatsAppService::canonicalWaId((string) $id) === $target;
                } catch (\Throwable $e) {
                    return false;
                }
            });

        // Mensajes huérfanos (solo whats_app_chats): acotado por últimos 10 dígitos
        if (strlen($target) >= 10) {
            $suffix = substr($target, -10);
            $extra = WhatsAppChat::query()
                ->where('empresa_id', $empresaId)
                ->where('wa_id', 'like', '%'.$suffix)
                ->select('wa_id')
                ->groupBy('wa_id')
                ->limit(40)
                ->pluck('wa_id')
                ->filter(function ($id) use ($target) {
                    try {
                        return WhatsAppService::canonicalWaId((string) $id) === $target;
                    } catch (\Throwable $e) {
                        return false;
                    }
                });
            $ids = $ids->merge($extra)->unique()->values();
        }

        return $ids->all();
    }

    private function findConversationByCanonical(int $empresaId, string $waId): WhatsAppConversation
    {
        $target = WhatsAppService::canonicalWaId((string) $waId);

        $direct = WhatsAppConversation::query()
            ->where('empresa_id', $empresaId)
            ->where('wa_id', $target)
            ->first();
        if ($direct) {
            return $direct;
        }

        if (strlen($target) >= 10) {
            $suffix = substr($target, -10);
            $match = WhatsAppConversation::query()
                ->where('empresa_id', $empresaId)
                ->where('wa_id', 'like', '%'.$suffix)
                ->limit(25)
                ->get()
                ->first(function ($c) use ($target) {
                    try {
                        return WhatsAppService::canonicalWaId((string) $c->wa_id) === $target;
                    } catch (\Throwable $e) {
                        return false;
                    }
                });
            if ($match) {
                return $match;
            }
        }

    }

    public function toggleChatbot(Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? \App\Support\EmpresaResolver::resolveId();
        $empresa = \App\Models\Empresa::find($empresaId);
        
        if (!$empresa) return response()->json(['error' => 'Empresa no encontrada'], 404);

        $empresa->whatsapp_chatbot_enabled = !$empresa->whatsapp_chatbot_enabled;
        
        // Si no tiene prompt, ponerle el de OpenClaw por defecto
        if (empty($empresa->whatsapp_chatbot_prompt)) {
            $empresa->whatsapp_chatbot_prompt = "Eres OpenClaw 🦞, el asistente inteligente de Climas del Desierto. Contesta de forma rápida, eficiente y profesional.";
        }
        
        $empresa->save();

        return response()->json([
            'enabled' => (bool)$empresa->whatsapp_chatbot_enabled,
            'mode' => $empresa->whatsapp_chatbot_mode
        ]);
    }
}
