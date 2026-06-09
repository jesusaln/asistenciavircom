<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Nomina extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::creating(function (Nomina $nomina) {
            if (empty($nomina->folio)) {
                try {
                    $nomina->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('nomina');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for nomina: ' . $e->getMessage());
                }
            }
        });
    }

    protected $table = 'nominas';

    protected $fillable = [
        'folio',
        'empresa_id',
        'empleado_id',
        'periodo_inicio',
        'periodo_fin',
        'tipo_periodo',
        'numero_periodo',
        'anio',
        'salario_base',
        'dias_trabajados',
        'horas_extra',
        'monto_horas_extra',
        'total_percepciones',
        'total_deducciones',
        'total_neto',
        'estado',
        'fecha_pago',
        'metodo_pago',
        'referencia_pago',
        'creado_por',
        'procesado_por',
        'procesado_at',
        'pagado_por',
        'pagado_at',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date:Y-m-d',
            'periodo_fin' => 'date:Y-m-d',
            'fecha_pago' => 'date:Y-m-d',
            'procesado_at' => 'datetime',
            'pagado_at' => 'datetime',
            'salario_base' => 'decimal:2',
            'dias_trabajados' => 'decimal:2',
            'horas_extra' => 'decimal:2',
            'monto_horas_extra' => 'decimal:2',
            'total_percepciones' => 'decimal:2',
            'total_deducciones' => 'decimal:2',
            'total_neto' => 'decimal:2',
            'anio' => 'integer',
            'numero_periodo' => 'integer',
        ];
    }

    // ========================================
    // Relaciones
    // ========================================

    public function empleado()
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }

    public function conceptos()
    {
        return $this->hasMany(NominaConcepto::class);
    }

    public function percepciones()
    {
        return $this->hasMany(NominaConcepto::class)->where('tipo', 'percepcion');
    }

    public function deducciones()
    {
        return $this->hasMany(NominaConcepto::class)->where('tipo', 'deduccion');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function procesadoPor()
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }

    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    // ========================================
    // Accessors
    // ========================================

    /**
     * Período formateado (ej: "01 Dic - 15 Dic 2024")
     */
    public function getPeriodoFormateadoAttribute()
    {
        $inicio = Carbon::parse($this->periodo_inicio);
        $fin = Carbon::parse($this->periodo_fin);

        if ($inicio->month === $fin->month) {
            return $inicio->format('d') . ' - ' . $fin->format('d M Y');
        }

        return $inicio->format('d M') . ' - ' . $fin->format('d M Y');
    }

    /**
     * Tipo de período formateado
     */
    public function getTipoPeriodoFormateadoAttribute()
    {
        $tipos = [
            'semanal' => 'Semanal',
            'quincenal' => 'Quincenal',
            'mensual' => 'Mensual',
        ];
        return $tipos[$this->tipo_periodo] ?? $this->tipo_periodo;
    }

    /**
     * Estado formateado con color
     */
    public function getEstadoInfoAttribute()
    {
        $estados = [
            'borrador' => ['label' => 'Borrador', 'color' => 'bg-gray-100 text-gray-700', 'dot' => 'bg-gray-400'],
            'procesada' => ['label' => 'Procesada', 'color' => 'bg-blue-100 text-blue-700', 'dot' => 'bg-blue-400'],
            'pagada' => ['label' => 'Pagada', 'color' => 'bg-green-100 text-green-700', 'dot' => 'bg-green-400'],
            'cancelada' => ['label' => 'Cancelada', 'color' => 'bg-red-100 text-red-700', 'dot' => 'bg-red-400'],
        ];
        return $estados[$this->estado] ?? $estados['borrador'];
    }

    /**
     * Verificar si es editable
     */
    public function getEsEditableAttribute()
    {
        return $this->estado === 'borrador';
    }

    /**
     * Nombre del empleado
     */
    public function getNombreEmpleadoAttribute()
    {
        return $this->empleado?->nombre_completo ?? 'Sin empleado';
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeDelAnio($query, $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopeDelPeriodo($query, $tipoPeriodo)
    {
        return $query->where('tipo_periodo', $tipoPeriodo);
    }

    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeBorradores($query)
    {
        return $query->where('estado', 'borrador');
    }

    public function scopeProcesadas($query)
    {
        return $query->where('estado', 'procesada');
    }

    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagada');
    }

    public function scopeDelEmpleado($query, $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('periodo_inicio', [$inicio, $fin]);
    }

    // ========================================
    // Métodos
    // ========================================

    /**
     * Recalcular totales basado en conceptos
     */
    public function recalcularTotales(): void
    {
        $this->total_percepciones = $this->percepciones()->sum('monto');
        $this->total_deducciones = $this->deducciones()->sum('monto');
        $this->total_neto = $this->total_percepciones - $this->total_deducciones;
        $this->save();
    }

    /**
     * Agregar un concepto
     */
    public function agregarConcepto(array $datos): NominaConcepto
    {
        $concepto = $this->conceptos()->create($datos);
        $this->recalcularTotales();
        return $concepto;
    }

    /**
     * Procesar nómina (cambiar estado a procesada)
     */
    public function procesar(int $userId): bool
    {
        if ($this->estado !== 'borrador') {
            return false;
        }

        $this->recalcularTotales();
        $this->estado = 'procesada';
        $this->procesado_por = $userId;
        $this->procesado_at = now();
        return $this->save();
    }

    /**
     * Marcar como pagada
     */
    public function marcarPagada(int $userId, ?string $metodoPago = null, ?string $referencia = null): bool
    {
        if (!in_array($this->estado, ['procesada'])) {
            return false;
        }

        $this->estado = 'pagada';
        $this->fecha_pago = now()->toDateString();
        $this->pagado_por = $userId;
        $this->pagado_at = now();
        $this->metodo_pago = $metodoPago;
        $this->referencia_pago = $referencia;
        return $this->save();
    }

    /**
     * Cancelar nómina
     */
    public function cancelar(): bool
    {
        if ($this->estado === 'pagada') {
            return false;
        }

        $this->estado = 'cancelada';
        return $this->save();
    }

    /**
     * Generar conceptos automáticos (ISR, IMSS desde préstamos activos)
     */
    public function generarConceptosAutomaticos(): void
    {
        // Agregar sueldo base como percepción
        $this->conceptos()->create([
            'tipo' => 'percepcion',
            'concepto' => 'Sueldo',
            'clave' => 'P001',
            'clave_sat' => '001',
            'monto' => $this->salario_base,
            'es_automatico' => true,
            'es_gravable' => true,
        ]);

        // Agregar horas extra si aplica
        if ($this->horas_extra > 0 && $this->monto_horas_extra > 0) {
            $this->conceptos()->create([
                'tipo' => 'percepcion',
                'concepto' => 'Horas Extra',
                'clave' => 'P002',
                'clave_sat' => '019',
                'monto' => $this->monto_horas_extra,
                'es_automatico' => true,
                'es_gravable' => true,
            ]);
        }

        // Agregar deducciones de préstamos activos
        $prestamosActivos = $this->empleado->prestamosActivos();
        foreach ($prestamosActivos as $prestamo) {
            $this->conceptos()->create([
                'tipo' => 'deduccion',
                'concepto' => 'Préstamo #' . $prestamo->id,
                'clave' => 'D004',
                'clave_sat' => '004',
                'monto' => $prestamo->pago_periodico,
                'prestamo_id' => $prestamo->id,
                'es_automatico' => true,
                'notas' => 'Deducción automática de préstamo',
            ]);
        }

        $this->recalcularTotales();
    }

    /**
     * Calcular días del período
     */
    public function getDiasPeriodoAttribute(): int
    {
        return Carbon::parse($this->periodo_inicio)->diffInDays(Carbon::parse($this->periodo_fin)) + 1;
    }
}
