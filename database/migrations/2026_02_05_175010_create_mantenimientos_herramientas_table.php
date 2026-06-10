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
        if (!Schema::hasTable('mantenimientos_herramientas')) {
            Schema::create('mantenimientos_herramientas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('herramienta_id')->constrained()->onDelete('cascade');
                $table->date('fecha_mantenimiento');
                $table->decimal('costo', 10, 2)->nullable()->default(0);
                $table->text('descripcion');
                $table->foreignId('realizado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tipo')->default('preventivo'); // preventivo, correctivo
                $table->timestamps();

                $table->index('fecha_mantenimiento');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos_herramientas');
    }
};
