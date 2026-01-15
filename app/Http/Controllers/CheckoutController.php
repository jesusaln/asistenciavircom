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
     * Validar el carrito contra stock y precios reales (CVA)
     */
    public function validateCart(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.producto_id' => 'required', // Puede ser int (local) o string (CVA)
            'items.*.cantidad' => 'required|integer|min:1',
        ]);

        $serviceCva = new \App\Services\CVAService();
        $results = [];
        $hasChanges = false;

        foreach ($validated['items'] as $item) {
            if (is_string($item['producto_id']) && str_starts_with($item['producto_id'], 'CVA-')) {
                $clave = str_replace('CVA-', '', $item['producto_id']);
                $latest = $serviceCva->getProductDetails($clave, true);

                if (!$latest) {
                    $results[] = [
                        'producto_id' => $item['producto_id'],
                        'status' => 'not_found',
                        'message' => 'Producto ya no está disponible'
                    ];
                    $hasChanges = true;
                    continue;
                }

                $newPrice = round($latest['precio'] * 1.16, 2);
                $status = 'ok';
                $message = '';

                if ($latest['stock'] < $item['cantidad']) {
                    $status = 'insufficient_stock';
                    $message = "Solo quedan {$latest['stock']} unidades";
                    $hasChanges = true;
                }

                $results[] = [
                    'producto_id' => $item['producto_id'],
                    'status' => $status,
                    'message' => $message,
                    'latest_price' => $newPrice,
                    'latest_stock' => $latest['stock']
                ];

                // Opción: Actualizar el cache local en DB si existe
                Producto::where('cva_clave', $clave)->update([
                    'precio_compra' => $latest['precio_compra'],
                    'precio_venta' => $latest['precio'],
                    'stock' => $latest['stock_local'],
                    'stock_cedis' => $latest['stock_cedis'],
                    'cva_last_sync' => now()
                ]);

            } else {
                // Validación local
                $local = Producto::find($item['producto_id']);
                if (!$local || $local->stock < $item['cantidad']) {
                    $results[] = [
                        'producto_id' => $item['producto_id'],
                        'status' => 'insufficient_stock',
                        'message' => 'Stock local agotado'
                    ];
                    $hasChanges = true;
                }
            }
        }

        return response()->json([
            'valid' => !$hasChanges,
            'results' => $results
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
            'items.*.producto_id' => 'required',
            'items.*.cantidad' => 'required|integer|min:1',
            'metodo_pago' => 'required|in:mercadopago,paypal,transferencia,efectivo',
            'notas' => 'nullable|string|max:500',
        ]);

        // Usar transacción para garantizar integridad
        // Usar transacción para garantizar integridad
        return \DB::transaction(function () use ($validated) {
            // 1. GESTIÓN DE CLIENTE (CRM)
            $cliente = \App\Models\Cliente::where('email', $validated['email'])->first();
            $direccion = $validated['direccion'] ?? [];

            if (!$cliente) {
                // Crear nuevo cliente
                $cliente = \App\Models\Cliente::create([
                    'nombre_razon_social' => $validated['nombre'],
                    'rfc' => 'XAXX010101000', // RFC Genérico Público en General
                    'email' => $validated['email'],
                    'telefono' => $validated['telefono'],
                    'calle' => $direccion['calle'] ?? null,
                    'colonia' => $direccion['colonia'] ?? null,
                    'municipio' => $direccion['ciudad'] ?? null,
                    // Si el estado es largo (ej. SONORA), tomar solo las primeras 3 letras para clave (SON) 
                    // o dejar null si no hay mapa exacto, pero no enviar nombre completo si la columna es corta.
                    // Si el error persiste sobre char(2), podria ser el pais.
                    'estado' => isset($direccion['estado']) ? mb_strtoupper(substr($direccion['estado'], 0, 3)) : null,
                    'codigo_postal' => $direccion['cp'] ?? null,
                    'pais' => 'MX',
                    'regimen_fiscal' => '616',
                    'uso_cfdi' => 'S01',
                    'tipo_persona' => 'fisica',
                    'activo' => true
                ]);
            } else {
                // Actualizar dirección si es envío a domicilio
                if (($validated['tipo_entrega'] === 'domicilio') && !empty($direccion)) {
                    $cliente->update([
                        'calle' => $direccion['calle'] ?? $cliente->calle,
                        'colonia' => $direccion['colonia'] ?? $cliente->colonia,
                        'municipio' => $direccion['ciudad'] ?? $cliente->municipio,
                        'estado' => $direccion['estado'] ?? $cliente->estado,
                        'codigo_postal' => $direccion['cp'] ?? $cliente->codigo_postal,
                        'telefono' => $validated['telefono'] ?? $cliente->telefono,
                    ]);
                }
            }

            $subtotal = 0;
            $itemsConPrecio = [];
            $productosActualizar = [];

            $serviceCva = new \App\Services\CVAService();
            $itemsCVA = [];

            foreach ($validated['items'] as $item) {
                if (is_string($item['producto_id']) && str_starts_with($item['producto_id'], 'CVA-')) {
                    // Producto de CVA
                    $clave = str_replace('CVA-', '', $item['producto_id']);

                    // IMPORTACIÓN AUTOMÁTICA AL CATÁLOGO LOCAL
                    $productoLocal = \App\Models\Producto::where('codigo', $clave)->first();
                    if (!$productoLocal) {
                        try {
                            $importResult = $serviceCva->importProduct($clave);
                            if (isset($importResult['success']) && $importResult['success']) {
                                $productoLocal = $importResult['producto'];
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error importando CVA en checkout: " . $e->getMessage());
                        }
                    }

                    $itemCvaInfo = $serviceCva->getProductDetails($clave, true); // Normalized

                    if (!$itemCvaInfo) {
                        throw new \Exception("Producto de Almacén Central no encontrado: {$clave}");
                    }

                    if ($itemCvaInfo['stock'] < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente en Almacén Central para: {$itemCvaInfo['nombre']}. Disponible: {$itemCvaInfo['stock']}");
                    }

                    $precioConIva = round($itemCvaInfo['precio'] * 1.16, 2);
                    $itemSubtotal = $precioConIva * $item['cantidad'];
                    $subtotal += $itemSubtotal;

                    $itemsConPrecio[] = [
                        'producto_id' => $item['producto_id'],
                        'local_id' => $productoLocal?->id, // Guardamos referencia local
                        'nombre' => $itemCvaInfo['nombre'],
                        'precio' => $itemCvaInfo['precio'],
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

            // Costos de envío DINÁMICOS si es a domicilio
            $costoEnvio = 0;
            if ($validated['tipo_entrega'] === 'domicilio') {
                $cp = $validated['direccion']['cp'];
                // Formatear items para el cotizador de CVA
                $itemsParaEnvio = array_map(function ($i) {
                    return [
                        'id' => $i['producto_id'],
                        'cantidad' => $i['cantidad'],
                        // El peso se obtendrá del producto en el servicio si no viene aquí
                    ];
                }, $itemsConPrecio);

                $shippingInfo = $serviceCva->calculateShippingCost($cp, $itemsParaEnvio);

                if (isset($shippingInfo['success']) && $shippingInfo['success']) {
                    $costoEnvio = (float) $shippingInfo['costo'];
                } else {
                    // Fallback: Costo fijo de envío si falla API o no hay cotización
                    $costoEnvio = 100.00;
                }
            }

            $total = $subtotal + $costoEnvio;

            // Crear pedido
            $cliente = $this->getClienteFromSession();
            $clienteTiendaId = null;
            $clientePortalId = null;

            if ($cliente) {
                if ($cliente['tipo'] === 'tienda')
                    $clienteTiendaId = $cliente['id'];
                if ($cliente['tipo'] === 'portal')
                    $clientePortalId = $cliente['id'];
            } else {
                // AUTO-ASOCIACIÓN DE INVITADOS
                // Buscar si ya existe un cliente con este email para no duplicar ni dejar huérfano
                $clienteExistente = ClienteTienda::where('email', $validated['email'])->first();

                if ($clienteExistente) {
                    $clienteTiendaId = $clienteExistente->id;
                    // Opcional: Actualizar teléfono si no tenía
                    if (empty($clienteExistente->telefono) && !empty($validated['telefono'])) {
                        $clienteExistente->update(['telefono' => $validated['telefono']]);
                    }
                } else {
                    // Crear un nuevo registro de cliente automáticamente
                    try {
                        $empresaId = \App\Models\EmpresaConfiguracion::first()?->id ?? 1; // Obtener ID empresa
                        $nuevoCliente = ClienteTienda::create([
                            'empresa_id' => $empresaId,
                            'nombre' => $validated['nombre'],
                            'email' => $validated['email'],
                            'telefono' => $validated['telefono'] ?? null,
                            'direccion_predeterminada' => $validated['direccion'] ?? null,
                            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)), // Password aleatorio
                        ]);
                        $clienteTiendaId = $nuevoCliente->id;
                    } catch (\Exception $e) {
                        \Log::error('Error auto-creando cliente invitado: ' . $e->getMessage());
                        // No detenemos la venta si falla la creación del usuario, seguimos como null
                    }
                }
            }

            $pedido = PedidoOnline::create([
                'numero_pedido' => PedidoOnline::generarNumeroPedido(),
                'cliente_tienda_id' => $clienteTiendaId,
                'cliente_id' => $clientePortalId,
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

            // SOLO ENVIAR A CVA si el método es Transferencia/Efectivo
            // Para pagos ONLINE (PayPal/MP), esperaremos al WEBHOOK para confirmar y disparar la orden
            $isManualPayment = in_array($validated['metodo_pago'], ['transferencia', 'efectivo']);

            if (!empty($itemsCVA) && $isManualPayment) {
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
                            'notas' => $pedido->notas . " [DETALLE CVA: " . ($cvaResult['data']['pedido'] ?? 'ORDEN CREADA') . "]"
                        ]);
                    } else {
                        \Log::error('Error al enviar pedido manual a CVA', ['error' => $cvaResult['error']]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Excepción al enviar pedido manual a CVA: ' . $e->getMessage());
                }
            }

            // Log del pedido creado
            \Log::info('Pedido online creado', [
                'numero_pedido' => $pedido->numero_pedido,
                'total' => $total,
                'items_count' => count($itemsConPrecio),
                'cliente' => $cliente ? $cliente['email'] ?? $cliente['nombre'] : 'Invitado',
            ]);

            // Enviar correo de confirmación
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $pedido->email)
                    ->notify(new \App\Notifications\PedidoCreadoNotification($pedido));
            } catch (\Exception $e) {
                \Log::error('Error enviando correo de pedido: ' . $e->getMessage());
            }

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
