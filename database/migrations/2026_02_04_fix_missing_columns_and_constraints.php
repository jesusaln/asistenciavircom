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
        // 1. Add folio to traspasos
        if (Schema::hasTable('traspasos') && !Schema::hasColumn('traspasos', 'folio')) {
            Schema::table('traspasos', function (Blueprint $table) {
                $table->string('folio', 50)->nullable()->after('id');
            });
        }

        // 2. Add folio to mantenimientos
        if (Schema::hasTable('mantenimientos') && !Schema::hasColumn('mantenimientos', 'folio')) {
            Schema::table('mantenimientos', function (Blueprint $table) {
                $table->string('folio', 50)->nullable()->after('id');
            });
        }

        // 3. Fix uuid in clientes (make it nullable or remove not null)
        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'uuid')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('uuid', 50)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('traspasos')) {
            Schema::table('traspasos', function (Blueprint $table) {
                $table->dropColumn('folio');
            });
        }

        if (Schema::hasTable('mantenimientos')) {
            Schema::table('mantenimientos', function (Blueprint $table) {
                $table->dropColumn('folio');
            });
        }

        // Reverting change() on clientes depends on previous state, usually not safe to force NOT NULL back without defaults.
    }
};
