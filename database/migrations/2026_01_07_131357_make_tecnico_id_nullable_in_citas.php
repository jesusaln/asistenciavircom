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
        if (!Schema::hasTable('citas')) {
            return;
        }

        Schema::table('citas', function (Blueprint $table) {
            // Hacer tecnico_id nullable para citas públicas que no tienen técnico asignado
            if (Schema::hasColumn('citas', 'tecnico_id')) {
                $table->foreignId('tecnico_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('citas')) {
            return;
        }

        Schema::table('citas', function (Blueprint $table) {
            if (Schema::hasColumn('citas', 'tecnico_id')) {
                $table->foreignId('tecnico_id')->nullable(false)->change();
            }
        });
    }
};
