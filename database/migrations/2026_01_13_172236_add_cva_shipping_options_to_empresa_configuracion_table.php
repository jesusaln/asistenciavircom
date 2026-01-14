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
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->integer('cva_codigo_sucursal')->default(1)->nullable()->after('cva_utility_percentage'); // 1 = Guadalajara
            $table->integer('cva_paqueteria_envio')->default(4)->nullable()->after('cva_codigo_sucursal'); // 4 = Paquetexpress
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn(['cva_codigo_sucursal', 'cva_paqueteria_envio']);
        });
    }
};
