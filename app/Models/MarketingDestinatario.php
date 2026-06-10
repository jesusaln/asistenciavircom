<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingDestinatario extends Model
{
    use BelongsToEmpresa;

    use HasFactory;

    protected $table = 'marketing_destinatarios';

    protected $fillable = [
        'campana_id',
        'cliente_id',
        'estado',
        'external_message_id',
        'error_mensaje',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function campana()
    {
        return $this->belongsTo(MarketingCampana::class, 'campana_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'marketing_destinatario_id');
    }
}
