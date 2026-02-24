<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Tablas y sus columnas que deben ser únicas POR empresa
        $indices = [
            'productos' => ['sku', 'codigo'],
            'ventas' => ['numero_venta'],
            'pedidos' => ['numero_pedido'],
            'cotizaciones' => ['numero_cotizacion'],
            'clientes' => ['codigo'],
            'proveedores' => ['codigo'],
            'compras' => ['numero_compra'],
            'almacenes' => ['codigo'],
            'folio_configs' => ['document_type']
        ];

        foreach ($indices as $table => $columns) {
            if (!Schema::hasTable($table))
                continue;

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column))
                        continue;

                    // 1. Intentar eliminar el índice único anterior si existe
                    // Usamos SQL directo para PostgreSQL para aprovechar 'IF EXISTS' y evitar fallos que aborten la transacción
                    $indexName = "{$table}_{$column}_unique";
                    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS \"{$indexName}\"");

                    // También intentamos el nombre que Laravel genera a veces (pueden ser varios)
                    // pero con IF EXISTS no fallará si no existen.

                    // 2. Crear el nuevo índice único compuesto (columna + empresa_id)
                    $blueprint->unique([$column, 'empresa_id'], "{$table}_{$column}_empresa_unique");
                }
            });
        }
    }

    public function down(): void
    {
        // No destructivo
    }
};
