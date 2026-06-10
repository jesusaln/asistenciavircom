<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Support\Facades\Storage;

class TallerOrden extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'taller_ordenes';

    protected $fillable = [
        'empresa_id',
        'folio',
        'cliente_id',
        'nombre_cliente',
        'telefono_cliente',
        'equipo_marca',
        'equipo_modelo',
        'equipo_serie',
        'accesorios',
        'estado_fisico',
        'problema_reportado',
        'diagnostico',
        'trabajo_realizado',
        'costo_estimado',
        'costo_final',
        'estado',
        'firma_recepcion',
        'firma_entrega',
        'fecha_recepcion',
        'fecha_entrega',
        'fecha_compromiso',
        'user_id',
        'tecnico_id',
    ];

    protected $casts = [
        'accesorios' => 'array',
        'costo_estimado' => 'decimal:2',
        'costo_final' => 'decimal:2',
        'fecha_recepcion' => 'datetime',
        'fecha_entrega' => 'datetime',
        'fecha_compromiso' => 'datetime',
    ];

    protected $appends = ['firma_recepcion_url', 'firma_entrega_url'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function recepcionista()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function venta()
    {
        return $this->hasOne(Venta::class, 'taller_orden_id');
    }

    public function getFirmaRecepcionUrlAttribute()
    {
        return $this->firma_recepcion ? Storage::url('taller/firmas/' . $this->firma_recepcion) : null;
    }

    public function getFirmaEntregaUrlAttribute()
    {
        return $this->firma_entrega ? Storage::url('taller/firmas/' . $this->firma_entrega) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orden) {
            if (empty($orden->folio)) {
                try {
                    $orden->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('taller');
                } catch (\Exception $e) {
                    $orden->folio = 'TALL-' . time();
                }
            }
        });
    }
}
