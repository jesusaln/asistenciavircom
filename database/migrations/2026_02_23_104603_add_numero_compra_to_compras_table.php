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
        if (Schema::hasTable('compras')) {
            Schema::table('compras', function (Blueprint $table) {
                if (!Schema::hasColumn('compras', 'numero_compra')) {
                    if (Schema::hasColumn('compras', 'folio')) {
                        $table->renameColumn('folio', 'numero_compra');
                    } else {
                        $table->string('numero_compra')->nullable()->index();
                    }
                }

                if (!Schema::hasColumn('compras', 'fecha_compra')) {
                    $table->date('fecha_compra')->nullable();
                }

                if (!Schema::hasColumn('compras', 'metodo_pago')) {
                    $table->string('metodo_pago')->nullable();
                }

                if (!Schema::hasColumn('compras', 'cfdi_uuid')) {
                    $table->string('cfdi_uuid')->nullable();
                }

                if (!Schema::hasColumn('compras', 'cfdi_fecha')) {
                    $table->dateTime('cfdi_fecha')->nullable();
                }

                if (!Schema::hasColumn('compras', 'origen_importacion')) {
                    $table->string('origen_importacion')->default('manual');
                }

                if (!Schema::hasColumn('compras', 'cfdi_folio')) {
                    $table->string('cfdi_folio')->nullable();
                }

                if (!Schema::hasColumn('compras', 'cfdi_serie')) {
                    $table->string('cfdi_serie')->nullable();
                }

                if (!Schema::hasColumn('compras', 'cfdi_emisor_rfc')) {
                    $table->string('cfdi_emisor_rfc')->nullable();
                }

                if (!Schema::hasColumn('compras', 'cfdi_emisor_nombre')) {
                    $table->string('cfdi_emisor_nombre')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('compras') && Schema::hasColumn('compras', 'numero_compra')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->renameColumn('numero_compra', 'folio');
            });
        }
    }
};
