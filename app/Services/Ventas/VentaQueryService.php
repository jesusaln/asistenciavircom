<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Almacen;
use App\Models\PriceList;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Models\SatEstado;
use App\Models\SatFormaPago;
use App\Models\User;
use App\Models\Pedido;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\TallerOrden;

class VentaQueryService
{
    /**
     * Empresa para listas (usuarios, cuentas): resolver + fallback al usuario web.
     */
    private function empresaIdParaListas(): ?int
    {
        $id = \App\Support\EmpresaResolver::resolveId();
        if ($id) {
            return (int) $id;
        }

        $uid = Auth::id();
        if ($uid) {
            $eid = User::query()->whereKey($uid)->value('empresa_id');
            if ($eid) {
                return (int) $eid;
            }
        }

        return null;
    }

    public function getVentasList(Request $request): array
    {
        $query = Venta::with(['cliente', 'almacen', 'items.ventable', 'items.series.almacen', 'items.series.productoSerie.producto', 'vendedor', 'createdBy', 'updatedBy', 'cuentaBancaria', 'entregaDinero.cuentaBancaria', 'cfdis']);

        // Aplicar filtros
        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $searchPattern = '%' . $search . '%';
                $q->whereHas('cliente', function ($clienteQuery) use ($search, $searchPattern) {
                    // Accent-insensitive search for client name
                    $clienteQuery->whereRaw("unaccent(nombre_razon_social) ILIKE unaccent(?)", [$searchPattern]);
                })->orWhere('numero_venta', 'ILIKE', $searchPattern);
            });
        }

        if ($request->filled('estado')) {
            $estado = strtolower(trim($request->estado));
            $query->whereRaw('LOWER(estado) = ?', [$estado]);
        }

        if ($request->filled('cfdi')) {
            $cfdiFilter = strtolower(trim($request->cfdi));
            if ($cfdiFilter === 'timbrada') {
                $query->whereHas('cfdis', function ($q) {
                    $q->whereIn('estatus', ['timbrado', 'vigente']);
                });
            } elseif ($cfdiFilter === 'sin_timbrar') {
                $query->whereDoesntHave('cfdis', function ($q) {
                    $q->whereIn('estatus', ['timbrado', 'vigente']);
                });
            }
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $ventas = $query->orderBy('created_at', 'desc')->paginate($perPage)->appends($request->query());

        $ventas->getCollection()->transform(fn($v) => $this->transformVentaForList($v));

        $estadisticas = $this->getVentasSummaryStats();

        $empresaId = $this->empresaIdParaListas();
        $usuariosCobro = $empresaId
            ? User::query()
                ->where('empresa_id', $empresaId)
                ->where('activo', true)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return [
            'ventas' => $ventas,
            'estadisticas' => $estadisticas,
            'cuentasBancarias' => \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
            'usuariosCobro' => $usuariosCobro,
            'pagination' => [
                'current_page' => $ventas->currentPage(),
                'last_page' => $ventas->lastPage(),
                'per_page' => $ventas->perPage(),
                'from' => $ventas->firstItem(),
                'to' => $ventas->lastItem(),
                'total' => $ventas->total(),
            ],
            'filters' => $request->only(['search', 'estado', 'cfdi']),
            'sorting' => ['sort_by' => 'created_at', 'sort_direction' => 'desc'],
        ];
    }

    private function transformVentaForList(Venta $venta): array
    {
        $items = collect();
        foreach ($venta->items as $item) {
            $ventable = $item->ventable;
            if ($ventable) {
                $series = [];
                if ($item->series && $item->series->count() > 0) {
                    $series = $item->series->map(fn($s) => [
                        'numero_serie' => $s->numero_serie,
                        'almacen' => $s->almacen ? $s->almacen->nombre : 'N/A',
                        'componente_nombre' => $s->productoSerie?->producto?->nombre
                    ])->toArray();
                }

                $items->push([
                    'id' => $item->ventable_id,
                    'nombre' => $ventable->nombre ?? $ventable->descripcion ?? 'N/A',
                    'tipo' => ($item->ventable_type === 'producto' || $item->ventable_type === Producto::class) ? 'producto' : 'servicio',
                    'requiere_serie' => $ventable->requiere_serie ?? false,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento ?? 0,
                    'series' => $series,
                ]);
            }
        }

        $cuentaBancaria = $venta->cuentaBancaria;

        return [
            'id' => $venta->id,
            'numero_venta' => $venta->numero_venta,
            'cliente' => $venta->cliente ?? [
                'id' => null,
                'nombre_razon_social' => 'Público en general',
                'rfc' => 'XAXX010101000',
            ],
            'almacen' => $venta->almacen,
            'items' => $items,
            'total' => $venta->total,
            'subtotal' => $venta->subtotal,
            'iva' => $venta->iva,
            'estado' => $venta->estado?->value ?? 'desconocido',
            'pagado' => $venta->pagado ?? false,
            'metodo_pago' => $venta->metodo_pago,
            'forma_pago_sat' => $venta->forma_pago_sat,
            'metodo_pago_sat' => $venta->metodo_pago_sat,
            'cuenta_bancaria' => $cuentaBancaria ? [
                'id' => $cuentaBancaria->id,
                'nombre' => $cuentaBancaria->nombre,
                'banco' => $cuentaBancaria->banco,
            ] : null,
            'vendedor' => $venta->vendedor ? [
                'id' => $venta->vendedor_id,
                'name' => $venta->vendedor->name ?? $venta->vendedor->nombre ?? 'N/A',
                'type' => $venta->vendedor_type
            ] : null,
            'fecha' => $venta->fecha ? $venta->fecha->toIso8601String() : null,
            'fecha_pago' => $venta->fecha_pago ? $venta->fecha_pago->toIso8601String() : null,
            'created_at' => $venta->created_at ? $venta->created_at->toIso8601String() : null,
            'updated_at' => $venta->updated_at ? $venta->updated_at->toIso8601String() : null,
            'created_by_user_name' => $venta->createdBy->name ?? 'N/A',
            'updated_by_user_name' => $venta->updatedBy->name ?? 'N/A',
            'esta_facturada' => $venta->cfdis->whereIn('estatus', ['timbrado', 'vigente'])->isNotEmpty(),
            'cfdi_cancelado' => $venta->cfdis->where('estatus', 'cancelado')->isNotEmpty(),
            'factura_uuid' => $venta->cfdis->whereIn('estatus', ['timbrado', 'vigente'])->last()?->uuid,
            'sharing_token' => $venta->sharing_token,
            'tiene_entrega_dinero' => $venta->entregaDinero !== null,
            'entrega_dinero_estado' => $venta->entregaDinero?->estado,
        ];
    }

    private function getVentasSummaryStats(): array
    {
        $empresaId = \App\Support\EmpresaResolver::resolveId();
        
        // ✅ FIX (A-04): Ensure we have an empresa_id for the cache key to avoid cross-tenant leakage
        if (!$empresaId) {
            return $this->calculateVentasSummaryStats();
        }

        $connection = config('database.default');
        $cacheKey = "ventas_summary_stats_{$connection}_empresa_{$empresaId}";

        return Cache::remember($cacheKey, 300, function () {
            return $this->calculateVentasSummaryStats();
        });
    }

    private function calculateVentasSummaryStats(): array
    {
        $stats = Venta::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN estado = 'borrador' THEN 1 END) as borrador,
            COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
            COUNT(CASE WHEN estado = 'aprobada' THEN 1 END) as aprobadas,
            COUNT(CASE WHEN estado = 'enviada' THEN 1 END) as enviadas,
            COUNT(CASE WHEN estado = 'facturada' THEN 1 END) as facturadas,
            COUNT(CASE WHEN estado = 'pagado' THEN 1 END) as pagadas,
            COUNT(CASE WHEN estado = 'cancelada' THEN 1 END) as cancelada
        ")->first();

        return [
            'total' => (int) $stats->total,
            'borrador' => (int) $stats->borrador,
            'pendientes' => (int) $stats->pendientes,
            'aprobadas' => (int) $stats->aprobadas,
            'enviadas' => (int) $stats->enviadas,
            'facturadas' => (int) $stats->facturadas,
            'pagadas' => (int) $stats->pagadas,
            'cancelada' => (int) $stats->cancelada,
        ];
    }

    /**
     * ✅ FIX (A-03): Invalidate stats cache
     */
    public static function invalidateStatsCache(?int $empresaId = null): void
    {
        $empresaId = $empresaId ?? \App\Support\EmpresaResolver::resolveId();
        $connection = config('database.default');
        $cacheKey = "ventas_summary_stats_{$connection}_empresa_{$empresaId}";
        Cache::forget($cacheKey);
        
        Log::info("VentaQueryService: Invalidadas estadísticas de ventas para empresa #{$empresaId}");
    }

    /**
     * Ventas recientes del mismo cliente que la cita (modal en reportes, etc.).
     *
     * @return list<array<string, mixed>>
     */
    public function getVentasClienteCandidatasForCita(Cita $cita): array
    {
        if (! $cita->cliente_id) {
            return [];
        }

        return Venta::query()
            ->where('cliente_id', $cita->cliente_id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(80)
            ->get(['id', 'numero_venta', 'total', 'fecha', 'cita_id', 'metodo_pago', 'pagado', 'estado'])
            ->map(function (Venta $v) {
                $estado = $v->estado;
                if ($estado instanceof \BackedEnum) {
                    $estado = $estado->value;
                }

                return [
                    'id' => $v->id,
                    'numero_venta' => $v->numero_venta,
                    'total' => (float) $v->total,
                    'fecha' => $v->fecha?->toIso8601String(),
                    'cita_id' => $v->cita_id,
                    'metodo_pago' => $v->metodo_pago,
                    'pagado' => (bool) $v->pagado,
                    'estado' => $estado,
                ];
            })
            ->all();
    }

    public function getCreateData(Request $request): array
    {
        $startTime = microtime(true);
        Log::info('VentaQueryService: Iniciando carga de datos para crear venta');

        $clienteSelect = ['id', 'nombre_razon_social', 'rfc', 'email', 'price_list_id', 'tipo_persona', 'credito_activo', 'limite_credito'];

        $citaParaVenta = $request->filled('cita_id')
            ? \App\Models\Cita::with(['cliente', 'items.citable', 'venta:id,cita_id,numero_venta,total,fecha,cliente_id'])->find($request->cita_id)
            : null;

        $tallerParaVenta = $request->filled('taller_id')
            ? TallerOrden::with(['cliente'])->find($request->taller_id)
            : null;

        $connection = config('database.default');
        $empresaId = $this->empresaIdParaListas();
        
        // OPTIMIZACIÓN: Cargar clientes de forma más eficiente
        // ✅ FIX (A-04): Secure cache key with mandatory empresaId
        $cacheKey = $empresaId 
            ? "clientes_venta_directa_{$connection}_{$empresaId}"
            : "clientes_venta_directa_{$connection}_anonymous_" . session()->getId();

        $clientes = Cache::remember($cacheKey, 300, function () use ($clienteSelect) {
            return Cliente::select($clienteSelect)
                ->orderBy('nombre_razon_social')
                ->limit(50)
                ->with('priceList:id,nombre,clave')
                ->get();
        });

        if ($citaParaVenta?->cliente_id && ! $clientes->contains(fn ($c) => (int) $c->id === (int) $citaParaVenta->cliente_id)) {
            $clienteCita = Cliente::select($clienteSelect)
                ->whereKey($citaParaVenta->cliente_id)
                ->with('priceList:id,nombre,clave')
                ->first();
            if ($clienteCita) {
                $clientes = $clientes->prepend($clienteCita)->values();
            }
        }

        if ($tallerParaVenta?->cliente_id && ! $clientes->contains(fn ($c) => (int) $c->id === (int) $tallerParaVenta->cliente_id)) {
            $clienteTaller = Cliente::select($clienteSelect)
                ->whereKey($tallerParaVenta->cliente_id)
                ->with('priceList:id,nombre,clave')
                ->first();
            if ($clienteTaller) {
                $clientes = $clientes->prepend($clienteTaller)->values();
            }
        }

        // OPTIMIZACIÓN: Cargar productos de forma más eficiente para evitar N+1 queries
        $productosQuery = Producto::select('id', 'nombre', 'codigo', 'precio_venta', 'stock', 'categoria_id', 'marca_id', 'requiere_serie', 'tipo_producto', 'unidad_medida', 'sat_clave_unidad', 'reservado', 'bloquear_venta_directa')
            ->with([
                'categoria:id,nombre',
                'marca:id,nombre',
                // Cargar solo precios básicos inicialmente (lazy load details si es necesario)
                'precios' => function($q) {
                    $q->select('id', 'producto_id', 'price_list_id', 'precio');
                }
            ])
            ->where('estado', 'activo')
            ->paraVentaDirectaSegunUsuario(Auth::user())
            ->orderBy('nombre')
            ->limit(100); // Reducido de 300 a 100 para carga instantánea

        $productos = $productosQuery->get()->map(function ($p) {
            // Mapear precios por lista (solo si tiene precios)
            $p->precios_listas = $p->precios ? $p->precios->mapWithKeys(fn($pr) => [$pr->price_list_id => (float) $pr->precio]) : collect();

            // Stock simplificado: usar stock global por ahora, lazy load por almacén si es necesario
            $p->stock_total = max(0, (float) $p->stock - (float) ($p->reservado ?? 0));

            // Remover datos pesados que no se necesitan inicialmente
            unset($p->precios, $p->stock);
            return $p;
        });

        // Cargar stock por almacén de forma separada y cacheada (lazy load)
        $productoIds = $productos->pluck('id');
        $stockPorAlmacen = Cache::remember("productos_stock_{$connection}_{$this->empresaIdParaListas()}", 300, function() use ($productoIds) {
            return \DB::table('inventarios')
                ->whereIn('producto_id', $productoIds)
                ->where('cantidad', '>', 0)
                ->select('producto_id', 'almacen_id', 'cantidad')
                ->get()
                ->groupBy('producto_id')
                ->map(function($inventarios) {
                    return $inventarios->mapWithKeys(fn($inv) => [$inv->almacen_id => (float) $inv->cantidad]);
                });
        });

        // Asignar stock por almacén a productos
        $productos->each(function($producto) use ($stockPorAlmacen) {
            $producto->stock_almacenes = $stockPorAlmacen->get($producto->id, collect());
        });

        $empresaId = $this->empresaIdParaListas();

        // OPTIMIZACIÓN: Cachear servicios activos
        $servicios = Cache::remember("ventas_servicios_activos_{$connection}_{$empresaId}", 1800, function() {
            return Servicio::select('id', 'nombre', 'descripcion', 'precio', 'comision_vendedor', 'estado')
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->limit(50) // Reducido de 100 a 50
                ->get();
        });

        // OPTIMIZACIÓN: Cachear almacenes activos
        $almacenes = Cache::remember("ventas_almacenes_activos_{$connection}_{$empresaId}", 3600, function() {
            return Almacen::select('id', 'nombre', 'descripcion', 'ubicacion', 'estado')
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->get();
        });

        $catalogs = Cache::remember("ventas_catalogs_{$connection}_{$empresaId}", 604800, fn() => [
            'regimenes_fiscales' => SatRegimenFiscal::select('clave', 'descripcion')->get(),
            'usos_cfdi' => SatUsoCfdi::select('clave', 'descripcion')->get(),
            'formas_pago' => SatFormaPago::select('clave', 'descripcion')->get(),
            'metodos_pago' => ['PUE' => 'Pago en una sola exhibición', 'PPD' => 'Pago en parcialidades o diferido'],
            'estados' => SatEstado::select('clave', 'nombre')->get(),
        ]);

        // OPTIMIZACIÓN: Cachear vendedores activos con mejor query
        $vendedores = Cache::remember("ventas_vendedores_{$connection}_{$empresaId}", 1800, function() {
            return User::select('id', 'name', 'email', 'almacen_venta_id', 'almacen_compra_id', 'es_tecnico', 'es_vendedor')
                ->where('activo', true)
                ->where(function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->whereIn('name', [
                        'ventas', 'admin', 'tecnico', 'vendedor', 'cajero',
                        'cobranza', 'almacenista', 'super-admin',
                    ]))
                        ->orWhere('es_tecnico', true)
                        ->orWhere('es_vendedor', true);
                })
                ->with(['roles:id,name']) // Eager load roles para evitar N+1
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'type' => 'user',
                    'nombre' => $u->name,
                    'almacen_venta_id' => $u->almacen_venta_id,
                    'almacen_compra_id' => $u->almacen_compra_id,
                ]);
        });

        $result = [
            'clientes' => $clientes,
            'productos' => $productos,
            'servicios' => $servicios,
            'almacenes' => $almacenes,
            'priceLists' => Cache::remember("ventas_price_lists_activas_{$connection}_{$empresaId}", 3600, function() {
                return PriceList::activas()->select('id', 'nombre')->get();
            }),
            'catalogs' => $catalogs,
            'user' => Auth::user(),
            'pedido' => $request->has('pedido_id') ? Pedido::with(['cliente', 'items.pedible'])->find($request->pedido_id) : null,
            'cita' => $citaParaVenta,
            'taller' => $tallerParaVenta,
            'vendedores' => $vendedores,
            'puedeVenderComponentesSueltos' => Auth::user()?->can('venta componentes sueltos') ?? false,
            'defaults' => [
                'ivaPorcentaje' => (float) EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => EmpresaConfiguracionService::getIsrPorcentaje(),
                'enableIsr' => EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'retencionIvaDefault' => EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => EmpresaConfiguracionService::getRetencionIsrDefault(),
                'serviciosUsanListasPrecios' => (bool) config('ventas.servicios_usan_listas_precios', false),
            ],
        ];

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en milisegundos

        Log::info('VentaQueryService: Datos cargados exitosamente', [
            'duration_ms' => $duration,
            'clientes_count' => $clientes->count(),
            'productos_count' => $productos->count(),
            'servicios_count' => $servicios->count(),
            'almacenes_count' => $almacenes->count(),
            'vendedores_count' => $vendedores->count(),
        ]);

        return $result;
    }

    /**
     * Limpiar cache de datos de ventas (llamar cuando se modifiquen productos, clientes, etc.)
     */
    public function clearCreateDataCache(): void
    {
        $empresaId = $this->empresaIdParaListas();

        Cache::forget("ventas_clientes_{$empresaId}");
        Cache::forget("clientes_venta_directa_{$empresaId}");
        Cache::forget("ventas_vendedores_{$empresaId}");
        Cache::forget("productos_stock_{$empresaId}");
        Cache::forget("ventas_servicios_activos_{$empresaId}");
        Cache::forget("ventas_almacenes_activos_{$empresaId}");
        Cache::forget("ventas_price_lists_activas_{$empresaId}");
        Cache::forget("ventas_catalogs_{$empresaId}");

        Log::info('VentaQueryService: Cache limpiado para datos de creación de ventas');
    }

    /**
     * Invalidar caché de estadísticas de ventas (llamar tras crear/cancelar/eliminar ventas).
     */
    public function invalidateSummaryStatsCache(): void
    {
        $empresaId = \App\Support\EmpresaResolver::resolveId();
        $connection = config('database.default');
        $cacheKey = "ventas_summary_stats_{$connection}_empresa_{$empresaId}";
        Cache::forget($cacheKey);
    }

    public function getVentaDetails(Venta $venta): array
    {
        $venta->load(['cliente', 'almacen', 'items.ventable', 'items.series.almacen', 'items.series.productoSerie.producto', 'vendedor', 'createdBy', 'updatedBy', 'cuentaPorCobrar', 'cfdis', 'entregaDinero', 'cuentaBancaria']);

        $productos = [];
        foreach ($venta->items as $item) {
            $ventable = $item->ventable;
            if (!$ventable)
                continue;

            $isProducto = $item->ventable_type === Producto::class;
            $productos[] = [
                'id' => $ventable->id,
                'nombre' => $ventable->nombre ?? $ventable->descripcion ?? 'N/A',
                'tipo' => $isProducto ? 'producto' : 'servicio',
                'almacen_nombre' => $venta->almacen?->nombre ?? 'N/A',
                'requiere_serie' => $isProducto ? ($ventable->requiere_serie ?? false) : false,
                'pivot' => [
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento ?? 0,
                    'subtotal' => $item->subtotal ?? ($item->cantidad * $item->precio * (1 - ($item->descuento ?? 0) / 100)),
                    'descuento_monto' => $item->descuento_monto ?? (($item->precio * $item->cantidad) * (($item->descuento ?? 0) / 100)),
                ],
                'series' => $item->series ? $item->series->map(fn($s) => [
                    'numero_serie' => $s->numero_serie,
                    'almacen' => $s->almacen ? $s->almacen->nombre : 'N/A',
                    'componente_nombre' => $s->productoSerie?->producto?->nombre
                ])->toArray() : []
            ];
        }

        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasAnyRole(['admin', 'super-admin']);

        return [
            'venta' => [
                'id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'cliente' => $venta->cliente ?? (object) ['id' => null, 'nombre_razon_social' => 'Sin cliente'],
                'almacen' => $venta->almacen,
                'vendedor' => $venta->vendedor ? ['id' => $venta->vendedor_id, 'nombre' => $venta->vendedor->name ?? $venta->vendedor->nombre ?? 'N/A'] : null,
                'productos' => $productos,
                'subtotal' => $venta->subtotal,
                'descuento_general' => $venta->descuento_general ?? 0,
                'iva' => $venta->iva,
                'isr' => $venta->isr ?? 0,
                'total' => $venta->total,
                'estado' => $venta->estado?->value ?? 'desconocido',
                'pagado' => $venta->pagado ?? false,
                'metodo_pago' => $venta->metodo_pago,
                'forma_pago_sat' => $venta->forma_pago_sat,
                'metodo_pago_sat' => $venta->metodo_pago_sat,
                'cuenta_bancaria' => $venta->cuentaBancaria ? [
                    'id' => $venta->cuentaBancaria->id,
                    'nombre' => $venta->cuentaBancaria->nombre,
                    'banco' => $venta->cuentaBancaria->banco,
                ] : null,
                'fecha_pago' => $venta->fecha_pago,
                'notas_pago' => $venta->notas_pago,
                'notas' => $venta->notas,
                'fecha' => $venta->fecha,
                'created_at' => $venta->created_at,
                'hasPagos' => $venta->cuentaPorCobrar && $venta->cuentaPorCobrar->monto_pagado > 0,
                'esta_facturada' => $venta->cfdis()->timbrados()->exists(),
                'factura_uuid' => $venta->cfdis()->timbrados()->latest()->first()?->uuid,
                'factura' => $venta->cfdis()->timbrados()->latest()->first(),
                'tiene_entrega_dinero' => $venta->entregaDinero !== null,
                'entrega_dinero' => $venta->entregaDinero ? [
                    'id' => $venta->entregaDinero->id,
                    'fecha_entrega' => $venta->entregaDinero->fecha_entrega?->toIso8601String(),
                    'fecha_recibido' => $venta->entregaDinero->fecha_recibido?->toIso8601String(),
                    'monto_efectivo' => $venta->entregaDinero->monto_efectivo,
                    'estado' => $venta->entregaDinero->estado,
                    'recibido_por_nombre' => $venta->entregaDinero->recibidoPor?->name ?? 'N/A',
                ] : null,
            ],
            'canEdit' => $venta->estado?->value !== 'cancelada' && $venta->entregaDinero === null,
            'canDelete' => $venta->estado?->value === 'cancelada',
            'canCancel' => $venta->estado?->value !== 'cancelada',
            'isAdmin' => $isAdmin,
            'cuentasBancarias' => \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
            'usuariosCobro' => ($eid = $this->empresaIdParaListas())
                ? User::query()->where('empresa_id', $eid)->where('activo', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }

    public function getVentaEditData(Venta $venta): array
    {
        $venta->load(['cliente', 'vendedor', 'cuentaBancaria']);
        $productosItems = $venta->items()->where('ventable_type', Producto::class)->with(['ventable', 'series'])->get();
        $serviciosItems = $venta->items()->where('ventable_type', Servicio::class)->with('ventable')->get();

        $items = collect($productosItems->map(fn($item) => [
            'ventable_type' => $item->ventable_type,
            'ventable' => $item->ventable,
            'cantidad' => $item->cantidad,
            'precio' => $item->precio,
            'descuento' => $item->descuento,
            'series' => $item->series->pluck('numero_serie')->toArray(),
        ]))->merge(collect($serviciosItems->map(fn($item) => [
                        'ventable_type' => $item->ventable_type,
                        'ventable' => $item->ventable,
                        'cantidad' => $item->cantidad,
                        'precio' => $item->precio,
                        'descuento' => $item->descuento,
                    ])));

        $createData = $this->getCreateData(request());

        return array_merge($createData, [
            'cuentasBancarias' => \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
            'venta' => [
                'id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'cliente' => $venta->cliente ?? ['id' => null, 'nombre_razon_social' => 'Público en general'],
                'items' => $items,
                'total' => $venta->total,
                'estado' => $venta->estado,
                'pagado' => $venta->pagado ?? false,
                'fecha' => $venta->fecha->toIso8601String(),
                'almacen_id' => $venta->almacen_id,
                'metodo_pago' => $venta->metodo_pago ?? 'efectivo',
                'forma_pago_sat' => $venta->forma_pago_sat,
                'metodo_pago_sat' => $venta->metodo_pago_sat,
                'cuenta_bancaria_id' => $venta->cuenta_bancaria_id,
                'descuento_general' => $venta->descuento_general ?? 0,
                'notas' => $venta->notas ?? '',
                'tiene_entrega_dinero' => $venta->entregaDinero()->exists(),
                'vendedor_id' => $venta->vendedor_id,
                'vendedor_type' => $venta->vendedor_type,
                'pagado_por' => $venta->pagado_por,
            ]
        ]);
    }
}
