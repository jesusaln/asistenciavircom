<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;

class Factura extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'numero_factura',
        'folio',
        'fecha_emision',
        'subtotal',
        'iva',
        'total',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Estados posibles de la factura
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_ENVIADA = 'enviada';
    const ESTADO_PAGADA = 'pagada';
    const ESTADO_VENCIDA = 'vencida';
    const ESTADO_CANCELADA = 'cancelada';
    const ESTADO_ANULADA = 'anulada';

    // Métodos de pago
    const METODO_EFECTIVO = 'efectivo';
    const METODO_TRANSFERENCIA = 'transferencia';
    const METODO_TARJETA = 'tarjeta';
    const METODO_CHEQUE = 'cheque';
    const METODO_CREDITO = 'credito';

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con Ventas (una factura puede tener múltiples ventas)
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Relación con Pagos
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Relación con Items de Factura (si manejas items directamente)
     */
    public function items()
    {
        return $this->hasMany(FacturaItem::class);
    }

    /**
     * Obtener todos los items a través de las ventas
     */
    public function itemsViaVentas()
    {
        return $this->hasManyThrough(
            VentaItem::class,
            Venta::class,
            'factura_id',
            'venta_id',
            'id',
            'id'
        );
    }

    /**
     * Relación con CFDI
     */
    public function cfdi()
    {
        return $this->morphOne(Cfdi::class, 'cfdiable');
    }

    /**
     * Scopes
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Método para generar número de factura automático
     */
    public static function generarNumeroFactura()
    {
        $ultimaFactura = self::whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimaFactura) {
            $ultimoNumero = (int) substr($ultimaFactura->numero_factura, -5);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        return 'FAC-' . now()->format('Y') . '-' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method para eventos del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($factura) {
            if (empty($factura->numero_factura)) {
                $factura->numero_factura = self::generarNumeroFactura();
            }
        });
    }
}
