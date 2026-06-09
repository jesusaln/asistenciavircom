<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('empresa_configuracion') && !Schema::hasColumn('empresa_configuracion', 'minutos_tolerancia_retardo')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->unsignedSmallInteger('minutos_tolerancia_retardo')->default(15)->after('biometrics_far_liveness_penalty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('empresa_configuracion', 'minutos_tolerancia_retardo')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->dropColumn('minutos_tolerancia_retardo');
            });
        }
    }
};
