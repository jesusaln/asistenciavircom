<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contab_polizas', function (Blueprint $table) {
            if (!Schema::hasColumn('contab_polizas', 'soportes')) {
                $table->json('soportes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contab_polizas', function (Blueprint $table) {
            $table->dropColumn('soportes');
        });
    }
};
