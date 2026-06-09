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
        if (!Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->string('codigo')->nullable();
                $table->uuid('uuid')->nullable();
                $table->string('nombre_razon_social');
                $table->string('rfc')->nullable();
                $table->string('email')->nullable();
                $table->unsignedBigInteger('price_list_id')->nullable();
                $table->string('telefono')->nullable();
                $table->string('direccion')->nullable();
                $table->string('tipo_persona')->default('fisica');
                $table->string('regimen_fiscal')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->string('domicilio_fiscal_cp')->nullable();
                $table->string('calle')->nullable();
                $table->string('numero_exterior')->nullable();
                $table->string('numero_interior')->nullable();
                $table->string('colonia')->nullable();
                $table->string('codigo_postal')->nullable();
                $table->string('municipio')->nullable();
                $table->string('estado')->nullable();
                $table->string('pais')->nullable();
                $table->boolean('credito_activo')->default(false);
                $table->string('estado_credito')->default('sin_credito');
                $table->decimal('limite_credito', 15, 2)->default(0);
                $table->integer('dias_credito')->default(0);
                $table->integer('dias_gracia')->nullable()->default(0);
                $table->string('password')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
