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
        Schema::table('contab_polizas', function (Blueprint $table) {
            if (!Schema::hasColumn('contab_polizas', 'cfdi_uuids')) {
                $table->jsonb('cfdi_uuids')->nullable()->after('cfdi_uuid');
                $table->index('cfdi_uuids', 'contab_polizas_cfdi_uuids_gin', 'gin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contab_polizas', function (Blueprint $table) {
            if (Schema::hasColumn('contab_polizas', 'cfdi_uuids')) {
                $table->dropIndex('contab_polizas_cfdi_uuids_gin');
                $table->dropColumn('cfdi_uuids');
            }
        });
    }
};
