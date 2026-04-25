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
        Schema::table('citas', function (Blueprint $table) {
            if (!Schema::hasColumn('citas', 'latitud')) {
                $table->decimal('latitud', 10, 8)->nullable()->after('direccion_referencias');
            }
            if (!Schema::hasColumn('citas', 'longitud')) {
                $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            if (Schema::hasColumn('citas', 'latitud')) {
                $table->dropColumn('latitud');
            }
            if (Schema::hasColumn('citas', 'longitud')) {
                $table->dropColumn('longitud');
            }
        });
    }
};
