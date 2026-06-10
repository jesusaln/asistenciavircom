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
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'producto_id')) {
                $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'venta_id')) {
                $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'folio_manual')) {
                $table->string('folio_manual')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'tipo_servicio')) {
                $table->string('tipo_servicio')->nullable()->default('garantia');
            }
            if (!Schema::hasColumn('tickets', 'telefono_contacto')) {
                $table->string('telefono_contacto')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'email_contacto')) {
                $table->string('email_contacto')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'nombre_contacto')) {
                $table->string('nombre_contacto')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'notas_internas')) {
                $table->text('notas_internas')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'archivos')) {
                $table->json('archivos')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['venta_id']);
            $table->dropColumn([
                'producto_id',
                'venta_id',
                'folio_manual',
                'tipo_servicio',
                'telefono_contacto',
                'email_contacto',
                'nombre_contacto',
                'notas_internas',
                'archivos',
            ]);
        });
    }
};
