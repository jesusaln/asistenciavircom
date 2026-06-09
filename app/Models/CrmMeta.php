<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CrmMeta extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'crm_metas';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'tipo',
        'meta_diaria',
        'fecha_inicio',
        'fecha_fin',
        'activa',
        'created_by',
        'campania_id',
    ];

    protected $casts = [
        'meta_diaria' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activa' => 'boolean',
    ];

    public const TIPOS = [
        'actividades' => 'Actividades por día',
        'prospectos' => 'Prospectos contactados por día',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campania(): BelongsTo
    {
        return $this->belongsTo(CrmCampania::class, 'campania_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDelTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeVigentes($query)
    {
        $hoy = Carbon::today();
        return $query->where(function ($q) use ($hoy) {
            $q->whereNull('fecha_inicio')
              ->orWhere('fecha_inicio', '<=', $hoy);
        })->where(function ($q) use ($hoy) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>=', $hoy);
        });
    }

    /**
     * Obtener el progreso de hoy para esta meta
     */
    public function getProgresoHoy(): array
    {
        $hoy = Carbon::today();
        $userId = $this->user_id;
        $realizado = 0;

        if ($this->tipo === 'actividades') {
            // Contar actividades de CRM registradas hoy por el usuario
            $realizado = CrmActividad::where('user_id', $userId)
                ->whereDate('created_at', $hoy)
                ->count();
        } elseif ($this->tipo === 'prospectos') {
            // Contar prospectos con actividad registrada hoy
            $realizado = CrmProspecto::whereHas('actividades', function ($q) use ($userId, $hoy) {
                $q->where('user_id', $userId)
                  ->whereDate('created_at', $hoy);
            })->count();
        }

        $porcentaje = $this->meta_diaria > 0 
            ? min(100, round(($realizado / $this->meta_diaria) * 100)) 
            : 0;

        return [
            'meta' => $this->meta_diaria,
            'realizado' => $realizado,
            'porcentaje' => $porcentaje,
            'cumplida' => $realizado >= $this->meta_diaria,
        ];
    }

    /**
     * Obtener progreso de un usuario para un tipo específico
     */
    public static function getProgresoUsuario($userId, $tipo = null): array
    {
        $query = self::activas()->vigentes()->delUsuario($userId);
        
        if ($tipo) {
            $query->delTipo($tipo);
        }

        $metas = $query->get();
        $resultado = [];

        foreach ($metas as $meta) {
            $resultado[$meta->tipo] = $meta->getProgresoHoy();
            $resultado[$meta->tipo]['tipo_label'] = self::TIPOS[$meta->tipo] ?? $meta->tipo;
        }

        return $resultado;
    }

    /**
     * Obtener leaderboard del día
     */
    public static function getLeaderboard($limit = 10): array
    {
        $hoy = Carbon::today();
        
        // Obtener usuarios con metas activas
        $usuariosConMetas = self::activas()
            ->vigentes()
            ->with('user:id,name')
            ->get()
            ->pluck('user_id')
            ->unique();

        $leaderboard = [];

        foreach ($usuariosConMetas as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            // Contar actividades del día
            $actividadesHoy = CrmActividad::where('user_id', $userId)
                ->whereDate('created_at', $hoy)
                ->count();

            // Contar prospectos contactados hoy
            $prospectosHoy = CrmProspecto::whereHas('actividades', function ($q) use ($userId, $hoy) {
                $q->where('user_id', $userId)
                  ->whereDate('created_at', $hoy);
            })->count();

            // Obtener metas del usuario
            $metas = self::activas()->vigentes()->delUsuario($userId)->get();
            $metasCumplidas = 0;
            $totalMetas = $metas->count();

            foreach ($metas as $meta) {
                $progreso = $meta->getProgresoHoy();
                if ($progreso['cumplida']) {
                    $metasCumplidas++;
                }
            }

            $leaderboard[] = [
                'user_id' => $userId,
                'nombre' => $user->name,
                'actividades' => $actividadesHoy,
                'prospectos' => $prospectosHoy,
                'metas_cumplidas' => $metasCumplidas,
                'total_metas' => $totalMetas,
                'porcentaje_cumplimiento' => $totalMetas > 0 
                    ? round(($metasCumplidas / $totalMetas) * 100) 
                    : 0,
            ];
        }

        // Ordenar por metas cumplidas y luego por actividades
        usort($leaderboard, function ($a, $b) {
            if ($a['metas_cumplidas'] !== $b['metas_cumplidas']) {
                return $b['metas_cumplidas'] - $a['metas_cumplidas'];
            }
            return $b['actividades'] - $a['actividades'];
        });

        return array_slice($leaderboard, 0, $limit);
    }
}
