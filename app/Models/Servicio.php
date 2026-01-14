<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected static function booted()
    {
        static::creating(function (Servicio $servicio) {
            if (empty($servicio->codigo)) {
                try {
                    $servicio->codigo = app(\App\Services\Folio\FolioService::class)->getNextFolio('servicio');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for servicio: ' . $e->getMessage());
                }
            }
        });
    }

    /**
     * Generar el siguiente código para un servicio sin incrementarlo (Vista previa).
     */
    public static function generateNextCodigo(): string
    {
        return app(\App\Services\Folio\FolioService::class)->previewNextFolio('servicio');
    }

    protected $casts = [
        'precio' => 'decimal:2',
        'margen_ganancia' => 'decimal:2',
        'es_instalacion' => 'boolean',
        'comision_vendedor' => 'decimal:2',
    ];

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'codigo',
        'categoria_id',
        'precio',
        'margen_ganancia',
        'duracion',
        'estado',
        'es_instalacion',
        'comision_vendedor',
        'sat_clave_prod_serv',
        'sat_clave_unidad',
        'sat_objeto_imp',
    ];

    public function scopeActive($query)
    {
        return $query->where('estado', 'activo');
    }

    // Relación con la categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Relación con las cotizaciones
    public function cotizaciones()
    {
        return $this->morphToMany(Cotizacion::class, 'cotizable', 'cotizacion_items')
            ->withPivot(['cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto'])
            ->withTimestamps();
    }

    // Relación con las citas
    public function citas()
    {
        return $this->morphToMany(Cita::class, 'citable', 'cita_items')
            ->withPivot(['cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto', 'notas'])
            ->withTimestamps();
    }

    public function pedidos()
    {
        return $this->morphToMany(Pedido::class, 'pedible', 'pedido_items')
            ->withPivot('precio', 'cantidad')
            ->withTimestamps();
    }

    public function ventas()
    {
        return $this->morphToMany(Venta::class, 'ventable', 'venta_items')
            ->withPivot('cantidad', 'precio', 'descuento', 'costo_unitario');
    }

    public function getGananciaAttribute()
    {
        return $this->precio * ($this->margen_ganancia / 100);
    }

    /** =========================
     * Relaciones SAT
     * ========================= */

    public function satClaveProdServ()
    {
        return $this->belongsTo(SatClaveProdServ::class, 'sat_clave_prod_serv', 'clave');
    }

    public function satClaveUnidad()
    {
        return $this->belongsTo(SatClaveUnidad::class, 'sat_clave_unidad', 'clave');
    }

    public function satObjetoImp()
    {
        return $this->belongsTo(SatObjetoImp::class, 'sat_objeto_imp', 'clave');
    }
}
