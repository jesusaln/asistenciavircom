<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotFeedback extends Model
{
    protected $table = 'chatbot_feedback';

    protected $fillable = [
        'session_id',
        'user_message',
        'assistant_response',
        'sentiment',
        'trigger_phrase',
        'empresa_id',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
    ];
}
