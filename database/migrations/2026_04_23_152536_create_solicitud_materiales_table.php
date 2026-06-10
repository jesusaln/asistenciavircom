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
        if (!Schema::hasTable('solicitud_materiales')) {
            Schema::create('solicitud_materiales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('folio')->unique();
                $table->string('tipo')->comment('piezas, refacciones, herramienta, consumibles, otro');
                $table->string('prioridad')->default('Media')->comment('Baja, Media, Alta, Urgente');
                $table->string('estado')->default('Pendiente')->comment('Pendiente, En Proceso, Entregado, Rechazado');
                $table->text('motivo')->nullable();
                $table->text('comentarios_admin')->nullable();
                $table->date('fecha_requerida')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_materiales');
    }
};
