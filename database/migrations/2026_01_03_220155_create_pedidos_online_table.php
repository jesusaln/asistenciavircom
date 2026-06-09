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
        Schema::create('pedidos_online', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas');
            $table->string('numero_pedido')->unique();
            $table->foreignId('cliente_tienda_id')->nullable()->constrained('clientes_tienda'); // E-commerce client
            $table->foreignId('cliente_id')->nullable()->constrained('clientes'); // Portal client
            $table->string('email');
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->json('direccion_envio');
            $table->json('items');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('costo_envio', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->string('metodo_pago');
            $table->string('estado')->default('pendiente');
            $table->string('payment_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->json('payment_details')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_online');
    }
};
