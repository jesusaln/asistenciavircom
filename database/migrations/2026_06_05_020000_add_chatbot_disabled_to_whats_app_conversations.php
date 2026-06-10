<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whats_app_conversations', function (Blueprint $table) {
            $table->boolean('chatbot_disabled')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('whats_app_conversations', function (Blueprint $table) {
            $table->dropColumn('chatbot_disabled');
        });
    }
};
