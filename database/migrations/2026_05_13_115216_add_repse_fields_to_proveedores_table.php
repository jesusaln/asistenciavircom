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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->boolean('is_repse')->default(false);
            $table->string('repse_number')->nullable();
            $table->date('repse_expiry')->nullable();
            $table->text('repse_activity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['is_repse', 'repse_number', 'repse_expiry', 'repse_activity']);
        });
    }
};
