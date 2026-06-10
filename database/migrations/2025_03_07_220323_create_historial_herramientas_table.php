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
        if (!Schema::hasTable('historial_herramientas')) {
            Schema::create('historial_herramientas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('herramienta_id')->index();
                $table->unsignedBigInteger('tecnico_id')->index();
                $table->timestamp('fecha_asignacion');
                $table->timestamp('fecha_devolucion')->nullable();
                $table->unsignedBigInteger('asignado_por')->nullable();
                $table->unsignedBigInteger('recibido_por')->nullable();
                $table->text('observaciones_asignacion')->nullable();
                $table->text('observaciones_devolucion')->nullable();
                $table->string('motivo_devolucion')->nullable();
                $table->string('estado_herramienta_asignacion')->nullable();
                $table->string('estado_herramienta_devolucion')->nullable();
                $table->integer('duracion_dias')->nullable();
                $table->unsignedBigInteger('asignacion_masiva_id')->nullable();
                $table->string('codigo_asignacion')->nullable();
                $table->string('proyecto_trabajo')->nullable();
                $table->string('tipo_asignacion')->default('individual');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_herramientas');
    }
};
