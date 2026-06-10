<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class RepseComplianceDoc extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'proveedor_id',
        'type',
        'month',
        'year',
        'file_path',
        'status',
        'observations',
        'verified_at',
        'verified_by'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get readable document type name
     */
    public function getTypeNameAttribute()
    {
        return [
            'sat_opinion' => 'Opinión de Cumplimiento SAT',
            'imss_opinion' => 'Opinión de Cumplimiento IMSS',
            'infonavit_opinion' => 'Opinión de Cumplimiento INFONAVIT',
            'sua' => 'Pago de Cuotas SUA (IMSS)',
            'idse' => 'Movimientos Afiliatorios (IDSE)',
            'payroll' => 'Recibos de Nómina / Lista de Raya',
        ][$this->type] ?? $this->type;
    }
}
