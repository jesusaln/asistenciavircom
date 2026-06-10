<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Nom035Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nom035_complaints';

    protected $fillable = [
        'empresa_id',
        'folio',
        'type',
        'description',
        'incident_date',
        'is_anonymous',
        'reporter_name',
        'reporter_email',
        'status',
        'admin_notes',
        'resolution_details',
        'evidence_paths',
        'resolved_at'
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'incident_date' => 'date',
        'evidence_paths' => 'array',
        'resolved_at' => 'datetime',
        'description' => 'encrypted',
        'admin_notes' => 'encrypted',
    ];

    protected static function booted()
    {
        static::creating(function ($complaint) {
            if (empty($complaint->folio)) {
                $complaint->folio = 'DEN-' . strtoupper(Str::random(6));
            }
            if (empty($complaint->status)) {
                $complaint->status = 'pending';
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
