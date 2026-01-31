<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('poliza_audit_logs', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('user_id')->constrained('clientes')->onDelete('set null');
            $table->boolean('system_event')->default(false)->after('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poliza_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'system_event']);
        });
    }
};
