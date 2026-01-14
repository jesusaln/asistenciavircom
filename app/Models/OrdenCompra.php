<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Concerns\BelongsToEmpresa;

class OrdenCompra extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'orden_compras';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'pedido_id',
        'almacen_id',
        'numero_orden',
        'fecha_orden',
        'fecha_entrega_esperada',
        'prioridad',
        'direccion_entrega',
        'terminos_pago',
        'metodo_pago',
        'subtotal',
        'descuento_items',
        'descuento_general',
        'iva',
        'retencion_iva',
        'retencion_isr',
        'isr',
        'total',
        'observaciones',
        'estado',
        'fecha_recepcion',
        'aplicar_retencion_iva',
        'aplicar_retencion_isr',
        // Campos para rastreo de email
        'email_enviado',
        'email_enviado_fecha',
        'email_enviado_por',
        // Campos de auditoría
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_orden' => 'date',
        'fecha_entrega_esperada' => 'date',
        'fecha_recepcion' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento_items' => 'decimal:2',
        'descuento_general' => 'decimal:2',
        'iva' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        'isr' => 'decimal:2',
        'total' => 'decimal:2',
        'aplicar_retencion_iva' => 'boolean',
        'aplicar_retencion_isr' => 'boolean',
        // Campos de email
        'email_enviado' => 'boolean',
        'email_enviado_fecha' => 'datetime',
    ];

    // Relación con el Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Relación con el Almacén
    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    // Relación con el usuario que envió el email
    public function emailEnviadoPor()
    {
        return $this->belongsTo(User::class, 'email_enviado_por');
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

    // Relación muchos a muchos con Productos (incluye todos, para preservar historial)
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'orden_compra_producto')
            ->withPivot('cantidad', 'precio', 'descuento', 'unidad_medida');
    }

    // Relación solo con productos activos (para nuevas operaciones)
    public function productosActivos()
    {
        return $this->belongsToMany(Producto::class, 'orden_compra_producto')
            ->withPivot('cantidad', 'precio', 'descuento', 'unidad_medida')
            ->active();
    }

    // Relación con el Pedido que originó esta orden de compra
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    // ✅ FIX #5: Relación con la Compra generada desde esta orden
    public function compra()
    {
        return $this->hasOne(Compra::class);
    }

    // Relación con todas las compras generadas (en caso de múltiples recepciones parciales futuras)
    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    protected static function booted(): void
    {
        // Asignar usuario actual en creating
        static::creating(function (OrdenCompra $orden) {
            // Solo generar numero_orden si no viene en los datos de entrada
            if (empty($orden->numero_orden) && !isset($orden->getAttributes()['numero_orden'])) {
                $orden->numero_orden = self::getProximoNumero();
            }

            // Asignar usuario creador
            if (Auth::check() && empty($orden->created_by)) {
                $orden->created_by = Auth::id();
            }
        });

        static::created(function (OrdenCompra $orden) {
            // Si no se asignó numero_orden en el creating, intentar asignarlo aquí
            if (empty($orden->numero_orden)) {
                $orden->update(['numero_orden' => self::getProximoNumero()]);
            }
        });

        // Asignar usuario en updating
        static::updating(function (OrdenCompra $orden) {
            if (Auth::check()) {
                $orden->updated_by = Auth::id();
            }
        });

        // Asignar usuario en deleting (soft delete)
        static::deleting(function (OrdenCompra $orden) {
            if (Auth::check()) {
                $orden->deleted_by = Auth::id();
                $orden->saveQuietly();
            }
        });
    }

    public static function getProximoNumero()
    {
        // Usar una subconsulta compatible con MySQL y SQLite
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: usar CAST con UNSIGNED
            $ultimo = self::withTrashed()
                ->where('numero_orden', 'like', 'OC-%')
                ->orderByRaw("CAST(SUBSTRING(numero_orden, 4) AS UNSIGNED) DESC")
                ->first();
        } else {
            // SQLite y otros: usar CAST con INTEGER
            $ultimo = self::withTrashed()
                ->where('numero_orden', 'like', 'OC-%')
                ->orderByRaw("CAST(SUBSTR(numero_orden, 4) AS INTEGER) DESC")
                ->first();
        }

        if ($ultimo) {
            $numero = intval(substr($ultimo->numero_orden, 3)) + 1;
        } else {
            $numero = 1;
        }

        return 'OC-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }
}
