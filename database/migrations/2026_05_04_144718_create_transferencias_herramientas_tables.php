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
        if (!Schema::hasTable('transferencias_herramientas')) {
            Schema::create('transferencias_herramientas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emisor_id')->constrained('users');
                $table->foreignId('receptor_id')->constrained('users');
                $table->enum('estado', ['pendiente', 'aceptada', 'rechazada', 'cancelada'])->default('pendiente');
                $table->text('observaciones')->nullable();
                $table->foreignId('empresa_id')->nullable()->constrained('empresas');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transferencia_herramienta_items')) {
            Schema::create('transferencia_herramienta_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transferencia_id')->constrained('transferencias_herramientas')->onDelete('cascade');
                $table->foreignId('herramienta_id')->constrained('herramientas');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencia_herramienta_items');
        Schema::dropIfExists('transferencias_herramientas');
    }
};
