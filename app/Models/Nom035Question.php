<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nom035Question extends Model
{
    protected $fillable = ['questionnaire_id', 'section', 'order', 'question_text', 'category', 'domain', 'is_inverse', 'has_options'];
    protected $table = 'nom035_questions';
}
