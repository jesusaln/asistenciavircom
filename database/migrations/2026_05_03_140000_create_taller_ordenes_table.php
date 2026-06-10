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
        if (!Schema::hasTable('taller_ordenes')) {
            Schema::create('taller_ordenes', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->foreignId('empresa_id')->constrained('empresa_configuracion');
                $blueprint->string('folio')->unique();
                
                // Cliente
                $blueprint->foreignId('cliente_id')->nullable()->constrained('clientes');
                $blueprint->string('nombre_cliente')->nullable();
                $blueprint->string('telefono_cliente')->nullable();
                
                // Equipo
                $blueprint->string('equipo_marca');
                $blueprint->string('equipo_modelo');
                $blueprint->string('equipo_serie')->nullable();
                $blueprint->json('accesorios')->nullable();
                $blueprint->text('estado_fisico')->nullable();
                $blueprint->text('problema_reportado');
                
                // Proceso Técnico
                $blueprint->text('diagnostico')->nullable();
                $blueprint->text('trabajo_realizado')->nullable();
                $blueprint->decimal('costo_estimado', 12, 2)->default(0);
                $blueprint->decimal('costo_final', 12, 2)->default(0);
                
                // Estados
                $blueprint->string('estado')->default('recepcionado');
                // recepcionado, en_revision, reparando, listo, entregado, sin_reparacion, cancelado
                
                // Firmas y Fechas
                $blueprint->string('firma_recepcion')->nullable();
                $blueprint->string('firma_entrega')->nullable();
                $blueprint->timestamp('fecha_recepcion')->useCurrent();
                $blueprint->timestamp('fecha_entrega')->nullable();
                
                // Personal
                $blueprint->foreignId('user_id')->constrained('users'); // Quien recibe
                $blueprint->foreignId('tecnico_id')->nullable()->constrained('users'); // Tecnico asignado
                
                $blueprint->softDeletes();
                $blueprint->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taller_ordenes');
    }
};
