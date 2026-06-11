<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MeliService;
use App\Models\PedidoOnline;
use App\Models\MercadoLibreListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeliWebhookController extends Controller
{
    /**
     * Recibir notificaciones push de MercadoLibre
     *
     * Topics soportados:
     * - orders_v2: Orden creada/actualizada
     * - items: Cambios en listings (moderación, etc)
     * - questions: Preguntas de compradores
     */
    public function handle(Request $request)
    {
        $topic = $request->input('topic');
        $resource = $request->input('resource');
        $userId = $request->input('user_id');
        $attemptNumber = $request->input('attempts', 1);

        Log::info('MeliWebhook: Notificación recibida', [
            'topic' => $topic,
            'resource' => $resource,
            'user_id' => $userId,
            'attempt' => $attemptNumber,
        ]);

        // ML espera respuesta 200 rápida, procesamiento pesado se puede hacer en cola
        try {
            match ($topic) {
                'orders_v2' => $this->handleOrder($resource),
                'items' => $this->handleItem($resource),
                'questions' => $this->handleQuestion($resource),
                default => Log::info("MeliWebhook: Topic no manejado: {$topic}"),
            };
        } catch (\Exception $e) {
            Log::error('MeliWebhook: Error procesando notificación', [
                'topic' => $topic,
                'resource' => $resource,
                'error' => $e->getMessage(),
            ]);
        }

        // Siempre devolver 200 para que ML no reintente
        return response()->json(['received' => true], 200);
    }

    /**
     * Procesar notificación de orden
     */
    protected function handleOrder(string $resource): void
    {
        // $resource viene como "/orders/12345678"
        $orderId = basename($resource);

        // Verificar si ya existe
        if (PedidoOnline::where('meli_order_id', $orderId)->exists()) {
            Log::info("MeliWebhook: Orden ML #{$orderId} ya existe localmente.");
            return;
        }

        // Obtener detalle de la orden
        $meli = app(MeliService::class);
        $order = $meli->getOrder($orderId);

        if (isset($order['error'])) {
            Log::error("MeliWebhook: Error obteniendo orden #{$orderId}", $order);
            return;
        }

        $status = $order['status'] ?? '';

        if ($status !== 'paid') {
            Log::info("MeliWebhook: Orden #{$orderId} no está pagada (status: {$status}). Ignorando.");
            return;
        }

        // Despachar al comando de sync para procesamiento
        // Esto evita duplicar lógica y permite que el comando maneje transacciones
        \Artisan::call('meli:sync-orders', ['--hours' => 1]);

        Log::info("MeliWebhook: Orden pagada #{$orderId} detectada, sync-orders ejecutado.");
    }

    /**
     * Procesar notificación de cambio en listing
     */
    protected function handleItem(string $resource): void
    {
        // $resource viene como "/items/MLM123456789"
        $listingId = basename($resource);

        $meli = app(MeliService::class);
        $item = $meli->getItem($listingId);

        if (isset($item['error'])) {
            Log::error("MeliWebhook: Error obteniendo item {$listingId}", $item);
            return;
        }

        $status = $item['status'] ?? 'unknown';
        $listing = MercadoLibreListing::where('listing_id', $listingId)->first();

        if ($listing) {
            // Actualizar estado local si ML lo cambió (ej: moderación lo pausó)
            if ($listing->status !== $status) {
                $oldStatus = $listing->status;
                $listing->update([
                    'status' => $status,
                    'last_sync_at' => now(),
                ]);

                Log::info("MeliWebhook: Listing {$listingId} cambió de {$oldStatus} a {$status}");
            }
        }
    }

    /**
     * Procesar notificación de pregunta
     */
    protected function handleQuestion(string $resource): void
    {
        // $resource viene como "/questions/12345678"
        $questionId = basename($resource);

        // Por ahora solo hacemos log. En el futuro se puede:
        // - Enviar email/WhatsApp al admin
        // - Responder automáticamente con IA
        // - Crear un ticket en el sistema

        $meli = app(MeliService::class);
        $question = $meli->get("/questions/{$questionId}");

        if (isset($question['error'])) {
            Log::error("MeliWebhook: Error obteniendo pregunta #{$questionId}", $question);
            return;
        }

        Log::info("MeliWebhook: Nueva pregunta en ML", [
            'question_id' => $questionId,
            'item_id' => $question['item_id'] ?? null,
            'text' => $question['text'] ?? '',
            'status' => $question['status'] ?? '',
        ]);
    }
}
