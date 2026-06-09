<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'contenido',
        'es_interno',
        'tipo',
        'metadata',
    ];

    protected $casts = [
        'es_interno' => 'boolean',
        'metadata' => 'array',
    ];

    // Relaciones
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublicos($query)
    {
        return $query->where('es_interno', false);
    }

    public function scopeInternos($query)
    {
        return $query->where('es_interno', true);
    }

    public function scopeRespuestas($query)
    {
        return $query->where('tipo', 'respuesta');
    }
}
