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
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_messages', 'cliente_id')) {
                $table->unsignedBigInteger('cliente_id')->nullable()->after('empresa_id');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'campania_id')) {
                $table->unsignedBigInteger('campania_id')->nullable()->after('cliente_id');
            }
        });

        // Comprobación de índices fuera del blueprint para mayor compatibilidad
        $indexes = Schema::getIndexes('whatsapp_messages');
        $indexNames = array_column($indexes, 'name');
        
        Schema::table('whatsapp_messages', function (Blueprint $table) use ($indexNames) {
            if (!in_array('whatsapp_messages_cliente_id_campania_id_index', $indexNames)) {
                $table->index(['cliente_id', 'campania_id']);
            }
            if (!in_array('whatsapp_messages_campania_id_index', $indexNames)) {
                $table->index('campania_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = Schema::getIndexes('whatsapp_messages');
        $indexNames = array_column($indexes, 'name');

        Schema::table('whatsapp_messages', function (Blueprint $table) use ($indexNames) {
            if (in_array('whatsapp_messages_cliente_id_campania_id_index', $indexNames)) {
                $table->dropIndex(['cliente_id', 'campania_id']);
            }
            if (in_array('whatsapp_messages_campania_id_index', $indexNames)) {
                $table->dropIndex(['campania_id']);
            }

            if (Schema::hasColumn('whatsapp_messages', 'cliente_id')) {
                $table->dropColumn('cliente_id');
            }
            if (Schema::hasColumn('whatsapp_messages', 'campania_id')) {
                $table->dropColumn('campania_id');
            }
        });
    }
};
