<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingAudiencia extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'marketing_audiencias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'empresa_id',
        'user_id',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'marketing_audiencia_clientes', 'audiencia_id', 'cliente_id')
            ->withTimestamps();
    }
}
