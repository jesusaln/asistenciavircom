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
        $existingIndexes = collect(Schema::getConnection()->select("
            SELECT indexname FROM pg_indexes WHERE tablename = 'compras'
        "))->pluck('indexname')->toArray();

        Schema::table('compras', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('compras_tipo_estado_fecha_compra_index', $existingIndexes)) {
                $table->index(['tipo', 'estado', 'fecha_compra']);
            }
            if (!in_array('compras_created_by_index', $existingIndexes)) {
                $table->index('created_by');
            }
            if (!in_array('compras_user_id_index', $existingIndexes)) {
                $table->index('user_id');
            }
            if (!in_array('compras_categoria_gasto_id_index', $existingIndexes)) {
                $table->index('categoria_gasto_id');
            }
            if (!in_array('compras_proyecto_id_index', $existingIndexes)) {
                $table->index('proyecto_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            //
        });
    }
};
