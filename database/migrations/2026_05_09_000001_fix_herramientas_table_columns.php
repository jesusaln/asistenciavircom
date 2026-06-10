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
        Schema::table('herramientas', function (Blueprint $table) {
            if (!Schema::hasColumn('herramientas', 'codigo_inventario')) {
                $table->string('codigo_inventario')->nullable()->unique()->after('nombre');
            }
            if (!Schema::hasColumn('herramientas', 'usuario_entrega_id')) {
                $table->unsignedBigInteger('usuario_entrega_id')->nullable()->after('fecha_recepcion');
            }
            if (!Schema::hasColumn('herramientas', 'usuario_recepcion_id')) {
                $table->unsignedBigInteger('usuario_recepcion_id')->nullable()->after('usuario_entrega_id');
            }
            if (!Schema::hasColumn('herramientas', 'activo')) {
                $table->boolean('activo')->default(true)->after('usuario_recepcion_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('herramientas', function (Blueprint $table) {
            $table->dropColumn(['codigo_inventario', 'usuario_entrega_id', 'usuario_recepcion_id', 'activo']);
        });
    }
};
