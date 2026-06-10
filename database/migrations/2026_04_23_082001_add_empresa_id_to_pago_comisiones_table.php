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
        if (Schema::hasTable('pagos_comisiones')) {
            if (!Schema::hasColumn('pagos_comisiones', 'empresa_id')) {
                Schema::table('pagos_comisiones', function (Blueprint $table) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                    
                    if (Schema::hasTable('empresas')) {
                        $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_comisiones', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['empresa_id']);
            }
            $table->dropColumn('empresa_id');
        });
    }
};
