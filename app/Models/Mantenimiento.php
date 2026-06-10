<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Mantenimiento extends Model
{
    use BelongsToEmpresa;

    use HasFactory;

    protected static function booted()
    {
        static::creating(function (Mantenimiento $mantenimiento) {
            if (empty($mantenimiento->folio)) {
                try {
                    $mantenimiento->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('mantenimiento');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for mantenimiento: ' . $e->getMessage());
                }
            }
            if (empty($mantenimiento->fecha_programada) && !empty($mantenimiento->fecha)) {
                $mantenimiento->fecha_programada = $mantenimiento->fecha;
            }
        });
    }

    protected $fillable = [
        'folio',
        'carro_id',
        'tipo',
        'fecha',
        'fecha_programada',
        'proximo_mantenimiento',
        'descripcion',
        'notas',
        'costo',
        'taller',
        'estado',
        'kilometraje_actual',
        'prioridad',
        'dias_anticipacion_alerta',
        'requiere_aprobacion',
        'observaciones_alerta',
        'alerta_enviada',
        'recordatorios_enviados',
        'proximo_kilometraje',
        'km_anticipacion_alerta',
        'frecuencia_recordatorio_dias',
        'tecnico_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_programada' => 'date',
        'proximo_mantenimiento' => 'date',
        'costo' => 'decimal:2',
        'kilometraje_actual' => 'integer',
        'dias_anticipacion_alerta' => 'integer',
        'requiere_aprobacion' => 'boolean',
    ];

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_CANCELADO = 'cancelado';

    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_MEDIA = 'media';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_CRITICA = 'critica';

    const TIPOS = [
        'Cambio de aceite',
        'Revisión periódica',
        'Servicio de frenos',
        'Servicio de llantas',
        'Servicio de batería',
        'Servicio de motor',
        'Revisión de luces',
        'Alineación y balanceo',
        'Cambio de filtros',
        'Revisión de transmisión',
        'Otro servicio'
    ];

    const TIPOS_RECURRENTES = [
        'Cambio de aceite',
        'Revisión periódica',
        'Alineación y balanceo',
        'Cambio de filtros'
    ];

    const INTERVALOS_RECURRENTES = [
        'Cambio de aceite' => 180,
        'Revisión periódica' => 365,
        'Alineación y balanceo' => 180,
        'Cambio de filtros' => 365,
    ];

    const COSTOS_SUGERIDOS = [
        'Cambio de aceite' => 800.00,
        'Revisión periódica' => 1200.00,
        'Alineación y balanceo' => 800.00,
        'Cambio de filtros' => 400.00,
    ];

    const DIAS_MINIMOS_ENTRE_SERVICIOS = [
        'Cambio de aceite' => 30,
        'Revisión periódica' => 90,
        'Servicio de frenos' => 180,
        'Servicio de llantas' => 60,
        'Servicio de batería' => 180,
        'Servicio de motor' => 365,
        'Revisión de luces' => 90,
        'Alineación y balanceo' => 90,
        'Cambio de filtros' => 60,
        'Revisión de transmisión' => 365,
        'Otro servicio' => 7,
    ];

    const KM_INTERVALOS_RECURRENTES = [
        'Cambio de aceite' => 10000,
        'Revisión periódica' => 15000,
        'Alineación y balanceo' => 20000,
        'Cambio de filtros' => 20000,
    ];

    const DIAS_ALERTA_POR_PRIORIDAD = [
        self::PRIORIDAD_CRITICA => 45,
        self::PRIORIDAD_ALTA => 30,
        self::PRIORIDAD_MEDIA => 15,
        self::PRIORIDAD_BAJA => 7,
    ];

    const ESTADOS_VALIDOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROCESO,
        self::ESTADO_COMPLETADO,
        self::ESTADO_CANCELADO,
    ];

    const PRIORIDADES = [
        self::PRIORIDAD_BAJA,
        self::PRIORIDAD_MEDIA,
        self::PRIORIDAD_ALTA,
        self::PRIORIDAD_CRITICA,
    ];

    public function carro(): BelongsTo
    {
        return $this->belongsTo(Carro::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeCarro($query, $carroId)
    {
        return $query->where('carro_id', $carroId);
    }

    public function scopeActivos($query)
    {
        return $query->whereNotIn('estado', [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO]);
    }

    public function scopeProximosAVencer($query, $dias = 30)
    {
        return $query->where('proximo_mantenimiento', '<=', now()->addDays($dias))
            ->whereNotIn('estado', [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO]);
    }

    public function scopeVencidos($query)
    {
        return $query->where('proximo_mantenimiento', '<', now())
            ->whereNotIn('estado', [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO]);
    }

    public function scopePorVencer($query)
    {
        return $query->whereNotIn('estado', [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO])
            ->whereNotNull('proximo_mantenimiento')
            ->where('proximo_mantenimiento', '>=', now())
            ->whereRaw('(proximo_mantenimiento - CURRENT_DATE) <= COALESCE(dias_anticipacion_alerta, 15)');
    }

    public function scopeAlDia($query)
    {
        return $query->whereNotIn('estado', [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO])
            ->where(function ($q) {
                $q->whereNull('proximo_mantenimiento')
                  ->orWhere(function ($q2) {
                      $q2->where('proximo_mantenimiento', '>=', now())
                         ->whereRaw('(proximo_mantenimiento - CURRENT_DATE) > COALESCE(dias_anticipacion_alerta, 15)');
                  });
            });
    }

    public function scopeConAlertasPendientes($query, $dias = 30)
    {
        return $query->where(function ($q) use ($dias) {
            $q->whereNotIn('estado', [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO])
                ->whereNotNull('proximo_mantenimiento')
                ->where('proximo_mantenimiento', '<=', now()->addDays($dias));
        });
    }

    public function scopeCancelados($query)
    {
        return $query->where('estado', self::ESTADO_CANCELADO);
    }

    public function getDiasRestantesAttribute()
    {
        if (!$this->proximo_mantenimiento) {
            return null;
        }
        return now()->diffInDays($this->proximo_mantenimiento, false);
    }

    public function getEstadoFormateadoAttribute()
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => ['label' => 'Pendiente', 'color' => 'bg-yellow-100 text-yellow-800'],
            self::ESTADO_EN_PROCESO => ['label' => 'En Proceso', 'color' => 'bg-blue-100 text-blue-800'],
            self::ESTADO_COMPLETADO => ['label' => 'Completado', 'color' => 'bg-green-100 text-green-800'],
            self::ESTADO_CANCELADO => ['label' => 'Cancelado', 'color' => 'bg-gray-100 text-gray-800'],
            default => ['label' => 'Desconocido', 'color' => 'bg-gray-100 text-gray-800']
        };
    }

    public function getPrioridadFormateadaAttribute()
    {
        return match ($this->prioridad) {
            self::PRIORIDAD_BAJA => ['label' => 'Baja', 'color' => 'bg-green-100 text-green-800'],
            self::PRIORIDAD_MEDIA => ['label' => 'Media', 'color' => 'bg-blue-100 text-blue-800'],
            self::PRIORIDAD_ALTA => ['label' => 'Alta', 'color' => 'bg-orange-100 text-orange-800'],
            self::PRIORIDAD_CRITICA => ['label' => 'Crítica', 'color' => 'bg-red-100 text-red-800'],
            default => ['label' => 'Media', 'color' => 'bg-blue-100 text-blue-800']
        };
    }

    public function getCostoFormateadoAttribute()
    {
        return '$' . number_format($this->costo ?? 0, 2);
    }

    public function getRequiereAlertaAttribute()
    {
        if (in_array($this->estado, [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO])) {
            return false;
        }

        if (!$this->proximo_mantenimiento) {
            return false;
        }

        $diasRestantes = $this->dias_restantes;
        $diasAnticipacion = $this->dias_anticipacion_alerta
            ?? self::DIAS_ALERTA_POR_PRIORIDAD[$this->prioridad]
            ?? 15;

        return $diasRestantes !== null && $diasRestantes <= $diasAnticipacion;
    }

    public static function diasAlertaPorPrioridad(?string $prioridad): int
    {
        return self::DIAS_ALERTA_POR_PRIORIDAD[$prioridad] ?? 15;
    }

    public static function intervaloKmRecurrente(string $tipo): int
    {
        return self::KM_INTERVALOS_RECURRENTES[$tipo] ?? 0;
    }

    public function marcarCompletado($fechaCompletado = null, $notas = null)
    {
        $this->update([
            'estado' => self::ESTADO_COMPLETADO,
            'fecha' => $fechaCompletado ?? now()->format('Y-m-d'),
            'notas' => $notas ? ($this->notas ? $this->notas . ' | ' . $notas : $notas) : $this->notas
        ]);
    }

    public function cancelar($motivo = null)
    {
        $this->update([
            'estado' => self::ESTADO_CANCELADO,
            'notas' => $motivo
                ? ($this->notas ? $this->notas . ' | Cancelado: ' . $motivo : 'Cancelado: ' . $motivo)
                : $this->notas
        ]);
    }

    public function cambiarEstado($nuevoEstado)
    {
        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS)) {
            throw new \InvalidArgumentException('Estado no válido: ' . $nuevoEstado);
        }
        $this->update(['estado' => $nuevoEstado]);
    }

    public function marcarAlertaEnviada()
    {
        $this->update(['alerta_enviada' => true]);
    }

    public function agregarRecordatorioEnviado($tipo)
    {
        $recordatorios = $this->recordatorios_enviados ?? [];
        $recordatorios[] = [
            'tipo' => $tipo,
            'fecha' => now()->format('Y-m-d H:i:s'),
            'timestamp' => now()->timestamp
        ];
        $this->update(['recordatorios_enviados' => $recordatorios]);
    }

    public function getKmRestantesAttribute()
    {
        if (!$this->proximo_kilometraje || !$this->carro) {
            return null;
        }
        $kmActual = $this->carro->kilometraje ?? $this->kilometraje_actual ?? 0;
        return $this->proximo_kilometraje - $kmActual;
    }

    public static function getEstadisticasAlertas()
    {
        return [
            'pendientes_vencidos' => self::where(function ($q) {
                $q->where('proximo_mantenimiento', '<', now())
                    ->orWhereRaw('proximo_kilometraje <= (SELECT kilometraje FROM carros WHERE id = mantenimientos.carro_id)');
            })
                ->whereNotIn('estado', [self::ESTADO_COMPLETADO, self::ESTADO_CANCELADO])
                ->count(),
            'proximos_30_dias' => self::conAlertasPendientes(30)->count(),
            'alertas_enviadas_hoy' => self::whereDate('updated_at', now())->where('alerta_enviada', true)->count(),
        ];
    }
}
