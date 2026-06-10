<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('citas', 'equipos_servicio')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->json('equipos_servicio')->nullable()->after('poliza_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn('equipos_servicio');
        });
    }
};
