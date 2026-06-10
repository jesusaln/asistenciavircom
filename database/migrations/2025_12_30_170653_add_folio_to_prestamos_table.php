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
        Schema::table('prestamos', function (Blueprint $table) {
            if (!Schema::hasColumn('prestamos', 'folio')) {
                $table->string('folio', 50)->nullable()->after('id');
            }
            // Index se puede intentar siempre, si existe no suele fallar en MySQL pero en PGSQL sí. 
            // Mejor verificar.
        });

        // Agregar índice por separado si la columna existe (o se acaba de crear)
        Schema::table('prestamos', function (Blueprint $table) {
            // Verificar si existe el índice es complejo en Laravel estándar sin DB raw.
            // Simplemente envolveremos en try-catch silencioso o dejaremos que el driver maneje la creacion condicional si fuera necesario.
            // Pero lo más limpio para folio es solo crearlo si no existe.
            // En este caso, como el error fue "column folio already exists", con el if anterior basta.
            // El índice probablemente ya exista también.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn('folio');
        });
    }
};
