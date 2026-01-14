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
        $tables = [
            'users',
            'clientes',
            'proveedores',
            'cotizaciones',
            'pedidos',
            'ventas',
            'price_lists',
            'productos',
            'almacenes',
            'facturas',
            'ordenes_compra',
            'citas',
            'mantenimientos',
            'tickets',
            'ticket_categories',
            'knowledge_base_articles',
            'polizas_servicio'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'empresa_id')) {
                        $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                    }
                });

                // Asignar empresa_id = 8 (ID de la única empresa actual) a todos los registros
                \Illuminate\Support\Facades\DB::table($tableName)->whereNull('empresa_id')->update(['empresa_id' => 8]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed as this is a corrective migration
    }
};
