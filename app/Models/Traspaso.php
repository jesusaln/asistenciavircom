<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToEmpresa;

class Traspaso extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected static function booted()
    {
        static::creating(function (Traspaso $traspaso) {
            if (empty($traspaso->folio)) {
                try {
                    $traspaso->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('traspaso');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for traspaso: ' . $e->getMessage());
                }
            }
        });
    }

    protected $table = 'traspasos';

    protected $fillable = [
        'folio',
        'empresa_id',
        'producto_id', // Legacy - mantener para compatibilidad
        'almacen_origen_id',
        'almacen_destino_id',
        'cantidad', // Legacy - mantener para compatibilidad
        'estado',
        'usuario_autoriza',
        'usuario_envia',
        'usuario_recibe',
        'fecha_envio',
        'fecha_recepcion',
        'observaciones',
        'referencia',
        'costo_transporte',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_recepcion' => 'datetime',
        'costo_transporte' => 'decimal:2',
    ];

    /**
     * Relación con los items del traspaso (múltiples productos)
     */
    public function items(): HasMany
    {
        return $this->hasMany(TraspasoItem::class);
    }

    /**
     * Relación legacy con producto (para traspasos antiguos sin migrar)
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function usuarioAutoriza(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_autoriza');
    }

    public function usuarioEnvia(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_envia');
    }

    public function usuarioRecibe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_recibe');
    }

    /**
     * Obtener la cantidad total de productos trasladados
     */
    public function getCantidadTotalAttribute(): int
    {
        // Primero intentar desde items (nuevo sistema)
        if ($this->items->isNotEmpty()) {
            return $this->items->sum('cantidad');
        }

        // Fallback a campo legacy
        return (int) ($this->cantidad ?? 0);
    }

    /**
     * Obtener el número de productos diferentes en el traspaso
     */
    public function getProductosCountAttribute(): int
    {
        return $this->items->count() ?: ($this->producto_id ? 1 : 0);
    }
}
