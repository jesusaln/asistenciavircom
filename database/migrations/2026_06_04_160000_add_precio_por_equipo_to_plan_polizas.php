<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('plan_polizas', 'precio_por_equipo')) {
            Schema::table('plan_polizas', function (Blueprint $table) {
                $table->decimal('precio_por_equipo', 15, 2)->nullable()->after('precio_mensual');
            });
        }
    }

    public function down(): void
    {
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn('precio_por_equipo');
        });
    }
};
