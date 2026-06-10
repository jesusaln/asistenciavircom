<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Support\Str;

class MarketingCampana extends Model
{
    use BelongsToEmpresa;

    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'marketing_campanas';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'tipo',
        'plantilla_id',
        'data_plantilla',
        'estado',
        'fecha_programacion',
        'user_id',
        'empresa_id',
    ];

    protected $casts = [
        'data_plantilla' => 'array',
        'fecha_programacion' => 'datetime',
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

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function destinatarios()
    {
        return $this->hasMany(MarketingDestinatario::class, 'campana_id');
    }
}
