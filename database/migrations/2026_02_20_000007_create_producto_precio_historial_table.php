<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('producto_precio_historial')) {
            Schema::create('producto_precio_historial', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('producto_id');
                $table->unsignedBigInteger('user_id')->nullable();

                $table->decimal('precio_compra_anterior', 15, 2)->nullable();
                $table->decimal('precio_compra_nuevo', 15, 2)->nullable();
                $table->decimal('precio_venta_anterior', 15, 2)->nullable();
                $table->decimal('precio_venta_nuevo', 15, 2)->nullable();

                $table->string('tipo_cambio')->nullable(); // Ej: 'compra', 'manual'
                $table->text('notas')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
    }
};
