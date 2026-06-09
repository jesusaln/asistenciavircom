<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade índices de rendimiento críticos para el módulo de citas.
     */
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Índices simples para búsquedas frecuentes
            $table->index('tecnico_id', 'idx_citas_tecnico');
            $table->index('cliente_id', 'idx_citas_cliente');
            $table->index('estado', 'idx_citas_estado');
            $table->index('empresa_id', 'idx_citas_empresa');
            $table->index('fecha_hora', 'idx_citas_fecha_hora');
            $table->index('fecha_confirmada', 'idx_citas_fecha_confirmada');
            
            // Índice para búsquedas por folio (búsqueda exacta y LIKE 'CIT-%')
            $table->index('folio', 'idx_citas_folio');

            // Índice compuesto para optimizar la consulta de "Mi Agenda" (Técnico + Fecha)
            // Esta es la consulta más pesada y frecuente de la App móvil.
            $table->index(['tecnico_id', 'fecha_hora', 'estado'], 'idx_citas_agenda_tecnico');
            
            // Índice compuesto para validación de conflictos de horario
            $table->index(['tecnico_id', 'fecha_hora', 'fecha_hora_fin'], 'idx_citas_conflictos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropIndex('idx_citas_tecnico');
            $table->dropIndex('idx_citas_cliente');
            $table->dropIndex('idx_citas_estado');
            $table->dropIndex('idx_citas_empresa');
            $table->dropIndex('idx_citas_fecha_hora');
            $table->dropIndex('idx_citas_fecha_confirmada');
            $table->dropIndex('idx_citas_folio');
            $table->dropIndex('idx_citas_agenda_tecnico');
            $table->dropIndex('idx_citas_conflictos');
        });
    }
};
