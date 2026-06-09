<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_configuracion', 'costo_promedio_hora_tecnico')) {
                $table->decimal('costo_promedio_hora_tecnico', 10, 2)->default(150.00)->after('iva_porcentaje');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn('costo_promedio_hora_tecnico');
        });
    }
};
