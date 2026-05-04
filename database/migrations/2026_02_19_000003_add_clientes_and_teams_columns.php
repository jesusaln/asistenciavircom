<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns to clientes table using raw SQL
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN uso_cfdi VARCHAR(10)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN regimen_fiscal VARCHAR(10)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN forma_pago_default VARCHAR(10)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN nombre_fiscal VARCHAR(255)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN calle VARCHAR(255)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN numero_exterior VARCHAR(50)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN numero_interior VARCHAR(50)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN colonia VARCHAR(255)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN codigo_postal VARCHAR(10)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN municipio VARCHAR(255)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE clientes ADD COLUMN estado VARCHAR(255)');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement("ALTER TABLE clientes ADD COLUMN pais VARCHAR(255) DEFAULT 'MX'");
        } catch (\Exception $e) {
            // Column might already exist
        }

        // Add missing columns to teams table
        try {
            DB::statement('ALTER TABLE teams ADD COLUMN owned_by BIGINT');
        } catch (\Exception $e) {
            // Column might already exist
        }
        try {
            DB::statement('ALTER TABLE teams ADD COLUMN personal_team BOOLEAN DEFAULT false');
        } catch (\Exception $e) {
            // Column might already exist
        }

        // Add foreign key for owned_by
        try {
            DB::statement('ALTER TABLE teams ADD CONSTRAINT teams_owned_by_foreign FOREIGN KEY (owned_by) REFERENCES users(id) ON DELETE CASCADE');
        } catch (\Exception $e) {
            // Foreign key might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for this fix
    }
};
