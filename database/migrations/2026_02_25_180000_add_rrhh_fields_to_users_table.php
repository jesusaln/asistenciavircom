<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'ine')) {
                $table->string('ine', 30)->nullable()->after('nss');
            }

            if (!Schema::hasColumn('users', 'imss')) {
                $table->string('imss', 50)->nullable()->after('ine');
            }

            if (!Schema::hasColumn('users', 'dias_trabajo')) {
                $table->json('dias_trabajo')->nullable()->after('hora_salida_sabado');
            }

            if (!Schema::hasColumn('users', 'dias_descanso')) {
                $table->json('dias_descanso')->nullable()->after('dias_trabajo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('users', 'ine') ? 'ine' : null,
                Schema::hasColumn('users', 'imss') ? 'imss' : null,
                Schema::hasColumn('users', 'dias_trabajo') ? 'dias_trabajo' : null,
                Schema::hasColumn('users', 'dias_descanso') ? 'dias_descanso' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
