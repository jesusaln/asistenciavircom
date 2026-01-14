<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Concerns\BelongsToEmpresa;

class WhatsAppMessage extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'empresa_id',
        'to',
        'template_name',
        'template_params',
        'message_id',
        'status',
        'response',
        'error_code',
    ];

    protected $casts = [
        'template_params' => 'array',
        'response' => 'array',
    ];

    // Estados posibles del mensaje
    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_READ = 'read';

    /**
     * Relación con la empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar por empresa
     */
    public function scopeByEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope para mensajes recientes
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Marcar como enviado
     */
    public function markAsSent(string $messageId, array $response = []): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'message_id' => $messageId,
            'response' => $response,
        ]);
    }

    /**
     * Marcar como entregado
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $errorCode = null, array $response = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_code' => $errorCode,
            'response' => $response,
        ]);
    }

    /**
     * Marcar como leído
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => self::STATUS_READ,
        ]);
    }
}
