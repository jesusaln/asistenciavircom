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
            $table->boolean('contpaqi_enabled')->default(false)->after('pac_produccion');
            $table->string('contpaqi_bridge_url')->nullable()->after('contpaqi_enabled');
            $table->string('contpaqi_ruta_empresa')->nullable()->after('contpaqi_bridge_url');
            $table->string('contpaqi_codigo_concepto')->nullable()->after('contpaqi_ruta_empresa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'contpaqi_enabled',
                'contpaqi_bridge_url',
                'contpaqi_ruta_empresa',
                'contpaqi_codigo_concepto'
            ]);
        });
    }
};
