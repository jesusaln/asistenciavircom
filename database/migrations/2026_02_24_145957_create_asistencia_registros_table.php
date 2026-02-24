<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asistencia_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();

            $table->string('tipo', 30); // entry|exit|break_start|break_end
            $table->timestamp('registrado_at');
            $table->string('origen', 30)->default('web'); // web|app|manual

            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->unsignedInteger('precision_metros')->nullable();
            $table->string('direccion')->nullable();

            $table->string('selfie_path')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->text('notas')->nullable();
            $table->boolean('es_incidencia')->default(false);
            $table->text('motivo_incidencia')->nullable();
            $table->boolean('consentimiento_biometrico')->default(false);

            $table->timestamps();

            $table->index(['empresa_id', 'user_id', 'registrado_at']);
            $table->index(['empresa_id', 'almacen_id', 'registrado_at']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencia_registros');
    }
};
