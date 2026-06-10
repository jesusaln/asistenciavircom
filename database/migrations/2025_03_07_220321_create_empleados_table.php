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
        if (!Schema::hasTable('empleados')) {
            Schema::create('empleados', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('numero_empleado')->nullable();
                $table->date('fecha_nacimiento')->nullable();
                $table->string('curp', 18)->nullable();
                $table->string('rfc', 13)->nullable();
                $table->string('nss', 11)->nullable();
                $table->text('direccion')->nullable();
                $table->string('puesto')->nullable();
                $table->string('departamento')->nullable();
                $table->date('fecha_contratacion')->nullable();
                $table->decimal('salario_base', 12, 2)->nullable();
                $table->string('tipo_contrato')->default('tiempo_completo');
                $table->string('tipo_jornada')->default('diurna');
                $table->integer('horas_jornada')->default(8);
                $table->string('banco')->nullable();
                $table->string('numero_cuenta')->nullable();
                $table->string('clabe_interbancaria', 18)->nullable();
                $table->string('contacto_emergencia_nombre')->nullable();
                $table->string('contacto_emergencia_telefono')->nullable();
                $table->string('contacto_emergencia_parentesco')->nullable();
                $table->text('observaciones')->nullable();
                $table->boolean('activo')->default(true);
                $table->string('contrato_adjunto')->nullable();
                $table->string('frecuencia_pago')->default('quincenal');
                $table->time('hora_entrada')->nullable();
                $table->time('hora_salida')->nullable();
                $table->boolean('trabaja_sabado')->default(false);
                $table->time('hora_entrada_sabado')->nullable();
                $table->time('hora_salida_sabado')->nullable();
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
        Schema::dropIfExists('empleados');
    }
};
