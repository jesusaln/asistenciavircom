<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cfdi_conceptos')) {
            return;
        }

        Schema::table('cfdi_conceptos', function (Blueprint $table) {
            if (!Schema::hasColumn('cfdi_conceptos', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->constrained()->cascadeOnDelete()->after('cfdi_id');
                $table->index('empresa_id');
            }
        });

        // Backfill empresa_id for existing rows from the parent cfdis table
        if (Schema::hasColumn('cfdi_conceptos', 'empresa_id') && Schema::hasTable('cfdis')) {
            DB::statement('
                UPDATE cfdi_conceptos cc
                SET empresa_id = c.empresa_id
                FROM cfdis c
                WHERE cc.cfdi_id = c.id
                AND cc.empresa_id IS NULL
            ');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('cfdi_conceptos')) {
            return;
        }

        Schema::table('cfdi_conceptos', function (Blueprint $table) {
            if (Schema::hasColumn('cfdi_conceptos', 'empresa_id')) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }
};
