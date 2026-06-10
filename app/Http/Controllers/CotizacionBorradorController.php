<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Producto;
use App\Models\Servicio;
use App\Services\MarginService;
use App\Services\PrecioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CotizacionBorradorController extends Controller
{
    private PrecioService $precioService;

    public function __construct(PrecioService $precioService)
    {
        $this->precioService = $precioService;
    }

    /**
     * Guardar cotización en borrador.
     */
    public function guardarBorrador(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer|min:1',
            'productos.*.tipo' => 'required|in:producto,servicio',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0.01',
            'productos.*.descuento' => 'required|numeric|min:0|max:100',
            'descuento_general' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string|max:1000',
            'ajustar_margen' => 'nullable|boolean',
            'aplicar_retencion_iva' => 'nullable|boolean',
            'aplicar_retencion_isr' => 'nullable|boolean',
        ]);

        foreach ($validated['productos'] as $index => $item) {
            $class = $item['tipo'] === 'producto' ? Producto::class : Servicio::class;
            $modelo = $class::find($item['id']);

            if (!$modelo) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(["productos.{$index}.id" => "El " . $item['tipo'] . " con ID {$item['id']} no existe"])
                    ->with('error', 'Algunos productos o servicios seleccionados no existen');
            }

            if ($item['tipo'] === 'producto' && $modelo->estado !== 'activo') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(["productos.{$index}.id" => "El producto '{$modelo->nombre}' no está activo"])
                    ->with('error', 'Algunos productos seleccionados no están activos');
            }
        }

        $marginService = new MarginService();
        $validacionMargen = $marginService->validarMargenesProductos($validated['productos']);

        Log::info('Validación de márgenes en cotización (borrador)', [
            'productos_count' => count($validated['productos']),
            'todos_validos' => $validacionMargen['todos_validos'],
            'productos_bajo_margen_count' => count($validacionMargen['productos_bajo_margen']),
            'ajustar_margen_request' => $request->has('ajustar_margen') ? $request->ajustar_margen : 'no_presente'
        ]);

        if (!$validacionMargen['todos_validos']) {
            Log::info('Productos con margen insuficiente detectados (borrador)', [
                'productos_bajo_margen' => $validacionMargen['productos_bajo_margen']
            ]);

            if ($request->boolean('ajustar_margen')) {
                Log::info('Usuario aceptó ajuste automático de márgenes (borrador)');
                foreach ($validated['productos'] as &$item) {
                    if ($item['tipo'] === 'producto') {
                        $producto = Producto::find($item['id']);
                        if ($producto) {
                            $precioOriginal = $item['precio'];
                            $item['precio'] = $marginService->ajustarPrecioAlMargen($producto, $item['precio']);
                            Log::info('Precio ajustado (borrador)', [
                                'producto_id' => $producto->id,
                                'precio_original' => $precioOriginal,
                                'precio_ajustado' => $item['precio']
                            ]);
                        }
                    }
                }
            } else {
                Log::info('Mostrando modal de confirmación de márgenes insuficientes (borrador)');
                $mensaje = $marginService->generarMensajeAdvertencia($validacionMargen['productos_bajo_margen']);
                return redirect()->back()
                    ->withInput()
                    ->with('warning', $mensaje)
                    ->with('requiere_confirmacion_margen', true)
                    ->with('productos_bajo_margen', $validacionMargen['productos_bajo_margen']);
            }
        }

        try {
            DB::transaction(function () use ($validated, $request) {
                $cliente = Cliente::find($validated['cliente_id']);

                $subtotal = 0;
                $descuentoItems = 0;
                $itemData = [];

                foreach ($validated['productos'] as $item) {
                    $class = $item['tipo'] === 'producto' ? Producto::class : Servicio::class;
                    $modelo = $class::find($item['id']);

                    if (!$modelo) {
                        Log::warning('Ítem no encontrado al crear cotización (borrador)', [
                            'tipo' => $class,
                            'id' => $item['id']
                        ]);
                        continue;
                    }

                    $cantidad = (float) ($item['cantidad'] ?? 0);

                    if (isset($item['precio']) && $item['precio'] !== null) {
                        $precio = (float) $item['precio'];
                        $priceListId = $item['price_list_id'] ?? ($validated['price_list_id'] ?? null);
                    } else if ($item['tipo'] === 'producto') {
                        $detallesPrecio = $this->precioService->resolverPrecioConDetalles(
                            $modelo,
                            $cliente,
                            $validated['price_list_id'] ? \App\Models\PriceList::find($validated['price_list_id']) : null
                        );
                        $precio = $detallesPrecio['precio'];
                        $priceListId = $detallesPrecio['price_list_id'];
                    } else {
                        $precio = (float) ($item['precio'] ?? 0);
                        $priceListId = null;
                    }

                    $descuento = (float) ($item['descuento'] ?? 0);
                    $subtotalItem = $cantidad * $precio;
                    $descuentoMontoItem = $subtotalItem * ($descuento / 100);

                    $subtotal += $subtotalItem;
                    $descuentoItems += $descuentoMontoItem;

                    $itemData[] = [
                        'class' => $class,
                        'modelo' => $modelo,
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'descuento' => $descuento,
                        'subtotal' => $subtotalItem,
                        'descuento_monto' => $descuentoMontoItem,
                        'price_list_id' => $priceListId,
                        'item_id' => $item['id']
                    ];
                }

                $descuentoGeneralPorc = (float) ($request->descuento_general ?? 0);
                $descuentoGeneralMonto = ($subtotal - $descuentoItems) * ($descuentoGeneralPorc / 100);
                $subtotalFinal = ($subtotal - $descuentoItems) - $descuentoGeneralMonto;
                $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                $iva = $subtotalFinal * $ivaRate;

                $retencionIva = 0;
                if ($request->boolean('aplicar_retencion_iva')) {
                    $retIvaRate = \App\Services\EmpresaConfiguracionService::getRetencionIvaDefault() / 100;
                    $retencionIva = $subtotalFinal * $retIvaRate;
                }

                $retencionIsr = 0;
                if ($request->boolean('aplicar_retencion_isr')) {
                    $retIsrRate = \App\Services\EmpresaConfiguracionService::getRetencionIsrDefault() / 100;
                    $retencionIsr = $subtotalFinal * $retIsrRate;
                } else if ($cliente && $cliente->tipo_persona === 'moral' && \App\Services\EmpresaConfiguracionService::isIsrEnabled()) {
                    $isrRate = \App\Services\EmpresaConfiguracionService::getIsrPorcentaje() / 100;
                    $isr = $subtotalFinal * $isrRate;
                }

                $total = $subtotalFinal + $iva - $isr - $retencionIva - $retencionIsr;

                $cotizacion = Cotizacion::create([
                    'cliente_id' => $validated['cliente_id'],
                    'subtotal' => round($subtotal, 2),
                    'descuento_general' => round($descuentoGeneralMonto, 2),
                    'descuento_items' => round($descuentoItems, 2),
                    'iva' => round($iva, 2),
                    'retencion_iva' => round($retencionIva, 2),
                    'retencion_isr' => round($retencionIsr, 2),
                    'isr' => round($isr, 2),
                    'total' => round($total, 2),
                    'notas' => $request->notas,
                    'estado' => EstadoCotizacion::Borrador,
                ]);

                Log::info('Cotización borrador creada exitosamente', [
                    'cotizacion_id' => $cotizacion->id,
                    'cliente_id' => $validated['cliente_id'],
                    'productos_count' => count($validated['productos']),
                    'subtotal' => round($subtotal, 2),
                    'total' => round($total, 2),
                    'estado' => 'borrador'
                ]);

                foreach ($itemData as $data) {
                    CotizacionItem::create([
                        'cotizacion_id' => $cotizacion->id,
                        'cotizable_id' => $data['item_id'],
                        'cotizable_type' => $data['class'],
                        'cantidad' => (int) $data['cantidad'],
                        'precio' => round($data['precio'], 2),
                        'descuento' => round($data['descuento'], 2),
                        'subtotal' => round($data['subtotal'], 2),
                        'descuento_monto' => round($data['descuento_monto'], 2),
                        'price_list_id' => $data['price_list_id'],
                    ]);

                    Log::info('Ítem agregado a cotización borrador exitosamente', [
                        'cotizacion_id' => $cotizacion->id,
                        'tipo' => $data['class'],
                        'id' => $data['item_id'],
                        'nombre' => $data['modelo']->nombre,
                        'categoria' => $data['class'] === Producto::class ? $data['modelo']->categoria?->nombre : 'Servicio',
                        'cantidad' => $data['cantidad'],
                        'precio' => $data['precio'],
                        'price_list_id' => $data['price_list_id']
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error al crear cotización borrador', [
                'cliente_id' => $validated['cliente_id'],
                'productos_count' => count($validated['productos']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error interno al guardar el borrador. Por favor, inténtelo de nuevo.');
        }

        return redirect()->route('cotizaciones.index')->with('success', 'Cotización guardada como borrador');
    }
}
