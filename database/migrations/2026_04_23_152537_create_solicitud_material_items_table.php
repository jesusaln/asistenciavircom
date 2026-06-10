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
        if (!Schema::hasTable('solicitud_material_items')) {
            Schema::create('solicitud_material_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('solicitud_material_id')->constrained('solicitud_materiales')->onDelete('cascade');
                $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('set null');
                $table->string('descripcion')->nullable();
                $table->decimal('cantidad', 10, 2);
                $table->string('unidad_medida')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_material_items');
    }
};
