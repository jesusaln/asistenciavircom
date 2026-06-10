<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Catálogo de Cuentas
        if (!Schema::hasTable('contab_cuentas')) {
            Schema::create('contab_cuentas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->string('codigo')->index(); // Ej: 100-01-001
                $table->string('nombre');
                $table->enum('tipo', ['activo', 'pasivo', 'capital', 'ingreso', 'egreso', 'orden']);
                $table->enum('naturaleza', ['deudora', 'acreedora']);
                $table->integer('nivel')->default(1);
                $table->foreignId('padre_id')->nullable()->constrained('contab_cuentas')->onDelete('cascade');
                $table->boolean('es_detalle')->default(true); // Si se pueden registrar asientos en ella
                $table->string('sat_codigo')->nullable(); // Código agrupador del SAT
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['empresa_id', 'codigo']);
            });
        }

        // 2. Pólizas Contables
        if (!Schema::hasTable('contab_polizas')) {
            Schema::create('contab_polizas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->enum('tipo', ['ingreso', 'egreso', 'diario']);
                $table->date('fecha');
                $table->string('numero')->index(); // Folio interno
                $table->string('concepto', 500);
                $table->uuid('cfdi_uuid')->nullable()->index();
                $table->decimal('total', 15, 2)->default(0);
                $table->enum('estado', ['borrador', 'asentada', 'anulada'])->default('borrador');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['empresa_id', 'tipo', 'numero', 'fecha']);
            });
        }

        // 3. Asientos (Partidas de la Póliza)
        if (!Schema::hasTable('contab_asientos')) {
            Schema::create('contab_asientos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('poliza_id')->constrained('contab_polizas')->onDelete('cascade');
                $table->foreignId('cuenta_id')->constrained('contab_cuentas');
                $table->decimal('debe', 15, 2)->default(0);
                $table->decimal('haber', 15, 2)->default(0);
                $table->string('referencia')->nullable();
                $table->timestamps();
            });
        }

        // 4. Mappings de RFC a Cuentas (Para automatización)
        if (!Schema::hasTable('contab_rfc_mappings')) {
            Schema::create('contab_rfc_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->string('rfc', 15)->index();
                $table->foreignId('cuenta_id')->constrained('contab_cuentas');
                $table->string('nombre_auxiliar')->nullable();
                $table->timestamps();

                $table->unique(['empresa_id', 'rfc']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contab_rfc_mappings');
        Schema::dropIfExists('contab_asientos');
        Schema::dropIfExists('contab_polizas');
        Schema::dropIfExists('contab_cuentas');
    }
};
