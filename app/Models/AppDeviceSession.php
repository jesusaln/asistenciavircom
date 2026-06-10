<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppDeviceSession extends Model
{
    protected $fillable = [
        'user_id',
        'uuid',
        'platform',
        'version',
        'os_version',
        'model',
        'manufacturer',
        'last_seen_at',
        'attributes',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'attributes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
