<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Producto;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Activar todos los productos con fotos
        Producto::whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->where('estado', 'activo')
            ->update(['catalogo_web' => true]);

        // Desactivar todos los productos sin fotos
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
    }
};
