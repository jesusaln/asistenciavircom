<?php

namespace App\Services\Mantenimiento;

use App\Models\Mantenimiento;
use Carbon\Carbon;

class MantenimientoStatsService
{
    public function getConsolidatedStats($query = null): array
    {
        $base = $query ? clone $query : Mantenimiento::query();

        $totalQuery = clone $base;
        $completadosQuery = clone $base;
        $vencidosQuery = clone $base;
        $porVencerQuery = clone $base;
        $alDiaQuery = clone $base;
        $costoQuery = clone $base;

        return [
            'total_general' => $totalQuery->count(),
            'total_activos' => $completadosQuery->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO)->count(),
            'completados' => (clone $base)->where('estado', Mantenimiento::ESTADO_COMPLETADO)->count(),
            'vencidos' => $vencidosQuery->vencidos()->count(),
            'por_vencer' => $porVencerQuery->porVencer()->count(),
            'al_dia' => $alDiaQuery->alDia()->count(),
            'costo_total_mes' => $costoQuery->whereMonth('fecha', now()->month)
                ->whereYear('fecha', now()->year)
                ->sum('costo'),
        ];
    }

    public function calcularEstadoDerivado(Mantenimiento $mantenimiento): string
    {
        if ($mantenimiento->estado === Mantenimiento::ESTADO_COMPLETADO) {
            return 'completado';
        }

        if (!$mantenimiento->proximo_mantenimiento) {
            return 'al_dia';
        }

        $hoy = Carbon::today();
        $proximo = Carbon::parse($mantenimiento->proximo_mantenimiento);
        $diasAnticipacion = (int) ($mantenimiento->dias_anticipacion_alerta ?? 30);
        $alertAt = $proximo->copy()->subDays($diasAnticipacion);

        if ($proximo->isPast()) {
            return 'vencido';
        }

        if ($hoy->greaterThanOrEqualTo($alertAt)) {
            return 'por_vencer';
        }

        return 'al_dia';
    }

    public function getEstadoMetadata(Mantenimiento $mantenimiento): array
    {
        $estado = $this->calcularEstadoDerivado($mantenimiento);
        $hoy = Carbon::today();

        if ($mantenimiento->estado === Mantenimiento::ESTADO_COMPLETADO) {
            return [
                'estado' => 'completado',
                'descripcion' => 'Servicio completado',
                'clase' => 'text-green-700 bg-green-100',
                'dias_restantes' => 0,
                'es_vencido' => false,
                'es_proximo' => false
            ];
        }

        if (!$mantenimiento->proximo_mantenimiento) {
            return [
                'estado' => 'al_dia',
                'descripcion' => 'Sin fecha próxima',
                'clase' => 'text-gray-700 bg-gray-100',
                'dias_restantes' => null,
                'es_vencido' => false,
                'es_proximo' => false,
            ];
        }

        $proximo = Carbon::parse($mantenimiento->proximo_mantenimiento);
        $diasRestantes = (int) $hoy->diffInDays($proximo, false);

        return match ($estado) {
            'vencido' => [
                'estado' => 'vencido',
                'descripcion' => "Vencido hace " . abs($diasRestantes) . " días",
                'clase' => 'text-red-700 bg-red-100',
                'dias_restantes' => $diasRestantes,
                'es_vencido' => true,
                'es_proximo' => false
            ],
            'por_vencer' => [
                'estado' => 'por_vencer',
                'descripcion' => "Vence en {$diasRestantes} días",
                'clase' => 'text-orange-700 bg-orange-100',
                'dias_restantes' => $diasRestantes,
                'es_vencido' => false,
                'es_proximo' => true
            ],
            default => [
                'estado' => 'al_dia',
                'descripcion' => "Próximo en {$diasRestantes} días",
                'clase' => 'text-blue-700 bg-blue-100',
                'dias_restantes' => $diasRestantes,
                'es_vencido' => false,
                'es_proximo' => false
            ],
        };
    }
}
