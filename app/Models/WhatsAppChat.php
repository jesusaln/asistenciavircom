<?php

namespace App\Models;

use App\Services\WhatsAppService;
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
        'canonical_wa_id',
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

    protected static function booted(): void
    {
        static::creating(function (self $chat) {
            if ($chat->wa_id && !$chat->canonical_wa_id) {
                try {
                    $chat->canonical_wa_id = WhatsAppService::canonicalWaId($chat->wa_id);
                } catch (\Throwable $e) {
                    $chat->canonical_wa_id = $chat->wa_id;
                }
            }
        });

        static::updating(function (self $chat) {
            if ($chat->isDirty('wa_id') && !$chat->canonical_wa_id) {
                try {
                    $chat->canonical_wa_id = WhatsAppService::canonicalWaId($chat->wa_id);
                } catch (\Throwable $e) {
                    $chat->canonical_wa_id = $chat->wa_id;
                }
            }
        });
    }

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
