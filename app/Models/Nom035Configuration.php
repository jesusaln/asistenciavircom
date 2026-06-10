<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nom035Configuration extends Model
{
    protected $fillable = [
        'empresa_id',
        'policy_content',
        'policy_pdf_path',
        'responsible_name',
        'responsible_position'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
