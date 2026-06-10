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
        if (Schema::hasTable('productos')) {
            try {
                DB::statement('ALTER TABLE productos ALTER COLUMN codigo_barras DROP NOT NULL');
            } catch (\Throwable $e) {}
            try {
                DB::statement('ALTER TABLE productos ALTER COLUMN categoria_id DROP NOT NULL');
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse
    }
};
