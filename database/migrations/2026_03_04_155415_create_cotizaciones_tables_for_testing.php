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
        if (!Schema::hasTable('cotizaciones')) {
            Schema::create('cotizaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->unsignedBigInteger('almacen_id')->nullable()->index();
                $table->string('numero_cotizacion')->nullable()->index();
                $table->date('fecha_cotizacion')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento_general', 15, 2)->default(0);
                $table->decimal('descuento_items', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('retencion_iva', 15, 2)->default(0);
                $table->decimal('retencion_isr', 15, 2)->default(0);
                $table->decimal('isr', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->text('notas')->nullable();
                $table->string('estado')->default('pendiente');
                
                // Campos para rastreo de email
                $table->boolean('email_enviado')->default(false);
                $table->dateTime('email_enviado_fecha')->nullable();
                $table->unsignedBigInteger('email_enviado_por')->nullable();
                
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('cotizacion_items')) {
            Schema::create('cotizacion_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cotizacion_id')->index();
                $table->morphs('cotizable'); // para Producto o Servicio
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
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
        Schema::dropIfExists('cotizacion_items');
        Schema::dropIfExists('cotizaciones');
    }
};
