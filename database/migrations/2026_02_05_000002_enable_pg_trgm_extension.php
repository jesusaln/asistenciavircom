<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Habilitar extensión pg_trgm para búsquedas trigram eficientes (semántica/fuzzy)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Habilitar unaccent para búsquedas insensibles a acentos
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No deshabilitamos extensiones generalmente en down() 
        // porque otros esquemas podrían depender de ellas.
        // DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
};
