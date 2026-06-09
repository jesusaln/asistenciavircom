<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToEmpresa;

class AsistenciaRegistro extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'user_id',
        'almacen_id',
        'tipo',
        'registrado_at',
        'origen',
        'latitud',
        'longitud',
        'precision_metros',
        'direccion',
        'selfie_path',
        'ip_address',
        'user_agent',
        'notas',
        'es_incidencia',
        'motivo_incidencia',
        'consentimiento_biometrico',
        'face_verified',
        'face_match_score',
        'face_liveness_score',
        'face_verification_status',
        'face_provider',
        'face_verification_notes',
        'face_detected_count',
        'face_capture_quality_passed',
        'face_quality_brightness',
        'face_quality_sharpness',
        'face_quality_area_ratio',
        'face_quality_center_offset',
        'face_quality_message',
    ];

    protected $casts = [
        'registrado_at' => 'datetime',
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
        'precision_metros' => 'integer',
        'es_incidencia' => 'boolean',
        'consentimiento_biometrico' => 'boolean',
        'face_verified' => 'boolean',
        'face_match_score' => 'decimal:4',
        'face_liveness_score' => 'decimal:4',
        'face_detected_count' => 'integer',
        'face_capture_quality_passed' => 'boolean',
        'face_quality_brightness' => 'decimal:4',
        'face_quality_sharpness' => 'decimal:4',
        'face_quality_area_ratio' => 'decimal:4',
        'face_quality_center_offset' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }
}
