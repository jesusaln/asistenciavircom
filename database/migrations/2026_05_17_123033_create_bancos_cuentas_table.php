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
        Schema::create('bancos_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre_banco', 100);
            $table->string('alias', 100)->nullable();
            $table->string('numero_cuenta', 50)->nullable();
            $table->string('clabe', 50)->nullable();
            $table->string('moneda', 10)->default('MXN');
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->foreignId('cuenta_contable_id')->nullable()->constrained('contab_cuentas')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bancos_cuentas');
    }
};
