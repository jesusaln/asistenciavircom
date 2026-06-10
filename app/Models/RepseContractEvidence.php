<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepseContractEvidence extends Model
{
    protected $table = 'repse_contract_evidences';

    protected $fillable = [
        'repse_contract_id',
        'file_path',
        'description',
        'evidence_date'
    ];

    public function contract()
    {
        return $this->belongsTo(RepseContract::class, 'repse_contract_id');
    }
}
