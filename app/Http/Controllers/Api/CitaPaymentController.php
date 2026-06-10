<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\EmpresaConfiguracion;
use App\Services\VentaFromCitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CitaPaymentController extends Controller
{
    /**
     * Generar link de pago (Preferencia) en MercadoPago para una cita específica.
     */
    public function createPreference(Request $request, $id)
    {
        try {
            $cita = Cita::with(['cliente', 'items.citable'])->findOrFail($id);
            
            if ($cita->estado === Cita::ESTADO_COMPLETADO) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cita ya ha sido completada y pagada.'
                ], 400);
            }

            if ($cita->items->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cita no tiene productos o servicios asignados para cobrar.'
                ], 400);
            }

            // Obtener credenciales de MercadoPago de la configuración de la empresa o env
            $accessToken = config('services.mercadopago.access_token');
            $apiUrl = 'https://api.mercadopago.com';

            // Preparar items para MercadoPago
            $items = [];
            foreach ($cita->items as $item) {
                $items[] = [
                    'title' => $item->citable->nombre ?? $item->citable->descripcion ?? 'Servicio/Producto',
                    'quantity' => (int) $item->cantidad,
                    'unit_price' => (float) $item->precio,
                    'currency_id' => 'MXN',
                ];
            }

            $preferenceData = [
                'items' => $items,
                'back_urls' => [
                    'success' => route('api.citas.pago.exito', ['id' => $cita->id]),
                    'failure' => route('api.citas.pago.error', ['id' => $cita->id]),
                    'pending' => route('api.citas.pago.pendiente', ['id' => $cita->id]),
                ],
                'auto_return' => 'approved',
                'payer' => [
                    'name' => $cita->cliente->nombre_razon_social,
                    'email' => $cita->cliente->email ?? 'cliente@climasdeldesierto.com',
                ],
                'external_reference' => 'CITA_' . $cita->id . '_' . uniqid(),
                'notification_url' => route('api.citas.pago.webhook'),
                'metadata' => [
                    'cita_id' => $cita->id,
                    'tecnico_id' => $cita->tecnico_id,
                ]
            ];

            $response = Http::withToken($accessToken)
                ->post("$apiUrl/checkout/preferences", $preferenceData);

            if ($response->successful()) {
                $preference = $response->json();
                return response()->json([
                    'success' => true,
                    'preference_id' => $preference['id'],
                    'init_point' => $preference['init_point'],
                    'sandbox_init_point' => $preference['sandbox_init_point'],
                ]);
            }

            throw new \Exception('Error al crear preferencia en MercadoPago: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Error en CitaPaymentController@createPreference: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el link de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook para recibir notificaciones de pago de MercadoPago
     */
    public function webhook(Request $request)
    {
        Log::info('MercadoPago Cita Webhook received', $request->all());

        $type = $request->input('type');
        $data = $request->input('data');

        if ($type === 'payment' && isset($data['id'])) {
            try {
                $accessToken = config('services.mercadopago.access_token');
                $apiUrl = 'https://api.mercadopago.com';

                $response = Http::withToken($accessToken)
                    ->get("$apiUrl/v1/payments/{$data['id']}");

                if ($response->successful()) {
                    $payment = $response->json();
                    $citaId = $payment['metadata']['cita_id'] ?? null;

                    if ($citaId && ($payment['status'] === 'approved' || $payment['status'] === 'authorized')) {
                        $cita = Cita::findOrFail($citaId);

                        // Si ya está completada, no hacemos nada
                        if ($cita->estado === Cita::ESTADO_COMPLETADO) {
                            return response()->json(['status' => 'already_processed']);
                        }

                        // 1. Completar la cita automáticamente
                        $cita->estado = Cita::ESTADO_COMPLETADO;
                        $cita->fin_servicio = now();
                        if ($cita->inicio_servicio) {
                            $cita->tiempo_servicio = (int) $cita->inicio_servicio->diffInMinutes(now());
                        }
                        $cita->save();

                        // 2. Generar la venta usando el servicio existente
                        $paymentInfo = [
                            'pago_recibido' => 'si',
                            'metodo_pago' => 'terminal', // Lo marcamos como terminal/tarjeta
                            'pago_id' => $payment['id'],
                        ];

                        app(VentaFromCitaService::class)->createFromCita($cita, $paymentInfo);

                        Log::info("Cita #{$citaId} pagada y venta generada vía MercadoPago.");
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing MercadoPago Cita Webhook: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Rutas de retorno informativas
     */
    public function success(Request $id) { return response()->json(['success' => true, 'message' => 'Pago exitoso']); }
    public function error(Request $id) { return response()->json(['success' => false, 'message' => 'Pago fallido']); }
    public function pending(Request $id) { return response()->json(['success' => true, 'message' => 'Pago pendiente']); }
}
