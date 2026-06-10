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
        // 1. Create facturas table if it doesn't exist
        if (!Schema::hasTable('facturas')) {
            Schema::create('facturas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->foreignId('cliente_id')->constrained('clientes');
                $table->string('numero_factura')->unique();
                $table->date('fecha_emision');
                $table->date('fecha_vencimiento')->nullable();

                // Financials
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('descuento_general', 12, 2)->default(0);
                $table->decimal('impuestos', 12, 2)->default(0);
                $table->decimal('iva', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);

                $table->string('estado')->default('borrador'); // borrador, enviada, pagada, vencida, cancelada

                // SAT Data
                $table->string('metodo_pago')->nullable();
                $table->string('forma_pago')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->string('moneda')->default('MXN');
                $table->decimal('tasa_cambio', 10, 4)->default(1);

                $table->text('observaciones')->nullable();
                $table->json('direccion_facturacion')->nullable();
                $table->json('datos_fiscales')->nullable();

                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 2. Add factura_id to ventas table
        if (Schema::hasTable('ventas') && !Schema::hasColumn('ventas', 'factura_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();
            });
        }

        // 3. Update cfdis table to be polymorphic or support invoices
        if (Schema::hasTable('cfdis')) {
            Schema::table('cfdis', function (Blueprint $table) {
                // If not already polymorphic
                if (!Schema::hasColumn('cfdis', 'cfdiable_id')) {
                    $table->nullableMorphs('cfdiable');
                }
                // If we want a direct FK as a fallback or for performance (nullable)
                if (!Schema::hasColumn('cfdis', 'factura_id')) {
                    $table->foreignId('factura_id')->nullable()->after('venta_id')->constrained('facturas')->nullOnDelete();
                }
            });
        } else {
            // Create cfdis table if missing (unlikely given Model exists, but safe to add)
            Schema::create('cfdis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->foreignId('cliente_id')->constrained('clientes');
                // Polymorphic relation
                $table->nullableMorphs('cfdiable');

                // Legacy or direct
                $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
                $table->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();

                $table->string('uuid')->nullable()->index();
                $table->string('serie')->nullable();
                $table->string('folio')->nullable();
                $table->string('estatus')->default('borrador');

                // Timbre Fiscal
                $table->dateTime('fecha_timbrado')->nullable();
                $table->text('xml_url')->nullable();
                $table->text('pdf_url')->nullable();

                // Additional fields for Model compatibility
                $table->string('tipo_comprobante')->default('I');
                $table->decimal('total', 12, 2)->default(0);
                $table->text('cadena_original')->nullable();
                $table->string('no_certificado_sat')->nullable();
                $table->string('no_certificado_cfdi')->nullable();
                $table->text('sello_sat')->nullable();
                $table->text('sello_cfdi')->nullable();
                $table->string('uso_cfdi')->nullable();

                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'factura_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropForeign(['factura_id']);
                $table->dropColumn('factura_id');
            });
        }

        if (Schema::hasColumn('cfdis', 'factura_id')) {
            Schema::table('cfdis', function (Blueprint $table) {
                $table->dropForeign(['factura_id']);
                $table->dropColumn('factura_id');
            });
        }

        // We generally don't drop tables in down() if they might contain data unless strict rollback
        Schema::dropIfExists('facturas');
    }
};
