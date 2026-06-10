<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('historial_herramientas', function (Blueprint $table) {
            if (!Schema::hasColumn('historial_herramientas', 'confirmado_por_tecnico')) {
                $table->boolean('confirmado_por_tecnico')->default(false);
            }
            if (!Schema::hasColumn('historial_herramientas', 'fecha_confirmacion')) {
                $table->timestamp('fecha_confirmacion')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('historial_herramientas', function (Blueprint $table) {
            $cols = [];
            foreach (['confirmado_por_tecnico', 'fecha_confirmacion'] as $col) {
                if (Schema::hasColumn('historial_herramientas', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
