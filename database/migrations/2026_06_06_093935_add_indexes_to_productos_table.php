<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_productos_estado ON productos (estado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_productos_deleted_at ON productos (deleted_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_productos_tipo ON productos (tipo_producto)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_productos_destacado ON productos (destacado)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_productos_catalogo_web ON productos (catalogo_web)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_productos_sitemap ON productos (estado) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_productos_estado');
        DB::statement('DROP INDEX IF EXISTS idx_productos_deleted_at');
        DB::statement('DROP INDEX IF EXISTS idx_productos_tipo');
        DB::statement('DROP INDEX IF EXISTS idx_productos_destacado');
        DB::statement('DROP INDEX IF EXISTS idx_productos_catalogo_web');
        DB::statement('DROP INDEX IF EXISTS idx_productos_sitemap');
    }
};
