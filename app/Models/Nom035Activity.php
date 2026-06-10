<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nom035Activity extends Model
{
    protected $table = 'nom035_activities';
    protected $fillable = [
        'empresa_id',
        'type',
        'title',
        'description',
        'activity_date',
        'participants_count',
        'evidence_file',
        'status'
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];
}
