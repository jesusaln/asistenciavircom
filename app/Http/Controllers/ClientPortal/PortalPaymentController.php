<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Enums\EstadoVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PortalPaymentController extends Controller
{
    /**
     * Crear preferencia de pago en MercadoPago para una Venta
     */
    public function createMercadoPago(Request $request)
    {
        $validated = $request->validate([
            'venta_id' => 'required|exists:ventas,id',
        ]);

        $cliente = Auth::guard('client')->user();
        $venta = Venta::where('cliente_id', $cliente->id)->findOrFail($validated['venta_id']);

        if ($venta->estado === EstadoVenta::Pagado || $venta->pagado) {
            return response()->json([
                'success' => false,
                'message' => 'Esta venta ya fue pagada.',
            ], 400);
        }

        try {
            // Configurar MercadoPago SDK
            \MercadoPago\SDK::setAccessToken(config('services.mercadopago.access_token'));

            // Crear preferencia
            $preference = new \MercadoPago\Preference();

            // Items
            $items = [];
            // Aquí asumimos que venta->items tiene items con descripción y precio
            // Si la estructura es compleja, simplificamos a un solo item "Pago de Venta #Folio"

            $item = new \MercadoPago\Item();
            $item->title = "Pago de Nota de Venta #" . ($venta->numero_venta ?? $venta->id);
            $item->quantity = 1;
            $item->unit_price = floatval($venta->total);
            $items[] = $item;

            $preference->items = $items;

            // URLs de retorno
            $preference->back_urls = [
                'success' => route('portal.dashboard', ['pago_status' => 'success']),
                'failure' => route('portal.dashboard', ['pago_status' => 'failure']),
                'pending' => route('portal.dashboard', ['pago_status' => 'pending']),
            ];
            $preference->auto_return = 'approved';

            // Datos del comprador
            $preference->payer = [
                'name' => $cliente->nombre_razon_social,
                'email' => $cliente->email,
            ];

            // Referencia externa: Prefijo VENTA_ para distinguir de pedidos online si usan el mismo webhook,
            // pero usaremos webhook dedicado idealmente.
            $preference->external_reference = 'VENTA_' . $venta->id;

            // Usamos un webhook específico para portal si es posible, o el general con lógica de detección
            // Por simplicidad en este sprint, notificaremos al webhook general y ahí filtraremos,
            // o crearemos una ruta dedicada y la pasamos aquí.
            $preference->notification_url = route('portal.pagos.mercadopago.webhook');

            $preference->save();

            return response()->json([
                'success' => true,
                'init_point' => $preference->init_point,
                // init_point redirige al checkout de MP
                'sandbox_init_point' => $preference->sandbox_init_point,
            ]);

        } catch (\Exception $e) {
            Log::error('Portal MP error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar pasarela: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function webhookMercadoPago(Request $request)
    {
        // Lógica básica de webhook
        $type = $request->input('type');
        $data = $request->input('data');

        Log::info('Portal MP Webhook', $request->all());

        if ($type === 'payment') {
            try {
                \MercadoPago\SDK::setAccessToken(config('services.mercadopago.access_token'));
                $payment = \MercadoPago\Payment::find_by_id($data['id']);

                if ($payment && $payment->status === 'approved') {
                    $ref = $payment->external_reference;
                    if (str_starts_with($ref, 'VENTA_')) {
                        $ventaId = str_replace('VENTA_', '', $ref);
                        $venta = Venta::find($ventaId);

                        if ($venta && !$venta->pagado) {
                            $venta->estado = EstadoVenta::Pagado;
                            $venta->pagado = true;
                            $venta->fecha_pago = now();
                            $venta->metodo_pago = 'mercadopago'; // o tarjeta
                            $venta->notas_pago = "Pagado vía MercadoPago ID: " . $payment->id;
                            $venta->save();

                            // Actualizar saldo crédito si aplica? No, esto es pago directo.
                            // Pero si había CXC
                            if ($venta->cuentaPorCobrar) {
                                $venta->cuentaPorCobrar->update([
                                    'monto_pendiente' => 0,
                                    'estado' => 'pagado'
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing Portal MP webhook: ' . $e->getMessage());
            }
        }
        return response()->json(['status' => 'ok']);
    }
}
