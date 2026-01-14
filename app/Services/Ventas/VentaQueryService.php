<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Almacen;
use App\Models\PriceList;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Models\SatEstado;
use App\Models\User;
use App\Models\Pedido;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class VentaQueryService
{
    public function getVentasList(Request $request): array
    {
        $query = Venta::with(['cliente', 'almacen', 'items.ventable', 'items.series.almacen', 'createdBy', 'updatedBy', 'cuentaBancaria', 'entregaDinero.cuentaBancaria', 'cfdis']);

        // Aplicar filtros
        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $searchPattern = '%' . $search . '%';
                $q->whereHas('cliente', function ($clienteQuery) use ($searchPattern) {
                    $clienteQuery->where('nombre_razon_social', 'LIKE', $searchPattern);
                })->orWhere('numero_venta', 'LIKE', $searchPattern);
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

        return [
            'ventas' => $ventas,
            'estadisticas' => $estadisticas,
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
                        'almacen' => $s->almacen ? $s->almacen->nombre : 'N/A'
                    ])->toArray();
                }

                $items->push([
                    'id' => $item->ventable_id,
                    'nombre' => $ventable->nombre ?? $ventable->descripcion ?? 'N/A',
                    'tipo' => $item->ventable_type === Producto::class ? 'producto' : 'servicio',
                    'requiere_serie' => $ventable->requiere_serie ?? false,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'series' => $series,
                ]);
            }
        }

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
            'estado' => $venta->estado?->value ?? 'desconocido',
            'pagado' => $venta->pagado ?? false,
            'fecha' => $venta->fecha->toISOString(),
            'esta_facturada' => $venta->cfdis->whereIn('estatus', ['timbrado', 'vigente'])->isNotEmpty(),
            'cfdi_cancelado' => $venta->cfdis->where('estatus', 'cancelado')->isNotEmpty(),
            'factura_uuid' => $venta->cfdis->whereIn('estatus', ['timbrado', 'vigente'])->last()?->uuid,
        ];
    }

    private function getVentasSummaryStats(): array
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

    public function getCreateData(Request $request): array
    {
        $clientes = Cliente::select('id', 'nombre_razon_social', 'rfc', 'email', 'price_list_id', 'tipo_persona', 'credito_activo', 'limite_credito')
            ->whereNotNull('nombre_razon_social')
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->with('priceList:id,nombre,clave')
            ->get();

        $productos = Producto::select('id', 'nombre', 'codigo', 'precio_venta', 'stock', 'categoria_id', 'marca_id', 'requiere_serie', 'tipo_producto')
            ->with(['categoria:id,nombre', 'marca:id,nombre', 'precios', 'kitItems.item:id,nombre,codigo'])
            ->where('estado', 'activo')
            ->where(fn($q) => $q->where('stock', '>', 0)->orWhere('tipo_producto', 'kit'))
            ->orderBy('nombre')
            ->limit(1000)
            ->get()
            ->map(function ($p) {
                $p->precios_listas = $p->precios->mapWithKeys(fn($pr) => [$pr->price_list_id => (float) $pr->precio]);
                unset($p->precios);
                return $p;
            });

        $servicios = Servicio::select('id', 'nombre', 'descripcion', 'precio', 'comision_vendedor')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->limit(500)
            ->get();

        $almacenes = Almacen::select('id', 'nombre', 'descripcion', 'ubicacion', 'estado')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $catalogs = Cache::remember('ventas_catalogs', 604800, fn() => [
            'regimenes_fiscales' => SatRegimenFiscal::select('clave', 'descripcion')->get(),
            'usos_cfdi' => SatUsoCfdi::select('clave', 'descripcion')->get(),
            'estados' => SatEstado::select('clave', 'nombre')->get(),
        ]);

        $vendedores = User::select('id', 'name', 'email')
            ->where('activo', true)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['ventas', 'admin']))
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'type' => 'user', 'nombre' => $u->name]);

        return [
            'clientes' => $clientes,
            'productos' => $productos,
            'servicios' => $servicios,
            'almacenes' => $almacenes,
            'priceLists' => PriceList::activas()->select('id', 'nombre')->get(),
            'catalogs' => $catalogs,
            'user' => Auth::user(),
            'pedido' => $request->has('pedido_id') ? Pedido::with(['cliente', 'items.pedible'])->find($request->pedido_id) : null,
            'cita' => $request->has('cita_id') ? \App\Models\Cita::with(['cliente', 'items.citable'])->find($request->cita_id) : null,
            'vendedores' => $vendedores,
            'defaults' => [
                'ivaPorcentaje' => (float) EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => EmpresaConfiguracionService::getIsrPorcentaje(),
                'enableIsr' => EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'retencionIvaDefault' => EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrDefault' => EmpresaConfiguracionService::getRetencionIsrDefault(),
            ],
        ];
    }

    public function getVentaDetails(Venta $venta): array
    {
        $venta->load(['cliente', 'almacen', 'items.ventable', 'items.series.almacen', 'vendedor', 'createdBy', 'updatedBy', 'cuentaPorCobrar', 'cfdis']);

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
                    'almacen' => $s->almacen ? $s->almacen->nombre : 'N/A'
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
                'fecha_pago' => $venta->fecha_pago,
                'notas_pago' => $venta->notas_pago,
                'notas' => $venta->notas,
                'fecha' => $venta->fecha,
                'created_at' => $venta->created_at,
                'hasPagos' => $venta->cuentaPorCobrar && $venta->cuentaPorCobrar->monto_pagado > 0,
                'esta_facturada' => $venta->cfdis()->timbrados()->exists(),
                'factura_uuid' => $venta->cfdis()->timbrados()->latest()->first()?->uuid,
                'factura' => $venta->cfdis()->timbrados()->latest()->first(),
            ],
            'canEdit' => !$venta->pagado && $venta->estado?->value !== 'cancelada',
            'canDelete' => $venta->estado?->value === 'cancelada',
            'canCancel' => $venta->estado?->value !== 'cancelada' && (!$venta->pagado || $isAdmin),
            'isAdmin' => $isAdmin,
            'cuentasBancarias' => \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
        ];
    }

    public function getVentaEditData(Venta $venta): array
    {
        $venta->load(['cliente']);
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
            'venta' => [
                'id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'cliente' => $venta->cliente ?? ['id' => null, 'nombre_razon_social' => 'Público en general'],
                'items' => $items,
                'total' => $venta->total,
                'estado' => $venta->estado,
                'pagado' => $venta->pagado ?? false,
                'fecha' => $venta->fecha->toISOString(),
                'almacen_id' => $venta->almacen_id,
            ]
        ]);
    }
}
