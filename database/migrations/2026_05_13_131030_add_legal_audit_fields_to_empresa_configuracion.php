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
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->string('repse_number')->nullable();
            $table->date('repse_expiry')->nullable();
            $table->text('repse_activity')->nullable();
            $table->string('repse_constancia_path')->nullable();
            $table->string('acta_constitutiva_path')->nullable();
            $table->string('registro_patronal_imss')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'repse_number', 
                'repse_expiry', 
                'repse_activity', 
                'repse_constancia_path', 
                'acta_constitutiva_path',
                'registro_patronal_imss'
            ]);
        });
    }
};
