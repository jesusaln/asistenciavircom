<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoBitacora extends Model
{
    protected $table = 'pedidos_online_bitacora';

    // Desactivamos updated_at, solo nos interesa cuándo ocurrió (created_at)
    public $timestamps = false; // Manejamos created_at manualmente en migración o dejamos que Eloquent lo maneje si solo usamos created_at

    protected $fillable = [
        'pedido_online_id',
        'accion',
        'descripcion',
        'usuario_id',
        'metadata',
        'created_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoOnline::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
