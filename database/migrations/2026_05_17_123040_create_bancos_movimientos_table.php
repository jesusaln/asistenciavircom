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
        Schema::create('bancos_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('bancos_cuentas')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['ingreso', 'egreso', 'traspaso']);
            $table->decimal('monto', 15, 2);
            $table->string('concepto');
            $table->string('referencia')->nullable();
            $table->string('beneficiario_rfc')->nullable();
            $table->string('beneficiario_nombre')->nullable();
            $table->foreignId('poliza_id')->nullable()->constrained('contab_polizas')->nullOnDelete();
            $table->enum('estado_conciliacion', ['pendiente', 'conciliado'])->default('pendiente');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bancos_movimientos');
    }
};
