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
        Schema::table('entregas_dinero', function (Blueprint $table) {
            if (!Schema::hasColumn('entregas_dinero', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id_origen');
                $table->foreign('parent_id')->references('id')->on('entregas_dinero')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entregas_dinero', function (Blueprint $table) {
            if (Schema::hasColumn('entregas_dinero', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
