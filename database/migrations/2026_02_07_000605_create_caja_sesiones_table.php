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
        Schema::create('caja_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->integer('almacen_id')->index(); // Typically linked to Almacen but keeping integer for flexibility if Almacen model varies

            $table->decimal('monto_inicial', 10, 2)->default(0);

            // Closing totals
            $table->decimal('total_ventas_efectivo', 10, 2)->nullable();
            $table->decimal('total_entradas', 10, 2)->nullable(); // Other cash ins
            $table->decimal('total_salidas', 10, 2)->nullable(); // Cash outs (withdrawals)

            $table->decimal('monto_final_sistema', 10, 2)->nullable(); // Calculated expected cash
            $table->decimal('monto_declarado', 10, 2)->nullable(); // Counted by cashier
            $table->decimal('diferencia', 10, 2)->nullable();

            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();

            $table->string('estado', 20)->default('abierta'); // abierta, cerrada

            $table->json('detalles_cierre')->nullable(); // Stores { '500': 2, '200': 5... }
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_sesiones');
    }
};
