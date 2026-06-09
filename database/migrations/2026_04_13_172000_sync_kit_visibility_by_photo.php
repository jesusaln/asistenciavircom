<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Activar todos los productos (incluyendo kits) que tengan fotos
        Producto::whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->where('estado', 'activo')
            ->update(['catalogo_web' => true]);

        // Desactivar todos los productos que NO tengan fotos
        Producto::where(function($q) {
            $q->whereNull('imagen')->orWhere('imagen', '');
        })
        ->update(['catalogo_web' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay una forma segura de revertir esto sin perder cambios manuales previos,
        // pero podríamos desactivar todos los kits de la web si fuera necesario.
    }
};
