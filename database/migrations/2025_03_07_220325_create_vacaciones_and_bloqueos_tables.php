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
        if (!Schema::hasTable('vacaciones')) {
            Schema::create('vacaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->integer('dias_solicitados');
                $table->integer('dias_pendientes')->default(0);
                $table->integer('dias_aprobados')->default(0);
                $table->integer('dias_rechazados')->default(0);
                $table->text('motivo')->nullable();
                $table->string('estado')->default('pendiente');
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('aprobador_id')->nullable();
                $table->timestamp('fecha_aprobacion')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dias_bloqueados')) {
            Schema::create('dias_bloqueados', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->unsignedBigInteger('tecnico_id')->nullable()->index();
                $table->date('fecha');
                $table->string('motivo')->nullable();
                $table->time('hora_inicio')->nullable();
                $table->time('hora_fin')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dias_bloqueados');
        Schema::dropIfExists('vacaciones');
    }
};
