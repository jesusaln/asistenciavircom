<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedidos_online_bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_online_id')->constrained('pedidos_online')->onDelete('cascade');
            $table->string('accion'); // Ej: CREACION, PAGO, ENVIO_CVA, ACTUALIZACION_GUIA
            $table->text('descripcion');
            $table->foreignId('usuario_id')->nullable()->constrained('users'); // Quién lo hizo (null = sistema/cliente)
            $table->json('metadata')->nullable(); // Datos técnicos (JSON de respuesta CVA, IP cliente, etc)
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_online_bitacora');
    }
};
