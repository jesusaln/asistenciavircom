<?php

namespace App\Models;

use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversation extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'wa_id',
        'canonical_wa_id',
        'contact_name',
        'assigned_to',
        'status',
        'chatbot_disabled',
        'tags',
        'last_message_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $conv) {
            if ($conv->wa_id && !$conv->canonical_wa_id) {
                try {
                    $conv->canonical_wa_id = WhatsAppService::canonicalWaId($conv->wa_id);
                } catch (\Throwable $e) {
                    $conv->canonical_wa_id = $conv->wa_id;
                }
            }
        });

        static::updating(function (self $conv) {
            if ($conv->isDirty('wa_id') && !$conv->canonical_wa_id) {
                try {
                    $conv->canonical_wa_id = WhatsAppService::canonicalWaId($conv->wa_id);
                } catch (\Throwable $e) {
                    $conv->canonical_wa_id = $conv->wa_id;
                }
            }
        });
    }

    protected $casts = [
        'tags' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppChat::class, 'wa_id', 'wa_id');
    }
}
