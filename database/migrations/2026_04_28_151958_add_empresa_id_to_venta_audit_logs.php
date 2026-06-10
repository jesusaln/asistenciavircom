<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venta_audit_logs') && !Schema::hasColumn('venta_audit_logs', 'empresa_id')) {
            Schema::table('venta_audit_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->nullable()->default(8)->after('id');
                $table->index('empresa_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('venta_audit_logs') && Schema::hasColumn('venta_audit_logs', 'empresa_id')) {
            Schema::table('venta_audit_logs', function (Blueprint $table) {
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }
};
