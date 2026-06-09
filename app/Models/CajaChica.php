<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Support\Facades\Storage;

class CajaChica extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'caja_chica';

    protected $fillable = [
        'empresa_id',
        'concepto',
        'categoria',
        'monto',
        'tipo',
        'fecha',
        'nota',
        'comprobante_path',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    protected $appends = ['comprobante_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adjuntos()
    {
        return $this->hasMany(CajaChicaAdjunto::class);
    }

    public function getComprobanteUrlAttribute()
    {
        if ($this->relationLoaded('adjuntos') && $this->adjuntos->count() > 0) {
            return optional($this->adjuntos->first())->url;
        }
        return $this->comprobante_path ? Storage::url($this->comprobante_path) : null;
    }
}
