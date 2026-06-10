<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrato extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'contrato_plantilla_id',
        'tipo',
        'titulo',
        'contenido',
        'archivo_path',
        'estado',
        'signing_token',
        'metadata',
        'signed_at',
        'signature_client',
        'constancia_nom151',
        'hash_documento',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'signed_at' => 'datetime',
        'expires_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function getEstadoColorAttribute()
    {
        return match($this->estado) {
            'borrador' => 'slate',
            'pendiente_firma' => 'amber',
            'firmado' => 'emerald',
            'cancelado' => 'rose',
            default => 'slate',
        };
    }
}
