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
        // Tabla de proyectos
        if (!Schema::hasTable('proyectos')) {
            Schema::create('proyectos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
                $table->string('color')->default('#3B82F6'); // Color por defecto azul
                $table->timestamps();
            });
        }

        // Tabla de tareas de proyectos
        if (!Schema::hasTable('proyecto_tareas')) {
            Schema::create('proyecto_tareas', function (Blueprint $table) {
                $table->id();
                $table->string('titulo');
                $table->text('descripcion')->nullable();
                $table->string('estado')->default('pendiente'); // pendiente, en_progreso, completada
                $table->string('prioridad')->default('media'); // baja, media, alta
                $table->integer('orden')->default(0);
                $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // Tabla pivot para compartir proyectos con usuarios
        if (!Schema::hasTable('proyecto_user')) {
            Schema::create('proyecto_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('role')->default('viewer'); // viewer, editor, admin
                $table->timestamps();

                $table->unique(['proyecto_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyecto_user');
        Schema::dropIfExists('proyecto_tareas');
        Schema::dropIfExists('proyectos');
    }
};
