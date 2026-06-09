<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rustdesk_id', 30)->nullable()->after('telefono');
            $table->string('rustdesk_alias', 100)->nullable()->after('rustdesk_id');
            $table->index('rustdesk_id');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['rustdesk_id']);
            $table->dropColumn(['rustdesk_id', 'rustdesk_alias']);
        });
    }
};

