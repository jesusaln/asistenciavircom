<?php

namespace App\Services;

use App\Models\Herramienta;
use Illuminate\Database\Eloquent\Builder;

class HerramientaQueryService
{
    /**
     * Construye el query para index con filtros.
     */
    public function buildIndexQuery(array $filters): Builder
    {
        $search = (string) ($filters['search'] ?? '');
        $estado = (string) ($filters['estado'] ?? '');
        $categoria = (string) ($filters['categoria'] ?? '');
        $mantenimiento = (string) ($filters['mantenimiento'] ?? '');

        $query = Herramienta::query()
            ->with(['categoriaHerramienta', 'tecnico'])
            ->select(
                'id',
                'nombre',
                'numero_serie',
                'estado',
                'foto',
                'categoria_id',
                'tecnico_id',
                'fecha_ultimo_mantenimiento',
                'dias_para_mantenimiento',
                'vida_util_meses',
                'requiere_mantenimiento',
                'created_at'
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('numero_serie', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        if ($categoria !== '') {
            if ($categoria === 'sin_categoria') {
                $query->where(function ($q) {
                    $q->whereNull('categoria_id')->whereNull('categoria');
                });
            } else {
                $query->where('categoria_id', $categoria);
            }
        }

        if ($mantenimiento !== '') {
            switch ($mantenimiento) {
                case 'requiere':
                    $query->requierenMantenimientoUrgente();
                    break;
                case 'proximo':
                    $query->mantenimientoProximo();
                    break;
                case 'vencida':
                    $query->requierenMantenimientoUrgente();
                    break;
            }
        }

        return $query;
    }

    /**
     * Obtiene estadisticas generales para herramientas.
     */
    public function getStats(): array
    {
        return [
            'total' => Herramienta::count(),
            'disponibles' => Herramienta::disponibles()->count(),
            'asignadas' => Herramienta::asignadas()->count(),
            'mantenimiento' => Herramienta::enMantenimiento()->count(),
            'baja' => Herramienta::where('estado', Herramienta::ESTADO_BAJA)->count(),
            'perdida' => Herramienta::where('estado', Herramienta::ESTADO_PERDIDA)->count(),
            'requieren_mantenimiento' => Herramienta::requierenMantenimientoUrgente()->count(),
        ];
    }
}
