<?php

namespace App\Services\Cobros;

use App\Enums\EstadoVenta;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\Compra;
use App\Models\EntregaDinero;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Misma lógica que el resumen «Mi corte» (API / app) para no duplicar reglas al declarar entregas de efectivo.
 */
class MiCorteCobrosCalculator
{
    /**
     * @return array{
     *   ventas: Collection<int, Venta>,
     *   ventas_count: int,
     *   ventas_periodo_cobradas: int,
     *   total_general: float,
     *   por_metodo: array<string, float>,
     *   efectivo_a_entregar: float,
     *   descuentos_partidas: float,
     *   descuento_general: float,
     *   total_descuentos: float,
     *   efectivo_generado: float,
     *   ya_entregado: float,
     * }
     */
    public function resumenParaUsuario(?int $userId, Carbon $desde, Carbon $hasta): array
    {
        $candidatas = Venta::query()
            ->where('pagado', true)
            ->where('estado', '!=', EstadoVenta::Cancelada)
            ->whereBetween('created_at', [$desde, $hasta])
            // Solo ventas que NO tengan una entrega de dinero pendiente o recibida
            ->whereDoesntHave('entregas', function($q) {
                $q->whereIn('estado', ['pendiente', 'recibido']);
            })
            ->with(['vendedor', 'cliente:id,nombre_razon_social'])
            ->get([
                'id', 'numero_venta', 'total', 'metodo_pago', 'fecha_pago', 'created_at',
                'pagado_por', 'created_by', 'vendedor_id', 'vendedor_type', 'descuento_general', 'cliente_id',
            ]);

        $ventas = $candidatas->filter(function (Venta $v) use ($userId) {
            if ($userId === null) return true;
            return $this->ventaCuentaParaResumenCobros($v, $userId);
        })->values();

        $ventaIds = $ventas->pluck('id')->all();
        $descuentosPartidas = 0.0;
        if ($ventaIds !== []) {
            $descuentosPartidas = $this->sumDescuentosPartidasVentaItems($ventaIds);
        }
        $ventasPeriodoCobradas = $candidatas->count();
        $descuentoGeneralSum = round((float) $ventas->sum(fn (Venta $v) => (float) ($v->descuento_general ?? 0)), 2);
        $descuentosPartidas = round($descuentosPartidas, 2);
        $totalDescuentos = round($descuentosPartidas + $descuentoGeneralSum, 2);

        $porMetodo = [
            'efectivo' => 0.0,
            'transferencia' => 0.0,
            'tarjetas' => 0.0,
            'cheque' => 0.0,
            'credito' => 0.0,
            'otros' => 0.0,
        ];

        foreach ($ventas as $v) {
            $bucket = $this->bucketMetodoPago($v->metodo_pago);
            $porMetodo[$bucket] = ($porMetodo[$bucket] ?? 0) + (float) $v->total;
        }

        foreach ($porMetodo as $k => $v) {
            $porMetodo[$k] = round($v, 2);
        }

        $totalGeneral = round(array_sum($porMetodo), 2);

        $efectivoGenerado = $porMetodo['efectivo'];

        // Gastos pagados en efectivo por el usuario en el periodo
        $gastosQuery = Compra::query()
            ->where('tipo', 'gasto')
            ->where('metodo_pago', 'efectivo')
            ->when($userId, fn($q) => $q->where('created_by', $userId))
            ->whereBetween('fecha_compra', [$desde, $hasta])
            ->where('estado', '!=', 'cancelado');

        $gastosEfectivo = (float) $gastosQuery->sum('total');
        $gastosDetalle = $gastosQuery->with('categoriaGasto:id,nombre')->get(['id', 'numero_compra', 'total', 'fecha_compra', 'notas', 'categoria_gasto_id']);

        $tag = 'PERIODO:'.$desde->format('Y-m-d').'|'.$hasta->format('Y-m-d');
        $yaEntregado = (float) EntregaDinero::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('tipo_origen', 'declaracion_mi_corte')
            ->where('notas', 'like', '%'.$tag.'%')
            ->whereIn('estado', ['pendiente', 'recibido'])
            ->sum('total');

        $efectivoAEntregar = round(max(0, $efectivoGenerado - $yaEntregado - $gastosEfectivo), 2);

        return [
            'ventas' => $ventas,
            'ventas_count' => $ventas->count(),
            'ventas_periodo_cobradas' => $ventasPeriodoCobradas,
            'total_general' => $totalGeneral,
            'por_metodo' => $porMetodo,
            'efectivo_generado' => $efectivoGenerado,
            'ya_entregado' => round($yaEntregado, 2),
            'gastos_efectivo' => round($gastosEfectivo, 2),
            'gastos_detalle' => $gastosDetalle,
            'efectivo_a_entregar' => $efectivoAEntregar,
            'descuentos_partidas' => $descuentosPartidas,
            'descuento_general' => $descuentoGeneralSum,
            'total_descuentos' => $totalDescuentos,
        ];
    }

    /**
     * @param  array<int>  $ventaIds
     */
    private function sumDescuentosPartidasVentaItems(array $ventaIds): float
    {
        if ($ventaIds === []) {
            return 0.0;
        }

        $row = VentaItem::withoutGlobalScope('empresa')
            ->whereIn('venta_id', $ventaIds)
            ->selectRaw(
                'SUM(
                    CASE
                        WHEN COALESCE(descuento_monto, 0) > 0 THEN descuento_monto
                        ELSE COALESCE(precio, 0) * COALESCE(cantidad, 0) * (COALESCE(descuento, 0) / 100)
                    END
                ) as total_desc'
            )
            ->first();

        return round((float) ($row?->total_desc ?? 0), 2);
    }

    private function ventaCuentaParaResumenCobros(Venta $v, int $userId): bool
    {
        $bucket = $this->bucketMetodoPago($v->metodo_pago);
        $esPagador = (int) ($v->pagado_por ?? 0) === $userId;
        $esCreador = (int) ($v->created_by ?? 0) === $userId;
        $esVendedor = $this->usuarioEsVendedorAsignado($v, $userId);
        $tieneVendedorAsignado = filled($v->vendedor_id) && filled(trim((string) ($v->vendedor_type ?? '')));

        if ($bucket === 'efectivo') {
            if ($tieneVendedorAsignado) {
                if ($esVendedor) {
                    return true;
                }
                $rel = $v->relationLoaded('vendedor') ? $v->getRelation('vendedor') : $v->vendedor;
                if ($rel === null) {
                    return $esPagador || $esCreador;
                }

                return false;
            }

            return $esPagador || $esCreador;
        }

        if ($esPagador || $esVendedor) {
            return true;
        }

        if (! $tieneVendedorAsignado) {
            return $esCreador;
        }

        return false;
    }

    private function usuarioEsVendedorAsignado(Venta $v, int $userId): bool
    {
        if ($v->vendedor_id === null || ! filled($v->vendedor_type)) {
            return false;
        }

        $rel = $v->relationLoaded('vendedor') ? $v->getRelation('vendedor') : $v->vendedor;

        if ($rel instanceof User) {
            return (int) $rel->id === $userId;
        }

        if ($rel !== null && is_object($rel) && property_exists($rel, 'user_id') && $rel->user_id) {
            return (int) $rel->user_id === $userId;
        }

        if ($this->ventaTieneVendedorUserAsignado($v)) {
            return (int) $v->vendedor_id === $userId;
        }

        return false;
    }

    private function ventaTieneVendedorUserAsignado(Venta $v): bool
    {
        if ($v->vendedor_id === null || $v->vendedor_type === null) {
            return false;
        }

        $tipo = trim((string) $v->vendedor_type);
        if ($tipo === '') {
            return false;
        }

        $resolved = Relation::getMorphedModel($tipo) ?? $tipo;

        return is_a($resolved, User::class, true);
    }

    /**
     * @param  string|null  $metodo  Valor almacenado en ventas.metodo_pago
     */
    private function bucketMetodoPago(?string $metodo): string
    {
        $k = strtolower(trim((string) $metodo));
        if ($k === '') {
            return 'otros';
        }
        if (str_contains($k, 'efectivo')) {
            return 'efectivo';
        }
        if ($k === 'cash') {
            return 'efectivo';
        }
        if (str_contains($k, 'transfer')) {
            return 'transferencia';
        }
        if (str_contains($k, 'tarjeta')) {
            return 'tarjetas';
        }
        if (str_contains($k, 'cheque')) {
            return 'cheque';
        }
        if (str_contains($k, 'credit')) {
            return 'credito';
        }

        return 'otros';
    }
}
