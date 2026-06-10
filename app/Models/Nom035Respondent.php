<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Nom035Respondent extends Model
{
    use HasUuid;
    protected $fillable = [
        'empresa_id', 'evaluation_period_id', 'empleado_id', 'name', 'email', 'department', 'position',
        'applied_guide', 'status', 'risk_level', 'total_score', 'results', 'completed_at',
        'requires_clinical_valuation', 'clinical_valuation_status', 'clinical_valuation_notes',
        'clinical_valuation_date', 'clinical_valuation_evidence', 'signature_path', 'signature_date',
        'integrity_hash', 'consent_ip', 'consent_user_agent', 'consent_policy_hash', 'uuid'
    ];
    protected $table = 'nom035_respondents';
    protected $casts = [
        'results' => 'array', 
        'completed_at' => 'datetime',
        'signature_date' => 'datetime',
        'clinical_valuation_date' => 'date'
    ];
    public $timestamps = false;

    /**
     * Normalización de la guía aplicada (I, II o III)
     */
    public function setAppliedGuideAttribute($value)
    {
        if (str_contains($value, 'III') || $value === '3') {
            $this->attributes['applied_guide'] = 'III';
        } elseif (str_contains($value, 'II') || $value === '2') {
            $this->attributes['applied_guide'] = 'II';
        } elseif (str_contains($value, 'I') || $value === '1') {
            $this->attributes['applied_guide'] = 'I';
        } else {
            $this->attributes['applied_guide'] = $value;
        }
    }

    public function getAppliedGuideFmtAttribute()
    {
        $val = $this->applied_guide;
        if ($val === 'I') return 'Guía de Referencia I';
        if ($val === 'II') return 'Guía de Referencia II';
        if ($val === 'III') return 'Guía de Referencia III';
        return $val;
    }

    public function answers() { return $this->hasMany(Nom035Answer::class, 'respondent_id'); }
    public function empleado() { return $this->belongsTo(\App\Models\User::class, 'empleado_id'); }
    public function period() { return $this->belongsTo(Nom035EvaluationPeriod::class, 'evaluation_period_id'); }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
