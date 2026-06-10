<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->text('user_message');
            $table->text('assistant_response')->nullable();
            $table->string('sentiment')->index(); // positive/negative
            $table->string('trigger_phrase')->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_feedback');
    }
};
