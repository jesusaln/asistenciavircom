<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CajaSesion extends Model
{
    use BelongsToEmpresa;

    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'caja_sesiones';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'almacen_id',
        'monto_inicial',
        'total_ventas_efectivo',
        'total_entradas',
        'total_salidas',
        'monto_final_sistema',
        'monto_declarado',
        'diferencia',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'detalles_cierre',
        'notas'
    ];

    protected $casts = [
        'detalles_cierre' => 'array',
        'monto_inicial' => 'decimal:2',
        'total_ventas_efectivo' => 'decimal:2',
        'monto_final_sistema' => 'decimal:2',
        'monto_declarado' => 'decimal:2',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
}
