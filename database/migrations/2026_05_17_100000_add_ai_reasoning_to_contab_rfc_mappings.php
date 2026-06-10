<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contab_rfc_mappings', function (Blueprint $table) {
            if (!Schema::hasColumn('contab_rfc_mappings', 'ai_reasoning')) {
                $table->text('ai_reasoning')->nullable()->after('nombre_auxiliar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contab_rfc_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('contab_rfc_mappings', 'ai_reasoning')) {
                $table->dropColumn('ai_reasoning');
            }
        });
    }
};
