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

            // Multi-tenant
            $table->unsignedBigInteger('empresa_id')->index();

            // Vínculos
            $table->unsignedBigInteger('cliente_id')->nullable()->index();
            $table->unsignedBigInteger('cita_id')->nullable()->index();

            // WhatsApp del destinatario
            $table->string('wa_id', 32)->index();
            $table->string('nombre_cliente_snapshot', 200)->nullable();

            // Folio único de la encuesta (para tracking)
            $table->string('folio', 32)->unique();

            // State machine
            $table->string('estado', 20)->default('pendiente')
                ->comment('pendiente, en_progreso, completada, expirada, cancelada, fallida_envio');

            // Pregunta actual (1..5, 0=no iniciada)
            $table->unsignedTinyInteger('pregunta_actual')->default(0);

            // Respuestas: estructura
            // {
            //   "p1_satisfaccion": 5,
            //   "p2_puntualidad": 4,
            //   "p3_nps": 9,
            //   "p4_claridad": 5,
            //   "p5_comentario": "Excelente servicio",
            //   "completada_en_segundos": 142
            // }
            $table->jsonb('respuestas')->nullable();

            // Score calculado
            $table->decimal('calificacion_global', 3, 1)->nullable()->comment('Promedio p1+p2+p4 (1.0-5.0)');
            $table->unsignedTinyInteger('nps_score')->nullable()->comment('1-10 pregunta 3');

            // Código promocional generado al completar
            $table->string('codigo_promocional', 20)->nullable()->unique();
            $table->unsignedTinyInteger('descuento_porcentaje')->default(10);
            $table->string('servicio_aplicable', 40)->default('preventivo')
                ->comment('Categoría de servicio donde aplica el descuento');
            $table->timestampTz('codigo_expires_at')->nullable();
            $table->boolean('codigo_usado')->default(false);
            $table->timestampTz('codigo_usado_at')->nullable();
            $table->unsignedBigInteger('codigo_usado_cita_id')->nullable()->index();

            // Control de envíos y tiempos
            $table->timestampTz('programada_para')->nullable()
                ->comment('Cuándo se debe enviar el primer mensaje (24h post-completado)');
            $table->timestampTz('enviada_at')->nullable();
            $table->timestampTz('primera_respuesta_at')->nullable();
            $table->timestampTz('completada_at')->nullable();
            $table->unsignedTinyInteger('intentos_envio')->default(0);
            $table->text('ultimo_error_envio')->nullable();

            // Recordatorios (si después de X horas no responde, re-enviar una vez)
            $table->unsignedTinyInteger('recordatorios_enviados')->default(0);
            $table->timestampTz('proximo_recordatorio_at')->nullable();

            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'programada_para']);
            $table->index('codigo_promocional');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->nullOnDelete();
            $table->foreign('cita_id')->references('id')->on('citas')->nullOnDelete();
            $table->foreign('codigo_usado_cita_id')->references('id')->on('citas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_satisfaccion');
    }
};