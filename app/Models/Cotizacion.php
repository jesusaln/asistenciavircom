<?php

namespace App\Models;

use App\Enums\EstadoCotizacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\Blameable;

class Cotizacion extends Model
{
    use HasFactory, SoftDeletes, Blameable, BelongsToEmpresa;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'almacen_id',          // ✅ agregado para conversión a venta
        'numero_cotizacion',   // ✅ agregado
        'fecha_cotizacion',    // ✅ agregado
        'subtotal',
        'descuento_general',
        'descuento_items',     // ✅ agregado
        'iva',
        'retencion_iva',       // ✅ agregado
        'retencion_isr',       // ✅ agregado
        'isr',                 // ✅ ISR para personas morales
        'total',
        'notas',
        'estado',
        // Campos para rastreo de email
        'email_enviado',
        'email_enviado_fecha',
        'email_enviado_por',
    ];

    protected $casts = [
        'estado' => EstadoCotizacion::class,
        // (Opcional) ayuda a mantener consistencia de decimales
        'subtotal' => 'decimal:2',
        'descuento_general' => 'decimal:2',
        'descuento_items' => 'decimal:2',
        'iva' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        'isr' => 'decimal:2',
        'total' => 'decimal:2',
        // Campos de email
        'email_enviado' => 'boolean',
        'email_enviado_fecha' => 'datetime',
    ];

    /** Relación con cliente */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** Ítems de la cotización (tabla cotizacion_items) */
    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
    }

    // Relaciones de “culpables” (opcionales, para mostrar en UI)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function emailEnviadoPor()
    {
        return $this->belongsTo(User::class, 'email_enviado_por');
    }

    /**
     * Productos cotizados (relación polimórfica a través de cotizacion_items).
     * Nota: Solo es necesaria si en algún punto usas attach/detach directamente.
     * Solo incluye productos activos.
     */
    public function productos(): MorphToMany
    {
        return $this->morphedByMany(
            Producto::class,
            'cotizable',
            'cotizacion_items',
            'cotizacion_id',
            'cotizable_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto')
            ->wherePivot('cotizable_type', Producto::class)
            ->active();
    }

    /**
     * Servicios cotizados (relación polimórfica a través de cotizacion_items).
     * Nota: Solo es necesaria si en algún punto usas attach/detach directamente.
     * Solo incluye servicios activos.
     */
    public function servicios(): MorphToMany
    {
        return $this->morphedByMany(
            Servicio::class,
            'cotizable',
            'cotizacion_items',
            'cotizacion_id',
            'cotizable_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto')
            ->wherePivot('cotizable_type', Servicio::class)
            ->active();
    }

    /** Marcado de estado helper */
    public function marcarComoEnviadoAPedido(): void
    {
        $this->update(['estado' => EstadoCotizacion::EnviadoAPedido]);
    }

    /** Puede enviarse a pedido según su estado actual */
    public function puedeEnviarseAPedido(): bool
    {
        $estadoActual = $this->estado->value;
        return in_array($estadoActual, [
            EstadoCotizacion::Aprobada->value,
            EstadoCotizacion::Pendiente->value,
            EstadoCotizacion::Borrador->value,
        ], true);
    }

    /** Relación con pedidos generados desde esta cotización */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /** Último pedido asociado (si existe) */
    public function pedido(): HasOne
    {
        return $this->hasOne(Pedido::class)->latest();
    }

    /** Relación con ventas generadas desde esta cotización */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cotizacion_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Cotizacion $cot) {
            if (empty($cot->numero_cotizacion)) {
                $cot->numero_cotizacion = static::generarNumero();
            }
            if (empty($cot->estado)) {
                $cot->estado = EstadoCotizacion::Pendiente;
            }
        });
    }

    /**
     * Genera un número de cotización único de forma segura para concurrencia.
     * Usa bloqueo de base de datos para evitar duplicados en solicitudes simultáneas.
     * 
     * @param int $maxRetries Número máximo de reintentos en caso de colisión
     * @return string Número de cotización en formato C###
     */
    /**
     * Genera un número de cotización único usando FolioService.
     */
    public static function generarNumero(): string
    {
        try {
            return app(\App\Services\Folio\FolioService::class)->getNextFolio('cotizacion');
        } catch (\Exception $e) {
            Log::error("Error generando folio de cotización: " . $e->getMessage());
            return 'COT-' . date('Ymd-His'); // Fallback de emergencia
        }
    }

    /**
     * Verifica si una excepción de base de datos es reintentable (deadlock, timeout)
     */
    private static function isRetryableException(\Illuminate\Database\QueryException $e): bool
    {
        $retryableCodes = [
            1213, // Deadlock found
            1205, // Lock wait timeout
            40001, // Serialization failure
        ];

        return in_array($e->errorInfo[1] ?? 0, $retryableCodes);
    }
}
