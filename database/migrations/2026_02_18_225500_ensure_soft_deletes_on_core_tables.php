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
            'productos',
            'facturas',
            'cfdis',
            'clientes',
            'ventas',
            'cotizaciones',
            'pedidos',
            'compras',
            'orden_compras',
            'inventarios',
            'herramientas',
            'cuentas_por_pagar',
            'citas',
            'tickets'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->softDeletes();
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No drops for safety
    }
};
