<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToEmpresa;

class TicketCategory extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'color',
        'icono',
        'sla_horas',
        'orden',
        'activo',
    ];

    protected $casts = [
        'sla_horas' => 'integer',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    // Relaciones

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'categoria_id');
    }

    public function articulos()
    {
        return $this->hasMany(KnowledgeBaseArticle::class, 'categoria_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
