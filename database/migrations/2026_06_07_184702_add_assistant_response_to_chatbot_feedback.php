<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_feedback', function (Blueprint $table) {
            $table->text('assistant_response')->nullable()->after('user_message');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_feedback', function (Blueprint $table) {
            $table->dropColumn('assistant_response');
        });
    }
};
