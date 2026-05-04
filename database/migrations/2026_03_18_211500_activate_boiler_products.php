<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Activar productos de calderas/boilers de gas LP para el catálogo web
        DB::table('productos')
            ->where('nombre', 'ilike', '%boiler%')
            ->where('nombre', 'ilike', '%gas lp%')
            ->update([
                'catalogo_web' => true,
                'estado' => 'activo'
            ]);
            
        // También activar uno de prueba (Cable) que usamos antes si no existieran los anteriores
        DB::table('productos')
            ->where('id', 104)
            ->update(['catalogo_web' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertiremos para no afectar el estado previo si ya estaban activos
    }
};
