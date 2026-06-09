<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 1 - Mejora 1.2: Idempotencia en consumo de folios
 * Evita que un mismo ticket descuente dos veces el consumo de la póliza
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Timestamp que registra cuándo se descontó el folio de la póliza
            // Si tiene valor, significa que ya se registró el consumo
            $table->timestamp('consumo_registrado_at')->nullable()->after('poliza_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('consumo_registrado_at');
        });
    }
};
