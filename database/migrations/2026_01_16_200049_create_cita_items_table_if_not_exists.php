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
        if (!Schema::hasTable('cita_items')) {
            Schema::create('cita_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
                $table->morphs('citable'); // Producto o Servicio
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio', 15, 2);
                $table->decimal('descuento', 5, 2)->default(0); // Porcentaje
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2);
                $table->text('notas')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cita_items');
    }
};
