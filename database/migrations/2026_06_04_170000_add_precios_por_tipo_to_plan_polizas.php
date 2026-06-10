<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('plan_polizas', 'precios_por_tipo')) {
            Schema::table('plan_polizas', function (Blueprint $table) {
                $table->json('precios_por_tipo')->nullable()->after('precio_por_equipo');
            });
        }
    }

    public function down(): void
    {
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn('precios_por_tipo');
        });
    }
};
