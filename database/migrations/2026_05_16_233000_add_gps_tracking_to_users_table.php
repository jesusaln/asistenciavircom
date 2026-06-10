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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'latitud')) {
                $table->decimal('latitud', 10, 8)->nullable()->after('fcm_token');
            }
            if (!Schema::hasColumn('users', 'longitud')) {
                $table->decimal('longitud', 10, 8)->nullable()->after('latitud');
            }
            if (!Schema::hasColumn('users', 'ultima_fecha_gps')) {
                $table->timestamp('ultima_fecha_gps')->nullable()->after('longitud');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud', 'ultima_fecha_gps']);
        });
    }
};
