<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        $tables = ['clientes', 'proveedores', 'cotizaciones', 'pedidos'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'empresa_id')) {
                        $table->foreignId('empresa_id')->nullable()->after('id')->index();
                    }
                });
            }
        }
    }

    public function down()
    {
        $tables = ['clientes', 'proveedores', 'cotizaciones', 'pedidos'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'empresa_id')) {
                        $table->dropColumn('empresa_id');
                    }
                });
            }
        }
    }
};
