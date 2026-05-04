<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Credit columns
        if (!Schema::hasColumn('clientes', 'limite_credito')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->decimal('limite_credito', 12, 2)->default(0)->after('estado_credito');
            });
        }
        if (!Schema::hasColumn('clientes', 'dias_credito')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->integer('dias_credito')->default(0)->after('limite_credito');
            });
        }

        // Identification columns
        if (!Schema::hasColumn('clientes', 'tipo_identificacion')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('tipo_identificacion', 50)->nullable()->after('curp');
            });
        }
        if (!Schema::hasColumn('clientes', 'identificacion')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('identificacion', 50)->nullable()->after('tipo_identificacion');
            });
        }

        // CFDI columns
        if (!Schema::hasColumn('clientes', 'domicilio_fiscal_cp')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('domicilio_fiscal_cp', 10)->nullable()->after('numero_interior');
            });
        }
        if (!Schema::hasColumn('clientes', 'residencia_fiscal')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('residencia_fiscal', 10)->nullable()->after('misma_direccion_fiscal');
            });
        }
        if (!Schema::hasColumn('clientes', 'num_reg_id_trib')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('num_reg_id_trib', 40)->nullable()->after('residencia_fiscal');
            });
        }

        // General columns
        if (!Schema::hasColumn('clientes', 'requiere_factura')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->boolean('requiere_factura')->default(false)->after('activo');
            });
        }
        if (!Schema::hasColumn('clientes', 'notas')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->text('notas')->nullable()->after('requiere_factura');
            });
        }
        if (!Schema::hasColumn('clientes', 'price_list_id')) {
            Schema::table('clientes', function (Blueprint $table) {
                // Skip foreign key - price_lists table may not exist
                $table->unsignedBigInteger('price_list_id')->nullable()->after('notas');
            });
        }

        // WhatsApp columns
        if (!Schema::hasColumn('clientes', 'whatsapp_optin')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->boolean('whatsapp_optin')->default(false)->after('price_list_id');
            });
        }
        if (!Schema::hasColumn('clientes', 'whatsapp_consent_date')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->timestamp('whatsapp_consent_date')->nullable()->after('whatsapp_optin');
            });
        }
        if (!Schema::hasColumn('clientes', 'whatsapp_consent_method')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('whatsapp_consent_method', 50)->nullable()->after('whatsapp_consent_date');
            });
        }
        if (!Schema::hasColumn('clientes', 'whatsapp_consent_source')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('whatsapp_consent_source', 100)->nullable()->after('whatsapp_consent_method');
            });
        }

        // Billing defaults
        if (!Schema::hasColumn('clientes', 'cfdi_default_use')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('cfdi_default_use', 10)->nullable()->after('whatsapp_consent_source');
            });
        }
        if (!Schema::hasColumn('clientes', 'payment_form_default')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('payment_form_default', 10)->nullable()->after('cfdi_default_use');
            });
        }

        // Also ensure credito_activo exists (may have been added manually)
        if (!Schema::hasColumn('clientes', 'credito_activo')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->boolean('credito_activo')->default(false)->after('password');
            });
        }
    }

    public function down(): void
    {
        // These are critical columns - don't drop in down()
    }
};
