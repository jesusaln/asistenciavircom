<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_satisfaccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('cita_id')->nullable()->unique()->constrained('citas')->nullOnDelete();
            $table->string('wa_id')->nullable()->index();
            $table->unsignedTinyInteger('calificacion')->nullable();
            $table->text('comentario')->nullable();
            $table->string('cupon_codigo')->nullable()->unique();
            $table->unsignedTinyInteger('cupon_porcentaje')->default(10);
            $table->timestamp('cupon_vigencia_hasta')->nullable();
            $table->timestamp('respondida_at')->nullable();
            $table->string('origen')->default('whatsapp');
            $table->timestamps();

            $table->index(['empresa_id', 'respondida_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_satisfaccion');
    }
};
