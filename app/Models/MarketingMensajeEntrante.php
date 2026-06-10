<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Support\Str;

class MarketingMensajeEntrante extends Model
{
    use BelongsToEmpresa;

    use HasFactory, BelongsToEmpresa;

    protected $table = 'marketing_mensajes_entrantes';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'cliente_id',
        'telefono',
        'mensaje',
        'plataforma',
        'metadata',
        'empresa_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
