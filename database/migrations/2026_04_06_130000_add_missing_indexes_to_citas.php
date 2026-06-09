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
        Schema::table('citas', function (Blueprint $table) {
            $existingIndexes = Schema::getIndexes('citas');
            $indexNames = array_column($existingIndexes, 'name');

            if (!in_array('idx_citas_seguimiento', $indexNames)) {
                $table->index('link_seguimiento', 'idx_citas_seguimiento');
            }
            
            if (!in_array('idx_citas_fecha_confirmada', $indexNames)) {
                $table->index('fecha_confirmada', 'idx_citas_fecha_confirmada');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropIndex('idx_citas_seguimiento');
            $table->dropIndex('idx_citas_fecha_confirmada');
        });
    }
};
