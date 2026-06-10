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
        // Añadir columnas persona_fisica y persona_moral a sat_usos_cfdi
        if (Schema::hasTable('sat_usos_cfdi')) {
            Schema::table('sat_usos_cfdi', function (Blueprint $table) {
                if (!Schema::hasColumn('sat_usos_cfdi', 'persona_fisica')) {
                    $table->boolean('persona_fisica')->default(true)->after('descripcion');
                }
                if (!Schema::hasColumn('sat_usos_cfdi', 'persona_moral')) {
                    $table->boolean('persona_moral')->default(true)->after('persona_fisica');
                }
            });
        }

        // Añadir columnas persona_fisica y persona_moral a sat_regimenes_fiscales
        if (Schema::hasTable('sat_regimenes_fiscales')) {
            Schema::table('sat_regimenes_fiscales', function (Blueprint $table) {
                if (!Schema::hasColumn('sat_regimenes_fiscales', 'persona_fisica')) {
                    $table->boolean('persona_fisica')->default(false)->after('descripcion');
                }
                if (!Schema::hasColumn('sat_regimenes_fiscales', 'persona_moral')) {
                    $table->boolean('persona_moral')->default(false)->after('persona_fisica');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sat_usos_cfdi')) {
            Schema::table('sat_usos_cfdi', function (Blueprint $table) {
                $table->dropColumn(['persona_fisica', 'persona_moral']);
            });
        }

        if (Schema::hasTable('sat_regimenes_fiscales')) {
            Schema::table('sat_regimenes_fiscales', function (Blueprint $table) {
                $table->dropColumn(['persona_fisica', 'persona_moral']);
            });
        }
    }
};
