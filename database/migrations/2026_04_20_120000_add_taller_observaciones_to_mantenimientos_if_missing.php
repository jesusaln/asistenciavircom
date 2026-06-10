<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('mantenimientos')) {
            return;
        }

        Schema::table('mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimientos', 'taller')) {
                $table->string('taller', 100)->nullable();
            }
            if (!Schema::hasColumn('mantenimientos', 'observaciones_alerta')) {
                $table->string('observaciones_alerta', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('mantenimientos')) {
            return;
        }

        Schema::table('mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('mantenimientos', 'taller')) {
                $table->dropColumn('taller');
            }
            if (Schema::hasColumn('mantenimientos', 'observaciones_alerta')) {
                $table->dropColumn('observaciones_alerta');
            }
        });
    }
};
