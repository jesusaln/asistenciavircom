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
        Schema::table('compras', function (Blueprint $table) {
            if (!Schema::hasColumn('compras', 'cfdi_uuid')) {
                $table->string('cfdi_uuid')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('compras', 'cfdi_serie')) {
                $table->string('cfdi_serie')->nullable()->after('cfdi_uuid');
            }
            if (!Schema::hasColumn('compras', 'cfdi_folio')) {
                $table->string('cfdi_folio')->nullable()->after('cfdi_serie');
            }
            if (!Schema::hasColumn('compras', 'cfdi_fecha')) {
                $table->dateTime('cfdi_fecha')->nullable()->after('cfdi_folio');
            }
            if (!Schema::hasColumn('compras', 'cfdi_emisor_rfc')) {
                $table->string('cfdi_emisor_rfc')->nullable()->after('cfdi_fecha');
            }
            if (!Schema::hasColumn('compras', 'cfdi_emisor_nombre')) {
                $table->string('cfdi_emisor_nombre')->nullable()->after('cfdi_emisor_rfc');
            }
            if (!Schema::hasColumn('compras', 'cfdi_total')) {
                $table->decimal('cfdi_total', 10, 2)->nullable()->after('cfdi_emisor_nombre');
            }
            if (!Schema::hasColumn('compras', 'origen_importacion')) {
                $table->string('origen_importacion')->nullable()->default('manual')->after('cfdi_total'); // manual, xml, bulk_import
            }
            if (!Schema::hasColumn('compras', 'inventario_procesado')) {
                $table->boolean('inventario_procesado')->default(false)->after('origen_importacion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('compras', 'cfdi_uuid'))
                $columnsToDrop[] = 'cfdi_uuid';
            if (Schema::hasColumn('compras', 'cfdi_serie'))
                $columnsToDrop[] = 'cfdi_serie';
            if (Schema::hasColumn('compras', 'cfdi_folio'))
                $columnsToDrop[] = 'cfdi_folio';
            if (Schema::hasColumn('compras', 'cfdi_fecha'))
                $columnsToDrop[] = 'cfdi_fecha';
            if (Schema::hasColumn('compras', 'cfdi_emisor_rfc'))
                $columnsToDrop[] = 'cfdi_emisor_rfc';
            if (Schema::hasColumn('compras', 'cfdi_emisor_nombre'))
                $columnsToDrop[] = 'cfdi_emisor_nombre';
            if (Schema::hasColumn('compras', 'cfdi_total'))
                $columnsToDrop[] = 'cfdi_total';
            if (Schema::hasColumn('compras', 'origen_importacion'))
                $columnsToDrop[] = 'origen_importacion';
            if (Schema::hasColumn('compras', 'inventario_procesado'))
                $columnsToDrop[] = 'inventario_procesado';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
