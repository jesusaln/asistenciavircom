<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepseContract extends Model
{
    protected $fillable = [
        'cliente_id',
        'contract_number',
        'service_object',
        'start_date',
        'end_date',
        'amount',
        'status',
        'file_path'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empleados()
    {
        return $this->belongsToMany(\App\Models\User::class, 'repse_contract_employee', 'repse_contract_id', 'user_id')->withTimestamps();
    }

    public function evidences()
    {
        return $this->hasMany(RepseContractEvidence::class, 'repse_contract_id');
    }
}
