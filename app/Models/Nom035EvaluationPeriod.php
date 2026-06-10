<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nom035EvaluationPeriod extends Model
{
    protected $fillable = ['empresa_id', 'name', 'start_date', 'end_date', 'active', 'status'];
    protected $table = 'nom035_evaluation_periods';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function respondents()
    {
        return $this->hasMany(\App\Models\Nom035Respondent::class, 'evaluation_period_id');
    }
}
