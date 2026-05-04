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
        // Helper to check index existence in PostgreSQL
        $indexExists = function ($table, $index) {
            $conn = Schema::getConnection();
            $results = $conn->select("SELECT count(*) FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $index]);
            return $results[0]->count > 0;
        };

        // Movimientos Bancarios
        Schema::table('movimientos_bancarios', function (Blueprint $blueprint) use ($indexExists) {
            if (!$indexExists('movimientos_bancarios', 'movimientos_bancarios_referencia_index'))
                $blueprint->index('referencia');
            if (!$indexExists('movimientos_bancarios', 'movimientos_bancarios_estado_index'))
                $blueprint->index('estado');
            if (!$indexExists('movimientos_bancarios', 'movimientos_bancarios_tipo_index'))
                $blueprint->index('tipo');
            if (!$indexExists('movimientos_bancarios', 'movimientos_bancarios_fecha_index'))
                $blueprint->index('fecha');
        });

        // Facturas
        Schema::table('facturas', function (Blueprint $blueprint) use ($indexExists) {
            if (!$indexExists('facturas', 'facturas_folio_index'))
                $blueprint->index('folio');
            if (!$indexExists('facturas', 'facturas_numero_factura_index'))
                $blueprint->index('numero_factura');
            if (!$indexExists('facturas', 'facturas_estado_index'))
                $blueprint->index('estado');
            if (!$indexExists('facturas', 'facturas_fecha_emision_index'))
                $blueprint->index('fecha_emision');
        });

        // CFDIs
        Schema::table('cfdis', function (Blueprint $blueprint) use ($indexExists) {
            if (!$indexExists('cfdis', 'cfdis_uuid_index'))
                $blueprint->index('uuid');
            if (!$indexExists('cfdis', 'cfdis_rfc_emisor_index'))
                $blueprint->index('rfc_emisor');
            if (!$indexExists('cfdis', 'cfdis_rfc_receptor_index'))
                $blueprint->index('rfc_receptor');
            if (!$indexExists('cfdis', 'cfdis_fecha_emision_index'))
                $blueprint->index('fecha_emision');
            if (!$indexExists('cfdis', 'cfdis_estatus_index'))
                $blueprint->index('estatus');
            if (!$indexExists('cfdis', 'cfdis_folio_index'))
                $blueprint->index('folio');
            if (!$indexExists('cfdis', 'cfdis_tipo_comprobante_index'))
                $blueprint->index('tipo_comprobante');
        });

        // Ventas
        Schema::table('ventas', function (Blueprint $blueprint) use ($indexExists) {
            if (!$indexExists('ventas', 'ventas_numero_venta_index'))
                $blueprint->index('numero_venta');
            if (!$indexExists('ventas', 'ventas_estado_index'))
                $blueprint->index('estado');
            if (!$indexExists('ventas', 'ventas_fecha_index'))
                $blueprint->index('fecha');
            if (!$indexExists('ventas', 'ventas_folio_index'))
                $blueprint->index('folio');
            if (!$indexExists('ventas', 'ventas_pagado_index'))
                $blueprint->index('pagado');
        });

        // Clientes
        Schema::table('clientes', function (Blueprint $blueprint) use ($indexExists) {
            if (!$indexExists('clientes', 'clientes_rfc_index'))
                $blueprint->index('rfc');
            if (!$indexExists('clientes', 'clientes_uuid_index'))
                $blueprint->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $blueprint) {
            $blueprint->dropIndex(['numero_venta']);
            $blueprint->dropIndex(['estado']);
            $blueprint->dropIndex(['fecha']);
            $blueprint->dropIndex(['folio']);
            $blueprint->dropIndex(['pagado']);
        });

        Schema::table('cfdis', function (Blueprint $blueprint) {
            $blueprint->dropIndex(['uuid']);
            $blueprint->dropIndex(['rfc_emisor']);
            $blueprint->dropIndex(['rfc_receptor']);
            $blueprint->dropIndex(['fecha_emision']);
            $blueprint->dropIndex(['estatus']);
            $blueprint->dropIndex(['folio']);
            $blueprint->dropIndex(['tipo_comprobante']);
        });

        Schema::table('facturas', function (Blueprint $blueprint) {
            $blueprint->dropIndex(['folio']);
            $blueprint->dropIndex(['numero_factura']);
            $blueprint->dropIndex(['estado']);
            $blueprint->dropIndex(['fecha_emision']);
        });

        Schema::table('movimientos_bancarios', function (Blueprint $blueprint) {
            $blueprint->dropIndex(['referencia']);
            $blueprint->dropIndex(['estado']);
            $blueprint->dropIndex(['tipo']);
            $blueprint->dropIndex(['fecha']);
        });

        Schema::table('clientes', function (Blueprint $blueprint) {
            $blueprint->dropIndex(['rfc']);
            $blueprint->dropIndex(['uuid']);
        });
    }
};
