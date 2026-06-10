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
            if (!Schema::hasColumn('empresa_configuracion', 'pin_auditoria')) {
                $table->string('pin_auditoria', 10)->default('1234')->after('whatsapp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_configuracion', 'pin_auditoria')) {
                $table->dropColumn('pin_auditoria');
            }
        });
    }
};
