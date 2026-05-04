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
        Schema::create('inventarios_fisicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nombre');
            $table->string('estado')->default('borrador'); // borrador, procesado, cancelado
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_fisico_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_fisico_id')->constrained('inventarios_fisicos')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->decimal('stock_sistema', 12, 4)->default(0);
            $table->decimal('stock_fisico', 12, 4)->default(0);
            $table->decimal('diferencia', 12, 4)->default(0);
            $table->boolean('ajustado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_fisico_items');
        Schema::dropIfExists('inventarios_fisicos');
    }
};
