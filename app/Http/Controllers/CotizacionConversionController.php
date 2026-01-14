<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;
use App\Enums\EstadoVenta;
use App\Models\Cotizacion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\VentaCreationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class CotizacionConversionController extends Controller
{
    /**
     * Convertir cotización a venta usando VentaCreationService
     */
    public function convertirAVenta($id)
    {
        $cotizacion = Cotizacion::with(['cliente', 'items.cotizable'])->findOrFail($id);

        if (!in_array($cotizacion->estado, [EstadoCotizacion::Pendiente, EstadoCotizacion::Aprobada], true)) {
            return Redirect::back()->with('error', 'Solo cotizaciones pendientes o aprobadas pueden convertirse a venta');
        }

        $almacenId = auth()->user()->almacen_venta_id;
        if (!$almacenId) {
            return Redirect::back()->with('error', 'No se pudo determinar el almacén de venta. Configure su almacén en su perfil de usuario.');
        }

        $almacen = \App\Models\Almacen::find($almacenId);
        if (!$almacen || $almacen->estado !== 'activo') {
            return Redirect::back()->with('error', 'El almacén asignado no existe o no está activo.');
        }

        DB::beginTransaction();
        try {
            foreach ($cotizacion->items as $item) {
                if ($item->cotizable_type === Producto::class) {
                    $producto = Producto::find($item->cotizable_id);
                    if ($producto && ($producto->requiere_serie || $producto->maneja_series || $producto->expires)) {
                        throw new \Exception('La cotización contiene productos serializados. Para convertir a venta, edite la cotización y especifique las series requeridas.');
                    }
                }
            }

            $productos = [];
            $servicios = [];

            foreach ($cotizacion->items as $item) {
                $itemData = [
                    'id' => $item->cotizable_id,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento ?? 0,
                    'price_list_id' => $item->price_list_id,
                ];

                if ($item->cotizable_type === Producto::class) {
                    $itemData['series'] = [];
                    $productos[] = $itemData;
                } else {
                    $servicios[] = $itemData;
                }
            }

            $ventaData = [
                'cliente_id' => $cotizacion->cliente_id,
                'cotizacion_id' => $cotizacion->id,
                'almacen_id' => $almacenId,
                'productos' => $productos,
                'servicios' => $servicios,
                'metodo_pago' => 'efectivo',
                'notas' => "Generada desde cotización #{$cotizacion->numero_cotizacion}",
            ];

            $ventaCreationService = app(VentaCreationService::class);
            $venta = $ventaCreationService->createVenta($ventaData, true);

            $cotizacion->update(['estado' => EstadoCotizacion::Convertida]);

            DB::commit();
            return Redirect::route('ventas.show', $venta->id)
                ->with('success', 'Venta creada exitosamente desde cotización');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error convirtiendo cotización a venta', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage()
            ]);
            return Redirect::back()->with('error', 'Error al crear la venta: ' . $e->getMessage());
        }
    }

    /**
     * Enviar a Pedido.
     * (Nota: la unificación completa de pivots se atiende en el paso #8)
     */
    public function enviarAPedido($id)
    {
        try {
            DB::beginTransaction();

            $cotizacion = Cotizacion::with([
                'items.cotizable',
                'cliente'
            ])->findOrFail($id);

            if (!$cotizacion->puedeEnviarseAPedido()) {
                return response()->json([
                    'success' => false,
                    'error' => 'La cotización no está en estado válido para enviar a pedido',
                    'estado_actual' => $cotizacion->estado->value,
                    'requiere_confirmacion' => false
                ], 400);
            }

            if ($cotizacion->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'La cotización no contiene items para enviar a pedido',
                    'requiere_confirmacion' => false
                ], 400);
            }

            if (!$cotizacion->cliente) {
                return response()->json([
                    'success' => false,
                    'error' => 'La cotización no tiene cliente asociado',
                    'requiere_confirmacion' => false
                ], 400);
            }

            $pedido = new Pedido();
            $pedido->fill([
                'cliente_id' => $cotizacion->cliente_id,
                'cotizacion_id' => $cotizacion->id,
                'numero_pedido' => $this->generarNumeroPedido(),
                'fecha' => now(),
                'estado' => EstadoPedido::Pendiente,
                'subtotal' => $cotizacion->subtotal,
                'descuento_general' => $cotizacion->descuento_general,
                'iva' => $cotizacion->iva,
                'total' => $cotizacion->total,
                'notas' => "Generado desde Cotizacion #{$cotizacion->id}"
            ]);
            $pedido->save();

            foreach ($cotizacion->items as $item) {
                $cotizable = $item->cotizable;
                $nombre = $cotizable?->nombre ?? 'Producto/Servicio';

                $tipoItem = 'producto';
                if ($item->cotizable_type === \App\Models\Servicio::class) {
                    $tipoItem = 'servicio';
                } elseif ($cotizable && method_exists($cotizable, 'esKit') && $cotizable->esKit()) {
                    $tipoItem = 'kit';
                }

                $pedido->items()->create([
                    'pedible_id' => $item->cotizable_id,
                    'pedible_type' => $item->cotizable_type,
                    'nombre' => $nombre,
                    'tipo_item' => $tipoItem,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento,
                    'subtotal' => $item->subtotal,
                    'descuento_monto' => $item->descuento_monto,
                    'price_list_id' => $item->price_list_id
                ]);
            }

            $ordenesCompraCreadas = [];
            $itemsFaltantesConsolidados = [];
            $almacenId = auth()->check() ? auth()->user()->almacen_venta_id : 1;

            foreach ($cotizacion->items as $item) {
                if ($item->cotizable_type === Producto::class) {
                    $producto = Producto::with('kitItems.item')->find($item->cotizable_id);

                    if ($producto) {
                        if ($producto->esKit()) {
                            $componentes = $producto->expandirKit($item->cantidad, $almacenId);

                            foreach ($componentes as $componenteData) {
                                $componente = $componenteData['producto'];
                                $cantidadNecesaria = $componenteData['cantidad'];

                                if ($componente->stock_disponible < $cantidadNecesaria) {
                                    $cantidadFaltante = $cantidadNecesaria - max(0, $componente->stock_disponible);
                                    $proveedorId = $componente->proveedor_id ?? 'generico';

                                    if (!isset($itemsFaltantesConsolidados[$proveedorId])) {
                                        $itemsFaltantesConsolidados[$proveedorId] = [];
                                    }

                                    if (!isset($itemsFaltantesConsolidados[$proveedorId][$componente->id])) {
                                        $itemsFaltantesConsolidados[$proveedorId][$componente->id] = [
                                            'producto' => $componente,
                                            'cantidad' => 0,
                                            'precio_costo' => $componenteData['costo_unitario'] ?? $componente->precio_compra ?? 0
                                        ];
                                    }

                                    $itemsFaltantesConsolidados[$proveedorId][$componente->id]['cantidad'] += $cantidadFaltante;
                                }
                            }
                        } else {
                            if ($producto->stock_disponible < $item->cantidad) {
                                $cantidadFaltante = $item->cantidad - max(0, $producto->stock_disponible);
                                $proveedorId = $producto->proveedor_id ?? 'generico';

                                if (!isset($itemsFaltantesConsolidados[$proveedorId])) {
                                    $itemsFaltantesConsolidados[$proveedorId] = [];
                                }

                                if (!isset($itemsFaltantesConsolidados[$proveedorId][$producto->id])) {
                                    $itemsFaltantesConsolidados[$proveedorId][$producto->id] = [
                                        'producto' => $producto,
                                        'cantidad' => 0,
                                        'precio_costo' => $producto->precio_compra ?? 0
                                    ];
                                }

                                $itemsFaltantesConsolidados[$proveedorId][$producto->id]['cantidad'] += $cantidadFaltante;
                            }
                        }
                    }
                }
            }

            if (!empty($itemsFaltantesConsolidados)) {
                $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;

                foreach ($itemsFaltantesConsolidados as $proveedorId => $items) {
                    $proveedor = null;

                    if ($proveedorId === 'generico') {
                        $proveedor = \App\Models\Proveedor::firstOrCreate(
                            ['nombre_razon_social' => 'PROVEEDOR GENERICO'],
                            [
                                'tipo_persona' => 'moral',
                                'rfc' => 'XAXX010101000',
                                'regimen_fiscal' => '601',
                                'uso_cfdi' => 'G03',
                                'email' => 'generico@sistema.com',
                                'calle' => 'CONOCIDO',
                                'numero_exterior' => 'S/N',
                                'colonia' => 'CENTRO',
                                'codigo_postal' => '00000',
                                'municipio' => 'GENERICO',
                                'estado' => 'GENERICO',
                                'pais' => 'México',
                                'activo' => true
                            ]
                        );
                    } else {
                        $proveedor = \App\Models\Proveedor::find($proveedorId);
                        if (!$proveedor) {
                            $proveedor = \App\Models\Proveedor::firstOrCreate(
                                ['nombre_razon_social' => 'PROVEEDOR GENERICO'],
                                [
                                    'rfc' => 'XAXX010101000',
                                    'tipo_persona' => 'moral',
                                    'regimen_fiscal' => '601',
                                    'uso_cfdi' => 'G03',
                                    'email' => 'generico@sistema.com',
                                    'calle' => 'CONOCIDO',
                                    'numero_exterior' => 'S/N',
                                    'colonia' => 'CENTRO',
                                    'codigo_postal' => '00000',
                                    'municipio' => 'GENERICO',
                                    'estado' => 'GENERICO',
                                    'pais' => 'México',
                                    'activo' => true
                                ]
                            );
                        }
                    }

                    $subtotalOrden = 0;
                    foreach ($items as $itemData) {
                        $subtotalOrden += $itemData['cantidad'] * $itemData['precio_costo'];
                    }

                    $ordenCompra = \App\Models\OrdenCompra::create([
                        'proveedor_id' => $proveedor->id,
                        'pedido_id' => $pedido->id,
                        'fecha_orden' => now(),
                        'prioridad' => 'media',
                        'terminos_pago' => 'contado',
                        'metodo_pago' => 'transferencia',
                        'subtotal' => $subtotalOrden,
                        'descuento_items' => 0,
                        'descuento_general' => 0,
                        'iva' => $subtotalOrden * $ivaRate,
                        'total' => $subtotalOrden * (1 + $ivaRate),
                        'estado' => 'pendiente',
                        'observaciones' => "Generada automáticamente por falta de stock en Pedido #{$pedido->numero_pedido}"
                    ]);

                    foreach ($items as $itemData) {
                        $ordenCompra->productos()->attach($itemData['producto']->id, [
                            'cantidad' => $itemData['cantidad'],
                            'precio' => $itemData['precio_costo'],
                            'descuento' => 0,
                            'unidad_medida' => $itemData['producto']->unidad_medida ?? 'pza'
                        ]);
                    }

                    $ordenesCompraCreadas[] = [
                        'id' => $ordenCompra->id,
                        'proveedor' => $proveedor->nombre_razon_social,
                        'total' => $ordenCompra->total
                    ];

                    Log::info('Orden de Compra automática creada', [
                        'orden_id' => $ordenCompra->id,
                        'pedido_origen' => $pedido->id,
                        'proveedor' => $proveedor->nombre_razon_social
                    ]);
                }
            }

            $cotizacion->update(['estado' => EstadoCotizacion::EnviadoAPedido]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($ordenesCompraCreadas) > 0
                    ? 'Pedido creado exitosamente. Se generaron ' . count($ordenesCompraCreadas) . ' órdenes de compra por falta de stock.'
                    : 'Pedido creado exitosamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'items_count' => $pedido->items()->count(),
                'ordenes_compra_creadas' => $ordenesCompraCreadas
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error interno al enviar cotización a pedido', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Error interno al procesar el pedido',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function generarNumeroPedido()
    {
        $ultimo = Pedido::orderBy('id', 'desc')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'PED-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}
