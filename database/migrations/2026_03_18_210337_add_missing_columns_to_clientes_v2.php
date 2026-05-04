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
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'destacado')) {
                $table->boolean('destacado')->default(false)->after('activo');
            }
            if (!Schema::hasColumn('clientes', 'owned_by')) {
                $table->foreignId('owned_by')->nullable()->constrained('users')->nullOnDelete()->after('empresa_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['destacado', 'owned_by']);
        });
    }
};
