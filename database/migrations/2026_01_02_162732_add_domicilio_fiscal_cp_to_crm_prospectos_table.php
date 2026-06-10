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
        Schema::table('crm_prospectos', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_prospectos', 'domicilio_fiscal_cp')) {
                $table->string('domicilio_fiscal_cp', 5)->nullable()->after('codigo_postal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_prospectos', function (Blueprint $table) {
            if (Schema::hasColumn('crm_prospectos', 'domicilio_fiscal_cp')) {
                $table->dropColumn('domicilio_fiscal_cp');
            }
        });
    }
};
