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
        if (!Schema::hasColumn('empresa_configuracion', 'firma_digital')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->longText('firma_digital')->nullable()->after('logo_reportes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn('firma_digital');
        });
    }
};
