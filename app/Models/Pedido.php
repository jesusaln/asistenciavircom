<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\EstadoPedido;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\Blameable;

class Pedido extends Model
{
    use SoftDeletes, Blameable, BelongsToEmpresa;

    protected $table = 'pedidos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'cotizacion_id',
        'numero_pedido',
        'subtotal',
        'descuento_general',
        'iva',
        'retencion_iva',
        'retencion_isr',
        'isr',
        'total',
        'notas',
        'estado',
        // Campos para rastreo de email
        'email_enviado',
        'email_enviado_fecha',
        'email_enviado_por',
    ];

    protected $casts = [
        'estado' => EstadoPedido::class,
        'subtotal' => 'decimal:2',
        'descuento_general' => 'decimal:2',
        'iva' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        'isr' => 'decimal:2',
        'total' => 'decimal:2',
        // Campos de email
        'email_enviado' => 'boolean',
        'email_enviado_fecha' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }



    // Relaciones polimórficas para productos y servicios
    public function productos()
    {
        return $this->morphedByMany(
            Producto::class,
            'pedible',
            'pedido_items',
            'pedido_id',
            'pedible_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto')
            ->wherePivotIn('pedible_type', [Producto::class, 'producto'])
            ->active();
    }

    public function servicios()
    {
        return $this->morphedByMany(
            Servicio::class,
            'pedible',
            'pedido_items',
            'pedido_id',
            'pedible_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto')
            ->wherePivotIn('pedible_type', [Servicio::class, 'servicio'])
            ->active();
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    // Órdenes de compra generadas automáticamente para este pedido
    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }

    // Relaciones de auditoría
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

    // Relación con el usuario que envió el email
    public function emailEnviadoPor()
    {
        return $this->belongsTo(User::class, 'email_enviado_por');
    }
}
