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
        Schema::create('plan_polizas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');

            // Información básica
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_corta')->nullable();

            // Categorización
            $table->string('tipo')->default('mantenimiento'); // mantenimiento, soporte, garantia, premium, personalizado
            $table->string('icono')->nullable(); // emoji o nombre de icono
            $table->string('color')->nullable(); // color personalizado del plan

            // Precios
            $table->decimal('precio_mensual', 12, 2);
            $table->decimal('precio_anual', 12, 2)->nullable(); // precio con descuento anual
            $table->decimal('precio_instalacion', 12, 2)->default(0); // costo único de activación

            // Configuración de servicio
            $table->integer('horas_incluidas')->nullable(); // horas mensuales incluidas
            $table->integer('tickets_incluidos')->nullable(); // tickets mensuales incluidos
            $table->integer('sla_horas_respuesta')->nullable(); // tiempo garantizado de respuesta
            $table->decimal('costo_hora_extra', 10, 2)->nullable(); // costo por hora adicional

            // Beneficios (JSON array)
            $table->json('beneficios')->nullable(); // ["Soporte 24/7", "Piezas incluidas", etc.]
            $table->json('incluye_servicios')->nullable(); // IDs de servicios incluidos

            // Configuración de venta
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false); // mostrar como recomendado
            $table->boolean('visible_catalogo')->default(true); // mostrar en catálogo público
            $table->integer('orden')->default(0); // orden de visualización

            // Restricciones
            $table->integer('min_equipos')->nullable(); // mínimo de equipos requeridos
            $table->integer('max_equipos')->nullable(); // máximo de equipos permitidos

            // Imágenes
            $table->string('imagen')->nullable(); // imagen del plan

            $table->softDeletes();
            $table->timestamps();

            $table->index(['empresa_id', 'activo', 'visible_catalogo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_polizas');
    }
};
