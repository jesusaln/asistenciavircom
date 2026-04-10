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
            $table->unsignedBigInteger('cliente_id')->nullable()->after('empresa_id');
            $table->unsignedBigInteger('campania_id')->nullable()->after('cliente_id');
            
            // Índices para búsquedas rápidas de historial
            $table->index(['cliente_id', 'campania_id']);
            $table->index('campania_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex(['cliente_id', 'campania_id']);
            $table->dropIndex(['campania_id']);
            $table->dropColumn(['cliente_id', 'campania_id']);
        });
    }
};
