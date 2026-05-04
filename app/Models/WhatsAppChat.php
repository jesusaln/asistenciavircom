<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class WhatsAppChat extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'whats_app_chats';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'wa_id',
        'from_name',
        'body',
        'type',
        'direction',
        'is_internal',
        'message_id',
        'status',
        'metadata',
        'received_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'received_at' => 'datetime',
        'is_internal' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'wa_id', 'wa_id');
    }
}
