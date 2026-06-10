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
        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('contrato_plantilla_id')->nullable()->constrained('contrato_plantillas')->onDelete('set null');
            $table->string('signing_token')->unique()->nullable();
            $table->text('signature_client')->nullable();
            $table->text('signature_provider')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['contrato_plantilla_id']);
            $table->dropColumn(['cliente_id', 'contrato_plantilla_id', 'signing_token', 'signature_client', 'signature_provider']);
        });
    }
};
