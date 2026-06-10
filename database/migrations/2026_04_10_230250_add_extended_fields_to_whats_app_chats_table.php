<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whats_app_chats', function (Blueprint $table) {
            if (!Schema::hasColumn('whats_app_chats', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('empresa_id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('whats_app_chats', 'is_internal')) {
                $table->boolean('is_internal')->default(false)->after('direction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whats_app_chats', function (Blueprint $table) {
            if (Schema::hasColumn('whats_app_chats', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('whats_app_chats', 'is_internal')) {
                $table->dropColumn('is_internal');
            }
        });
    }
};
