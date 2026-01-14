<?php

namespace App\Http\Controllers;

use App\Models\ClienteTienda;
use App\Models\PedidoOnline;
use App\Models\Producto;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    /**
     * Mostrar página del carrito
     */
    public function carrito()
    {
        $empresa = EmpresaConfiguracion::getConfig();

        $recomendados = Producto::where('estado', 'activo')
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'precio_con_iva' => round($p->precio_venta * 1.16, 2),
                    'imagen' => $p->imagen,
                    'stock' => $p->stock
                ];
            });

        return Inertia::render('Catalogo/Carrito', [
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Tienda',
                'whatsapp' => $empresa->whatsapp ?? $empresa->telefono ?? null,
            ] : null,
            'cliente' => $this->getClienteFromSession(),
            'canLogin' => true,
            'recomendados' => $recomendados,
        ]);
    }

    /**
     * Mostrar página de checkout
     */
    public function show()
    {
        $cliente = $this->getClienteFromSession();
        $empresa = EmpresaConfiguracion::getConfig();

        return Inertia::render('Catalogo/Checkout', [
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Tienda',
                'whatsapp' => $empresa->whatsapp ?? $empresa->telefono ?? null,
            ] : null,
            'cliente' => $cliente,
            'canLogin' => true,
        ]);
    }

    /**
     * Procesar el checkout y crear pedido
     * Usa transacciones y locks para evitar race conditions
     */
    public function procesar(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email',
            'telefono' => 'nullable|string|max:20',
            'tipo_entrega' => 'required|in:domicilio,recoger',
            'direccion' => 'required_if:tipo_entrega,domicilio|array',
            'direccion.calle' => 'required_if:tipo_entrega,domicilio|string',
            'direccion.colonia' => 'required_if:tipo_entrega,domicilio|string',
            'direccion.ciudad' => 'required_if:tipo_entrega,domicilio|string',
            'direccion.estado' => 'required_if:tipo_entrega,domicilio|string',
            'direccion.cp' => 'required_if:tipo_entrega,domicilio|string',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|string',
            'items.*.cantidad' => 'required|integer|min:1',
            'metodo_pago' => 'required|in:mercadopago,paypal,transferencia,efectivo',
            'notas' => 'nullable|string|max:500',
        ]);

        // Usar transacción para garantizar integridad
        return \DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $itemsConPrecio = [];
            $productosActualizar = [];

            $serviceCva = new \App\Services\CVAService();
            $itemsCVA = [];

            foreach ($validated['items'] as $item) {
                if (is_string($item['producto_id']) && str_starts_with($item['producto_id'], 'CVA-')) {
                    // Producto de CVA
                    $clave = str_replace('CVA-', '', $item['producto_id']);
                    $itemCva = $serviceCva->getProductDetails($clave, true); // Normalized

                    if (!$itemCva) {
                        throw new \Exception("Producto de Almacén Central no encontrado: {$clave}");
                    }

                    if ($itemCva['stock'] < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente en Almacén Central para: {$itemCva['nombre']}. Disponible: {$itemCva['stock']}");
                    }

                    $precioConIva = round($itemCva['precio'] * 1.16, 2);
                    $itemSubtotal = $precioConIva * $item['cantidad'];
                    $subtotal += $itemSubtotal;

                    $itemsConPrecio[] = [
                        'producto_id' => $item['producto_id'],
                        'nombre' => $itemCva['nombre'],
                        'precio' => $itemCva['precio'],
                        'precio_con_iva' => $precioConIva,
                        'cantidad' => $item['cantidad'],
                        'origen' => 'CVA'
                    ];

                    $itemsCVA[] = [
                        'clave' => $clave,
                        'cantidad' => $item['cantidad']
                    ];
                } else {
                    // Producto Local
                    $producto = Producto::where('id', $item['producto_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$producto || $producto->estado !== 'activo') {
                        throw new \Exception("Producto local no disponible: {$item['producto_id']}");
                    }

                    if ($producto->stock < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente para: {$producto->nombre}. Disponible: {$producto->stock}");
                    }

                    // Precio con IVA (16%)
                    $precioConIva = round($producto->precio_venta * 1.16, 2);
                    $itemSubtotal = $precioConIva * $item['cantidad'];
                    $subtotal += $itemSubtotal;

                    $itemsConPrecio[] = [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'precio' => $producto->precio_venta,
                        'precio_con_iva' => $precioConIva,
                        'cantidad' => $item['cantidad'],
                        'origen' => 'local'
                    ];

                    $productosActualizar[] = [
                        'producto' => $producto,
                        'cantidad' => $item['cantidad'],
                    ];
                }
            }

            // Costos de envío ($100 + IVA = $116 si es a domicilio)
            $costoEnvio = $validated['tipo_entrega'] === 'domicilio' ? 116.00 : 0.00;
            $total = $subtotal + $costoEnvio;

            // Crear pedido
            $cliente = $this->getClienteFromSession();

            $pedido = PedidoOnline::create([
                'numero_pedido' => PedidoOnline::generarNumeroPedido(),
                'cliente_tienda_id' => $cliente && $cliente['tipo'] === 'tienda' ? $cliente['id'] : null,
                'cliente_id' => $cliente && $cliente['tipo'] === 'portal' ? $cliente['id'] : null,
                'email' => $validated['email'],
                'nombre' => $validated['nombre'],
                'telefono' => $validated['telefono'] ?? null,
                'direccion_envio' => $validated['direccion'] ?? ['tipo' => 'recoger_en_tienda'],
                'items' => $itemsConPrecio,
                'subtotal' => $subtotal,
                'costo_envio' => $costoEnvio,
                'total' => $total,
                'metodo_pago' => $validated['metodo_pago'],
                'estado' => 'pendiente',
                'notas' => ($validated['notas'] ?? '') . ($validated['tipo_entrega'] === 'recoger' ? ' [RECOGER EN TIENDA]' : ''),
            ]);

            // Reducir stock de productos
            foreach ($productosActualizar as $data) {
                $data['producto']->decrement('stock', $data['cantidad']);
            }

            // Si hay productos de CVA, enviar pedido a CVA API
            if (!empty($itemsCVA)) {
                try {
                    $orderData = [
                        'productos' => $itemsCVA,
                    ];

                    if ($validated['tipo_entrega'] === 'domicilio') {
                        $orderData['tipo_flete'] = 'CP'; // Con paquetería
                        $orderData['flete'] = [
                            'calle' => $validated['direccion']['calle'] ?? '',
                            'numero' => 'SN',
                            'colonia' => $validated['direccion']['colonia'] ?? '',
                            'cp' => $validated['direccion']['cp'] ?? '',
                            'estado' => $validated['direccion']['estado'] ?? '',
                            'ciudad' => $validated['direccion']['ciudad'] ?? '',
                        ];
                    } else {
                        $orderData['tipo_flete'] = 'SF'; // Sin flete (Recoge)
                    }

                    $cvaResult = $serviceCva->createOrder($orderData);

                    if ($cvaResult['success']) {
                        $pedido->update([
                            'notas' => $pedido->notas . " [PEDIDO CVA: " . ($cvaResult['data']['pedido'] ?? 'OK') . "]"
                        ]);
                        \Log::info('Pedido enviado a CVA con éxito', ['cva_data' => $cvaResult['data']]);
                    } else {
                        \Log::error('Error al enviar pedido a CVA', ['error' => $cvaResult['error'], 'details' => $cvaResult['details'] ?? null]);
                        // Opcional: Podríamos marcar el pedido como "error_proveedor" o algo así
                        $pedido->update([
                            'notas' => $pedido->notas . " [ERROR CVA: " . ($cvaResult['error'] ?? 'Desconocido') . "]"
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Excepción al enviar pedido a CVA: ' . $e->getMessage());
                }
            }

            // Log del pedido creado
            \Log::info('Pedido online creado', [
                'numero_pedido' => $pedido->numero_pedido,
                'total' => $total,
                'items_count' => count($itemsConPrecio),
                'cliente' => $cliente ? $cliente['email'] ?? $cliente['nombre'] : 'Invitado',
            ]);

            return redirect()->route('tienda.pedido', ['numero' => $pedido->numero_pedido])
                ->with('success', 'Pedido creado con éxito');
        });
    }

    /**
     * Ver detalle de un pedido
     */
    public function pedido($numero)
    {
        $pedido = PedidoOnline::where('numero_pedido', $numero)->firstOrFail();
        $empresa = EmpresaConfiguracion::getConfig();

        return Inertia::render('Catalogo/PedidoConfirmado', [
            'pedido' => [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'nombre' => $pedido->nombre,
                'email' => $pedido->email,
                'telefono' => $pedido->telefono,
                'direccion_envio' => $pedido->direccion_envio,
                'items' => $pedido->items,
                'subtotal' => $pedido->subtotal,
                'costo_envio' => $pedido->costo_envio,
                'total' => $pedido->total,
                'metodo_pago' => $pedido->metodo_pago,
                'estado' => $pedido->estado,
                'estado_label' => $pedido->estado_label,
                'estado_color' => $pedido->estado_color,
                'created_at' => $pedido->created_at->format('d/m/Y H:i'),
            ],
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Tienda',
                'whatsapp' => $empresa->whatsapp ?? $empresa->telefono ?? null,
            ] : null,
            'cliente' => $this->getClienteFromSession(),
            'canLogin' => true,
        ]);
    }

    /**
     * Obtener cliente de la sesión
     */
    private function getClienteFromSession(): ?array
    {
        // 1. Intentar con el guard 'client' (Portal)
        if (Auth::guard('client')->check()) {
            $cliente = Auth::guard('client')->user();
            return [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre_razon_social,
                'email' => $cliente->email,
                'avatar' => null,
                'telefono' => $cliente->telefono,
                'direccion_predeterminada' => [
                    'calle' => $cliente->calle,
                    'colonia' => $cliente->colonia,
                    'ciudad' => $cliente->municipio,
                    'estado' => $cliente->estado,
                    'cp' => $cliente->codigo_postal,
                ],
                'iniciales' => mb_substr($cliente->nombre_razon_social, 0, 1),
                'tipo' => 'portal',
            ];
        }

        // 2. Intentar con sesión antigua (E-commerce)
        $clienteId = session('cliente_tienda_id');
        if (!$clienteId) {
            return null;
        }

        $cliente = ClienteTienda::find($clienteId);
        if (!$cliente) {
            return null;
        }

        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'email' => $cliente->email,
            'avatar' => $cliente->avatar,
            'telefono' => $cliente->telefono,
            'direccion_predeterminada' => $cliente->direccion_predeterminada,
            'iniciales' => $cliente->iniciales,
            'tipo' => 'tienda',
        ];
    }
}
