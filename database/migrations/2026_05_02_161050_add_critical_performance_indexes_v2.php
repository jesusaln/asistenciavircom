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
        Schema::table('producto_series', function (Blueprint $table) {
            // Índice para búsquedas rápidas de números de serie (Garantías, Inventario)
            if (!Schema::hasIndex('producto_series', 'producto_series_numero_serie_index')) {
                $table->index('numero_serie');
            }
        });

        Schema::table('cfdis', function (Blueprint $table) {
            // Índice para búsqueda de folios y series (SAT Compliance)
            if (!Schema::hasIndex('cfdis', 'cfdis_folio_index')) {
                $table->index('folio');
            }
            if (!Schema::hasIndex('cfdis', 'cfdis_uuid_index')) {
                $table->index('uuid');
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            // Índice para el número de venta
            if (!Schema::hasIndex('ventas', 'ventas_numero_venta_index')) {
                $table->index('numero_venta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producto_series', function (Blueprint $table) {
            $table->dropIndex(['numero_serie']);
        });

        Schema::table('cfdis', function (Blueprint $table) {
            $table->dropIndex(['folio']);
            $table->dropIndex(['uuid']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['numero_venta']);
        });
    }
};
