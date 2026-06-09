<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemoteSupportSession extends Model
{
    protected $fillable = [
        'empresa_id',
        'user_id',
        'cliente_id',
        'rustdesk_id',
        'rustdesk_alias',
        'started_at',
        'ended_at',
        'duration_minutes',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}

