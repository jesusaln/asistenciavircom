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
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'estado_credito')) {
                $table->string('estado_credito')->default('sin_credito')->after('credito_activo');
            }
        });

        Schema::create('cliente_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->string('tipo'); // ine_frontal, ine_trasera, comprobante_domicilio, contrato_firmado, etc.
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->string('extension')->nullable();
            $table->bigInteger('tamano')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_documentos');
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('estado_credito');
        });
    }
};
