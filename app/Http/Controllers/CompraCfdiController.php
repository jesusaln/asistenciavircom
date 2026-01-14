<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCompra;
use App\Models\Almacen;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\Compras\CompraCuentasPagarService;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompraCfdiController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly CompraCuentasPagarService $cuentasPagarService
    ) {
    }

    /**
     * Obtener lista de CFDIs recibidos disponibles para importar
     */
    public function getReceivedCfdis(Request $request)
    {
        $query = \App\Models\Cfdi::recibidos()
            ->where('tipo_comprobante', 'I');
        // Nota: El filtro doesntHave('cuentaPorPagar') se removio porque la columna cfdi_id
        // no existe actualmente en la tabla cuentas_por_pagar

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'ilike', "%{$search}%")
                    ->orWhere('folio', 'ilike', "%{$search}%")
                    ->orWhere('uuid', 'ilike', "%{$search}%")
                    ->orWhere('nombre_emisor', 'ilike', "%{$search}%")
                    ->orWhere('rfc_emisor', 'ilike', "%{$search}%");
            });
        }

        // Filtro por fecha si es necesario
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_fin);
        }

        $cfdis = $query->with('compra:id,numero_compra,cfdi_uuid')
            ->orderBy('fecha_emision', 'desc')
            ->limit($request->filled('fecha_inicio') ? 1000 : 50)
            ->get()
            ->map(function ($cfdi) {
                return [
                    'id' => $cfdi->id,
                    'serie_folio' => $cfdi->serie_folio,
                    'folio' => $cfdi->folio,
                    'serie' => $cfdi->serie,
                    'fecha' => $cfdi->fecha_emision?->format('Y-m-d'),
                    'total' => $cfdi->total,
                    'emisor_nombre' => $cfdi->nombre_emisor ?: 'N/A',
                    'emisor_rfc' => $cfdi->rfc_emisor ?: 'N/A',
                    'uuid' => $cfdi->uuid,
                    // Estado de importacion expuesto
                    'importado' => (bool) $cfdi->compra,
                    'compra_id' => $cfdi->compra?->id,
                    'compra_numero' => $cfdi->compra?->numero_compra,
                ];
            });

        return response()->json($cfdis);
    }

    /**
     * Parsear archivo XML de CFDI y retornar datos para poblar el formulario de compra
     */
    public function parseXmlCfdi(Request $request)
    {
        $request->validate([
            'xml_file' => 'nullable|required_without:cfdi_id|file|max:5120',
            'cfdi_id' => 'nullable|required_without:xml_file|exists:cfdis,id',
        ]);

        try {
            $xmlContent = null;

            if ($request->hasFile('xml_file')) {
                $file = $request->file('xml_file');

                // Validar que sea XML
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();

                if ($extension !== 'xml' && !str_contains($mimeType, 'xml')) {
                    throw new \Exception('El archivo debe ser un XML valido.');
                }

                $xmlContent = file_get_contents($file->path());
            } elseif ($request->filled('cfdi_id')) {
                $cfdi = \App\Models\Cfdi::findOrFail($request->input('cfdi_id'));

                if (!$cfdi->xml_url || !Storage::disk('public')->exists($cfdi->xml_url)) {
                    throw new \Exception('El archivo XML asociado a este CFDI no se encuentra en el servidor.');
                }

                $xmlContent = Storage::disk('public')->get($cfdi->xml_url);
            }

            if (!$xmlContent) {
                throw new \Exception('No se pudo obtener el contenido del XML');
            }

            // Parsear XML
            $parserService = app(\App\Services\CfdiXmlParserService::class);
            $data = $parserService->parseCfdiXml($xmlContent);

            // Para tipo P (Complemento de Pago/REP), retornar datos basicos sin mapeo de productos
            $tipoComprobante = $data['tipo_comprobante'] ?? 'I';
            if ($tipoComprobante === 'P') {
                $proveedor = null;
                if (!empty($data['emisor']['rfc'])) {
                    $proveedor = $parserService->findProveedorByRfc($data['emisor']['rfc']);
                }

                $data['es_complemento_pago'] = true;
                $data['proveedor_encontrado'] = $proveedor ? [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre_razon_social,
                    'rfc' => $proveedor->rfc,
                ] : null;

                return response()->json([
                    'success' => true,
                    'data' => $data,
                ]);
            }

            if (empty($data['es_factura_valida'])) {
                throw new \Exception('El CFDI no es valido para compras. Solo se aceptan I (Ingreso) o E (Egreso).');
            }

            // Poblar automaticamente catalogos SAT desde los conceptos del XML
            $satStats = $parserService->poblarCatalogosSatDesdeConceptos($data['conceptos']);
            $data['sat_catalogs_stats'] = $satStats;

            // Mapear conceptos a productos existentes
            $data['conceptos'] = $parserService->mapConceptosToProductos($data['conceptos']);

            // Buscar proveedor por RFC del emisor
            $proveedor = null;
            if (!empty($data['emisor']['rfc'])) {
                $proveedor = $parserService->findProveedorByRfc($data['emisor']['rfc']);
            }

            $data['proveedor_encontrado'] = $proveedor ? [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre_razon_social,
                'rfc' => $proveedor->rfc,
            ] : null;

            // Calcular estadisticas de mapeo
            $totalConceptos = count($data['conceptos']);
            $conceptosMapeados = count(array_filter($data['conceptos'], fn($c) => $c['match_type'] !== 'none'));
            $conceptosSimilares = count(array_filter($data['conceptos'], fn($c) => $c['match_type'] === 'similar'));
            $conceptosNoEncontrados = count(array_filter($data['conceptos'], fn($c) => $c['match_type'] === 'none'));

            $data['mapeo_stats'] = [
                'total' => $totalConceptos,
                'mapeados' => $conceptosMapeados,
                'similares' => $conceptosSimilares,
                'no_encontrados' => $conceptosNoEncontrados,
                'requiere_revision' => $conceptosSimilares > 0 || $conceptosNoEncontrados > 0,
            ];

            Log::info('XML CFDI parseado correctamente', [
                'folio' => $data['serie'] . $data['folio'],
                'emisor_rfc' => $data['emisor']['rfc'] ?? 'N/A',
                'conceptos' => $totalConceptos,
                'mapeados' => $conceptosMapeados,
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al parsear XML CFDI', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el XML: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Importar un CFDI como compra automaticamente (para importacion masiva)
     */
    public function bulkImportSingle(Request $request)
    {
        Log::info('bulkImportSingle iniciado', ['cfdi_id' => $request->cfdi_id]);

        $request->validate([
            'cfdi_id' => 'required|exists:cfdis,id',
        ]);

        try {
            $cfdi = \App\Models\Cfdi::findOrFail($request->cfdi_id);
            Log::info('CFDI encontrado', ['uuid' => $cfdi->uuid, 'fecha_emision' => $cfdi->fecha_emision]);

            // Verificar que no este ya vinculado a una compra
            $existingCompra = Compra::where('empresa_id', Auth::user()->empresa_id)
                ->where('cfdi_uuid', $cfdi->uuid)
                ->first();
            if ($existingCompra) {
                return response()->json([
                    'success' => false,
                    'message' => "El CFDI ya esta vinculado a la compra #{$existingCompra->id}"
                ], 422);
            }

            if (!in_array($cfdi->tipo_comprobante, ['I', 'E'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El CFDI no es valido para compras. Solo se aceptan I (Ingreso) o E (Egreso).'
                ], 422);
            }

            if ($cfdi->direccion !== \App\Models\Cfdi::DIRECCION_RECIBIDO) {
                return response()->json([
                    'success' => false,
                    'message' => 'El CFDI no corresponde a recibidos y no puede importarse como compra.'
                ], 422);
            }

            // Buscar o crear proveedor
            $proveedor = Proveedor::firstOrCreate(
                ['rfc' => $cfdi->rfc_emisor],
                [
                    'nombre_razon_social' => $cfdi->nombre_emisor,
                    'empresa_id' => Auth::user()->empresa_id,
                    'activo' => true,
                ]
            );

            // Obtener almacen seleccionado
            $almacenId = $request->input('almacen_id');
            $almacen = $almacenId ? Almacen::find($almacenId) : Almacen::first();

            // Crear compra con transaccion
            $compra = DB::transaction(function () use ($cfdi, $proveedor, $almacen) {
                // Preparar conceptos para el servicio de inteligencia (Smart Matching)
                $parserService = app(\App\Services\CfdiXmlParserService::class);
                $cfdi->loadMissing('conceptos');

                // Convertir coleccion Eloquent a array compatible con el servicio
                $conceptosArray = $cfdi->conceptos->map(function ($c) {
                    return [
                        'no_identificacion' => $c->no_identificacion,
                        'descripcion' => $c->descripcion,
                        'clave_prod_serv' => $c->clave_prod_serv,
                        'cantidad' => $c->cantidad,
                        'valor_unitario' => $c->valor_unitario,
                        'importe' => $c->importe,
                        'descuento' => $c->descuento,
                        'unidad' => $c->unidad,
                        'clave_unidad' => $c->clave_unidad,
                    ];
                })->toArray();

                // Usar la misma logica inteligente que la importacion individual
                $conceptosMapeados = $parserService->mapConceptosToProductosBulk($conceptosArray);

                $itemsData = [];
                $allProductsFound = true;

                $productoIds = array_values(array_unique(array_filter(array_map(
                    fn($concepto) => $concepto['producto_id'] ?? null,
                    $conceptosMapeados
                ))));
                $productos = !empty($productoIds)
                    ? Producto::whereIn('id', $productoIds)->get()->keyBy('id')
                    : collect();

                foreach ($conceptosMapeados as $concepto) {
                    $producto = null;

                    if (!empty($concepto['producto_id'])) {
                        $producto = $productos->get($concepto['producto_id']);
                    }

                    if (!$producto) {
                        $allProductsFound = false;
                    }

                    $itemsData[] = [
                        'concepto' => $concepto,
                        'producto' => $producto,
                    ];
                }

                // Determinar estado y notas
                $estadoCompra = $allProductsFound ? EstadoCompra::Procesada->value : EstadoCompra::Borrador->value;

                $notas = "Importado automaticamente desde CFDI {$cfdi->serie}{$cfdi->folio}.";
                if (!$allProductsFound) {
                    $notas .= " [Pendiente: Requiere vinculacion manual de items]";
                } else {
                    $notas .= " [Vinculacion exitosa: Stock actualizado]";
                }

                $compra = Compra::create([
                    'empresa_id' => Auth::user()->empresa_id,
                    'proveedor_id' => $proveedor->id,
                    'almacen_id' => $almacen?->id,
                    'created_by' => Auth::id(),
                    'fecha_compra' => $cfdi->fecha_emision ?? now(),
                    'estado' => $estadoCompra,
                    'subtotal' => $cfdi->subtotal ?? 0,
                    'iva' => $cfdi->total_impuestos_trasladados ?? 0,
                    'total' => $cfdi->total ?? 0,
                    'moneda' => $cfdi->moneda ?? 'MXN',
                    'tipo_cambio' => $cfdi->tipo_cambio ?? 1,

                    // Datos CFDI
                    'cfdi_uuid' => $cfdi->uuid,
                    'cfdi_serie' => $cfdi->serie,
                    'cfdi_folio' => $cfdi->folio,
                    'cfdi_fecha' => $cfdi->fecha_emision,
                    'cfdi_emisor_rfc' => $cfdi->rfc_emisor,
                    'cfdi_emisor_nombre' => $cfdi->nombre_emisor,
                    'cfdi_total' => $cfdi->total,
                    'origen_importacion' => 'bulk_import',
                    'notas' => $notas,

                    'inventario_procesado' => $allProductsFound,
                ]);

                // Crear items y procesar inventario si aplica
                foreach ($itemsData as $item) {
                    $concepto = $item['concepto'];
                    $producto = $item['producto'];

                    $cantidad = $this->normalizeCantidad($concepto['cantidad'] ?? 1);
                    $precio = $concepto['valor_unitario'] ?? 0;
                    $importe = $concepto['importe'] ?? 0;
                    $descuento = $concepto['descuento'] ?? 0;
                    $descuentoMonto = $descuento > 0 ? min($descuento, $importe) : 0;

                    CompraItem::create([
                        'compra_id' => $compra->id,
                        'comprable_id' => $producto?->id,
                        'comprable_type' => $producto ? Producto::class : null,
                        'descripcion' => $concepto['descripcion'],
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'subtotal' => $importe,
                        'descuento' => $descuento,
                        'descuento_monto' => $descuentoMonto,
                        'unidad_medida' => $concepto['unidad_medida'] ?? ($concepto['unidad'] ?? 'PZA'),
                    ]);

                    if ($allProductsFound && $producto) {
                        if ($producto->precio_compra != $precio) {
                            $producto->update(['precio_compra' => $precio]);
                        }

                        $this->inventarioService->entrada($producto, $cantidad, [
                            'skip_transaction' => true,
                            'motivo' => 'Importacion masiva XML',
                            'almacen_id' => $compra->almacen_id,
                            'user_id' => Auth::id(),
                            'referencia_type' => 'App\\Models\\Compra',
                            'referencia_id' => $compra->id,
                            'detalles' => [
                                'compra_id' => $compra->id,
                                'producto_id' => $producto->id,
                                'precio_unitario' => $precio,
                            ]
                        ]);
                    }
                }

                if ($allProductsFound) {
                    $this->cuentasPagarService->crearCuentaPorPagar($compra, (float) $compra->total);
                }

                return $compra;
            });

            return response()->json([
                'success' => true,
                'message' => "Compra #{$compra->id} creada exitosamente",
                'compra_id' => $compra->id,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Error de BD en bulkImportSingle: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $isUniqueViolation = $e->getCode() === '23505'
                || ($e->getCode() === '23000' && ($e->errorInfo[1] ?? null) === 1062)
                || str_contains($e->getMessage(), 'compras_empresa_cfdi_uuid_unique');

            if ($isUniqueViolation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este CFDI ya fue importado para la empresa actual.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => "Error de base de datos: " . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error general en bulkImportSingle: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => "Error inesperado: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener conceptos de multiples CFDIs seleccionados
     */
    public function getCfdiConceptos(Request $request)
    {
        $request->validate([
            'cfdi_ids' => 'required|array',
            'cfdi_ids.*' => 'exists:cfdis,id'
        ]);

        $cfdis = \App\Models\Cfdi::with('conceptos')->whereIn('id', $request->cfdi_ids)->get();
        $parserService = app(\App\Services\CfdiXmlParserService::class);

        $conceptosParaMapear = [];

        foreach ($cfdis as $cfdi) {
            foreach ($cfdi->conceptos as $concepto) {
                $c = $concepto->toArray();
                $c['cfdi_folio'] = $cfdi->serie . $cfdi->folio;
                $c['cfdi_uuid'] = $cfdi->uuid;
                $c['cfdi_id'] = $cfdi->id;

                $c['descripcion'] = $concepto->descripcion;
                $c['no_identificacion'] = $concepto->no_identificacion;
                $c['clave_prod_serv'] = $concepto->clave_prod_serv;

                $conceptosParaMapear[] = $c;
            }
        }

        $conceptosMapeados = $parserService->mapConceptosToProductos($conceptosParaMapear);

        return response()->json([
            'success' => true,
            'conceptos' => $conceptosMapeados,
            'total_cfdis' => $cfdis->count(),
        ]);
    }

    /**
     * Obtener preview de un CFDI para revision antes de importar
     */
    public function getCfdiPreview(int $cfdiId)
    {
        $cfdi = \App\Models\Cfdi::with('conceptos')->findOrFail($cfdiId);

        if ($cfdi->direccion !== \App\Models\Cfdi::DIRECCION_RECIBIDO) {
            return response()->json([
                'success' => false,
                'message' => 'El CFDI no corresponde a recibidos.'
            ], 422);
        }

        $existingCompra = Compra::where('empresa_id', Auth::user()->empresa_id)
            ->where('cfdi_uuid', $cfdi->uuid)
            ->first();

        if ($existingCompra) {
            return response()->json([
                'success' => false,
                'message' => "Este CFDI ya esta vinculado a la compra #{$existingCompra->id}",
                'compra_id' => $existingCompra->id
            ], 422);
        }

        $parserService = app(\App\Services\CfdiXmlParserService::class);

        $conceptos = $cfdi->conceptos->map(fn($c) => [
            'no_identificacion' => $c->no_identificacion,
            'descripcion' => $c->descripcion,
            'clave_prod_serv' => $c->clave_prod_serv,
            'cantidad' => $c->cantidad,
            'valor_unitario' => $c->valor_unitario,
            'importe' => $c->importe,
            'descuento' => $c->descuento,
            'unidad' => $c->unidad,
            'clave_unidad' => $c->clave_unidad,
        ])->toArray();

        $conceptosMapeados = $parserService->mapConceptosToProductos($conceptos);

        $stats = [
            'mapeados' => 0,
            'similares' => 0,
            'no_encontrados' => 0,
        ];
        foreach ($conceptosMapeados as $c) {
            if ($c['match_type'] === 'exact')
                $stats['mapeados']++;
            elseif ($c['match_type'] === 'similar')
                $stats['similares']++;
            else
                $stats['no_encontrados']++;
        }

        $proveedor = Proveedor::where('rfc', $cfdi->rfc_emisor)
            ->where('activo', true)
            ->first();

        return response()->json([
            'success' => true,
            'cfdi_id' => $cfdi->id,
            'uuid' => $cfdi->uuid,
            'serie' => $cfdi->serie,
            'folio' => $cfdi->folio,
            'fecha' => $cfdi->fecha_emision,
            'subtotal' => $cfdi->subtotal,
            'total' => $cfdi->total,
            'descuento' => $cfdi->descuento ?? 0,
            'moneda' => $cfdi->moneda ?? 'MXN',
            'tipo_cambio' => $cfdi->tipo_cambio ?? 1,
            'emisor' => [
                'rfc' => $cfdi->rfc_emisor,
                'nombre' => $cfdi->nombre_emisor,
                'regimen_fiscal' => $cfdi->regimen_fiscal_emisor ?? null,
            ],
            'proveedor_encontrado' => $proveedor ? [
                'id' => $proveedor->id,
                'nombre_razon_social' => $proveedor->nombre_razon_social,
                'rfc' => $proveedor->rfc,
            ] : null,
            'conceptos' => $conceptosMapeados,
            'mapeo_stats' => $stats,
            'es_factura_valida' => in_array($cfdi->tipo_comprobante, ['I', 'E']),
            'tipo_comprobante' => $cfdi->tipo_comprobante,
            'tipo_comprobante_nombre' => $cfdi->tipo_comprobante === 'I' ? 'Ingreso (Factura)' : ($cfdi->tipo_comprobante === 'E' ? 'Egreso (Nota de Credito)' : 'Otro'),
            'impuestos' => [
                'total_impuestos_trasladados' => $cfdi->total_impuestos_trasladados ?? 0,
            ],
        ]);
    }

    private function normalizeCantidad(mixed $cantidad): int
    {
        if (is_string($cantidad)) {
            $cantidad = str_replace(',', '', trim($cantidad));
        }

        if (!is_numeric($cantidad)) {
            throw new \Exception('Cantidad invalida: se esperaba un numero.');
        }

        $valor = (float) $cantidad;
        $redondeado = round($valor);

        if (abs($valor - $redondeado) > 0.0001) {
            throw new \Exception('Cantidad invalida: se requiere un entero para compras. Valor recibido: ' . $valor);
        }

        return (int) $redondeado;
    }
}
