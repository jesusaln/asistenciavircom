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
        'numero_factura',
        'cliente_id',
        'fecha_emision',
        'fecha_vencimiento',
        'subtotal',
        'descuento_general',
        'impuestos',
        'iva',
        'total',
        'estado',
        'metodo_pago',
        'moneda',
        'tasa_cambio',
        'observaciones',
        'direccion_facturacion',
        'datos_fiscales',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'descuento_general' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'tasa_cambio' => 'decimal:4',
        'datos_fiscales' => 'array',
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
     * Scopes
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeVencidas($query)
    {
        return $query->where('fecha_vencimiento', '<', now())
            ->where('estado', '!=', self::ESTADO_PAGADA)
            ->where('estado', '!=', self::ESTADO_CANCELADA)
            ->where('estado', '!=', self::ESTADO_ANULADA);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', [self::ESTADO_ENVIADA, self::ESTADO_VENCIDA]);
    }

    /**
     * Accesor para obtener el estado formateado
     */
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            self::ESTADO_BORRADOR => 'Borrador',
            self::ESTADO_ENVIADA => 'Enviada',
            self::ESTADO_PAGADA => 'Pagada',
            self::ESTADO_VENCIDA => 'Vencida',
            self::ESTADO_CANCELADA => 'Cancelada',
            self::ESTADO_ANULADA => 'Anulada',
        ];

        return $estados[$this->estado] ?? 'Desconocido';
    }

    /**
     * Accesor para saber si está vencida
     */
    public function getEstaVencidaAttribute()
    {
        return $this->fecha_vencimiento < now() &&
            !in_array($this->estado, [self::ESTADO_PAGADA, self::ESTADO_CANCELADA, self::ESTADO_ANULADA]);
    }

    /**
     * Accesor para obtener días de vencimiento
     */
    public function getDiasVencimientoAttribute()
    {
        if ($this->fecha_vencimiento) {
            return now()->diffInDays($this->fecha_vencimiento, false);
        }
        return null;
    }

    /**
     * Accesor para obtener el saldo pendiente
     */
    public function getSaldoPendienteAttribute()
    {
        $totalPagado = $this->pagos()->where('estado', 'aprobado')->sum('monto');
        return $this->total - $totalPagado;
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
     * Método para marcar como pagada
     */
    public function marcarComoPagada()
    {
        $this->update(['estado' => self::ESTADO_PAGADA]);
    }

    /**
     * Método para calcular totales desde las ventas
     */
    public function recalcularTotales()
    {
        $ventasTotales = $this->ventas->sum('total');
        $ventasSubtotales = $this->ventas->sum('subtotal');
        $ventasDescuentos = $this->ventas->sum('descuento_general');
        $ventasIva = $this->ventas->sum('iva');

        $this->update([
            'subtotal' => $ventasSubtotales,
            'descuento_general' => $ventasDescuentos,
            'iva' => $ventasIva,
            'total' => $ventasTotales,
        ]);
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

        static::saving(function ($factura) {
            // Actualizar estado a vencida si corresponde
            if (
                $factura->fecha_vencimiento < now() &&
                $factura->estado === self::ESTADO_ENVIADA
            ) {
                $factura->estado = self::ESTADO_VENCIDA;
            }
        });
    }
}
