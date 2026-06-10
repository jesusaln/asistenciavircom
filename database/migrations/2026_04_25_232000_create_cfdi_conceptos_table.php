<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cfdi_conceptos')) {
            return;
        }

        Schema::create('cfdi_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfdi_id')->constrained('cfdis')->cascadeOnDelete();
            $table->string('clave_prod_serv')->nullable();
            $table->string('no_identificacion')->nullable();
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->string('clave_unidad')->nullable();
            $table->string('unidad')->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('valor_unitario', 12, 2)->default(0);
            $table->decimal('importe', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->json('impuestos')->nullable();
            $table->string('numero_pedimento')->nullable();
            $table->string('cuenta_predial')->nullable();
            $table->json('complemento')->nullable();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('servicio_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_conceptos');
    }
};
