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
            $table->string('repse_constancia_name')->nullable();
            $table->string('acta_constitutiva_name')->nullable();
            $table->string('curp_pdf_name')->nullable();
            $table->string('csf_pdf_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn(['repse_constancia_name', 'acta_constitutiva_name', 'curp_pdf_name', 'csf_pdf_name']);
        });
    }
};
