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
        Schema::create('credenciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa_configuracion')->onDelete('cascade');
            $table->morphs('credentialable'); // Link to Cliente or PolizaServicio
            $table->string('nombre'); // Ej: Acceso SQL Server, Panel de Control
            $table->string('usuario');
            $table->text('password'); // Will be stored encrypted
            $table->string('host')->nullable();
            $table->string('puerto')->nullable();
            $table->text('notas')->nullable(); // Optional: can also be encrypted
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Add index for security auditing
        Schema::create('credenciales_accesos_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credencial_id')->constrained('credenciales')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('accion'); // Ej: revelado, editado
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credenciales_accesos_logs');
        Schema::dropIfExists('credenciales');
    }
};
