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
        Schema::table('sat_descargas_masivas', function (Blueprint $table) {
            if (!Schema::hasColumn('sat_descargas_masivas', 'retry_count')) {
                $table->integer('retry_count')->default(0)->after('created_by');
            }
            if (!Schema::hasColumn('sat_descargas_masivas', 'max_retries')) {
                $table->integer('max_retries')->default(3)->after('retry_count');
            }
            if (!Schema::hasColumn('sat_descargas_masivas', 'next_retry_at')) {
                $table->timestamp('next_retry_at')->nullable()->after('max_retries');
            }
            if (!Schema::hasColumn('sat_descargas_masivas', 'limite_tipo')) {
                $table->string('limite_tipo')->nullable()->after('next_retry_at');
            }
            if (!Schema::hasColumn('sat_descargas_masivas', 'mensaje_usuario')) {
                $table->text('mensaje_usuario')->nullable()->after('limite_tipo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sat_descargas_masivas', function (Blueprint $table) {
            $cols = [];
            foreach (['retry_count', 'max_retries', 'next_retry_at', 'limite_tipo', 'mensaje_usuario'] as $col) {
                if (Schema::hasColumn('sat_descargas_masivas', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
