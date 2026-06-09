<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'checkin_token')) {
                $table->string('checkin_token', 64)->nullable()->unique()->after('remember_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'checkin_token')) {
                try {
                    $table->dropUnique('users_checkin_token_unique');
                } catch (\Throwable $e) {
                    // El indice puede no existir en esquemas heredados.
                }
                $table->dropColumn('checkin_token');
            }
        });
    }
};
