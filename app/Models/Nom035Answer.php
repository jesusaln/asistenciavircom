<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nom035Answer extends Model
{
    protected $fillable = ['respondent_id', 'question_id', 'value'];
    protected $table = 'nom035_answers';

    public function question()
    {
        return $this->belongsTo(Nom035Question::class, 'question_id');
    }

    public function respondent()
    {
        return $this->belongsTo(Nom035Respondent::class, 'respondent_id');
    }
}
