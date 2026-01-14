<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmScript extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'crm_scripts';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'tipo',
        'etapa',
        'contenido',
        'tips',
        'activo',
        'orden',
        'created_by',
        'campania_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public const TIPOS = [
        'apertura' => 'Apertura',
        'seguimiento' => 'Seguimiento',
        'cierre' => 'Cierre',
        'objecion' => 'Manejo de Objeciones',
        'presentacion' => 'Presentación',
    ];

    public const ETAPAS = [
        'prospecto' => 'Prospecto',
        'contactado' => 'Contactado',
        'interesado' => 'Interesado',
        'cotizado' => 'Cotizado',
        'negociacion' => 'Negociación',
        'general' => 'General',
    ];

    // Relaciones
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campania(): BelongsTo
    {
        return $this->belongsTo(CrmCampania::class, 'campania_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorEtapa($query, $etapa)
    {
        return $query->where(function ($q) use ($etapa) {
            $q->where('etapa', $etapa)->orWhere('etapa', 'general');
        });
    }

    // Helpers
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getEtapaLabelAttribute(): string
    {
        return self::ETAPAS[$this->etapa] ?? $this->etapa;
    }

    public function getTipoIconAttribute(): string
    {
        return match($this->tipo) {
            'apertura' => 'door-open',
            'seguimiento' => 'redo',
            'cierre' => 'handshake',
            'objecion' => 'shield-alt',
            'presentacion' => 'presentation',
            default => 'file-alt',
        };
    }
}
