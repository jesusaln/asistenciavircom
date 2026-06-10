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
        if (!Schema::hasTable('rentas')) {
            return;
        }

        Schema::table('rentas', function (Blueprint $table) {
            if (!Schema::hasColumn('rentas', 'ine_frontal')) {
                $table->string('ine_frontal')->nullable()->after('firma_hash');
            }
            if (!Schema::hasColumn('rentas', 'ine_trasera')) {
                $table->string('ine_trasera')->nullable()->after('ine_frontal');
            }
            if (!Schema::hasColumn('rentas', 'comprobante_domicilio')) {
                $table->string('comprobante_domicilio')->nullable()->after('ine_trasera');
            }
            if (!Schema::hasColumn('rentas', 'solicitud_renta')) {
                $table->string('solicitud_renta')->nullable()->after('comprobante_domicilio');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('rentas')) {
            return;
        }

        Schema::table('rentas', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('rentas', 'ine_frontal')) {
                $dropColumns[] = 'ine_frontal';
            }
            if (Schema::hasColumn('rentas', 'ine_trasera')) {
                $dropColumns[] = 'ine_trasera';
            }
            if (Schema::hasColumn('rentas', 'comprobante_domicilio')) {
                $dropColumns[] = 'comprobante_domicilio';
            }
            if (Schema::hasColumn('rentas', 'solicitud_renta')) {
                $dropColumns[] = 'solicitud_renta';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
