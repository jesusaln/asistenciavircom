<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\PolizaCargo;
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

            $preference->external_reference = 'VENTA_' . $venta->id;
            $preference->notification_url = route('portal.pagos.mercadopago.webhook');

            $preference->save();

            return response()->json([
                'success' => true,
                'init_point' => $preference->init_point,
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

    /**
     * Crear preferencia de pago para un Cargo de Póliza
     */
    public function createForCargo(Request $request)
    {
        $validated = $request->validate([
            'cargo_id' => 'required|exists:poliza_cargos,id',
        ]);

        $cliente = Auth::guard('client')->user();
        $cargo = PolizaCargo::whereHas('poliza', function ($q) use ($cliente) {
            $q->where('cliente_id', $cliente->id);
        })->findOrFail($validated['cargo_id']);

        if ($cargo->estado === 'pagado') {
            return response()->json(['success' => false, 'message' => 'Este cargo ya fue pagado.'], 400);
        }

        try {
            \MercadoPago\SDK::setAccessToken(config('services.mercadopago.access_token'));
            $preference = new \MercadoPago\Preference();

            $item = new \MercadoPago\Item();
            $item->title = $cargo->concepto;
            $item->quantity = 1;
            $item->unit_price = floatval($cargo->total);
            $item->currency_id = $cargo->moneda ?: 'MXN';

            $preference->items = [$item];
            $preference->external_reference = 'CARGO_' . $cargo->id;
            $preference->notification_url = route('portal.pagos.mercadopago.webhook');

            $preference->back_urls = [
                'success' => route('portal.polizas.show', [$cargo->poliza_id, 'pago' => 'success']),
                'failure' => route('portal.polizas.show', [$cargo->poliza_id, 'pago' => 'failure']),
            ];
            $preference->auto_return = 'approved';

            $preference->save();

            return response()->json([
                'success' => true,
                'init_point' => $preference->init_point,
            ]);
        } catch (\Exception $e) {
            Log::error('Portal MP Cargo error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar pago.'], 500);
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
                            $venta->metodo_pago = 'mercadopago';
                            $venta->notas_pago = "Pagado vía MercadoPago ID: " . $payment->id;
                            $venta->save();

                            if ($venta->cuentaPorCobrar) {
                                $venta->cuentaPorCobrar->update([
                                    'monto_pendiente' => 0,
                                    'estado' => 'pagado'
                                ]);
                            }
                        }
                    } elseif (str_starts_with($ref, 'CARGO_')) {
                        $cargoId = str_replace('CARGO_', '', $ref);
                        $cargo = PolizaCargo::find($cargoId);

                        if ($cargo && $cargo->estado !== 'pagado') {
                            $cargo->update([
                                'estado' => 'pagado',
                                'fecha_pago' => now(),
                                'metodo_pago' => 'mercadopago',
                                'referencia_pago' => $payment->id,
                                'notas' => ($cargo->notas ? $cargo->notas . "\n" : "") . "Pagado vía MercadoPago ID: " . $payment->id
                            ]);

                            if ($cargo->poliza && $cargo->poliza->estado === 'vencida_en_gracia') {
                                $otrosVencidos = $cargo->poliza->cargos()->vencidos()->where('id', '!=', $cargo->id)->count();
                                if ($otrosVencidos === 0) {
                                    $cargo->poliza->update(['estado' => 'activa']);
                                }
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