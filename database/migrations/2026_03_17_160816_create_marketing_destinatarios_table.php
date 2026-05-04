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
        Schema::create('marketing_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('campana_id')->constrained('marketing_campanas')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->enum('estado', ['pendiente', 'enviado', 'entregado', 'leido', 'fallido'])->default('pendiente');
            $table->string('external_message_id')->nullable()->index(); // ID de Meta/Twilio
            $table->text('error_mensaje')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_destinatarios');
    }
};
