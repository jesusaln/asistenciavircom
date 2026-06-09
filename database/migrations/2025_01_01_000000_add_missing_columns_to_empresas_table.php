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
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'nombre_razon_social')) {
                $table->string('nombre_razon_social')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'tipo_persona')) {
                $table->string('tipo_persona')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'tipo_identificacion')) {
                $table->string('tipo_identificacion')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'identificacion')) {
                $table->string('identificacion')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'curp')) {
                $table->string('curp')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'rfc')) {
                $table->string('rfc')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'regimen_fiscal')) {
                $table->string('regimen_fiscal')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'uso_cfdi')) {
                $table->string('uso_cfdi')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'calle')) {
                $table->string('calle')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'numero_exterior')) {
                $table->string('numero_exterior')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'numero_interior')) {
                $table->string('numero_interior')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'colonia')) {
                $table->string('colonia')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'codigo_postal')) {
                $table->string('codigo_postal')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'municipio')) {
                $table->string('municipio')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'estado')) {
                $table->string('estado')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'pais')) {
                $table->string('pais')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_enabled')) {
                $table->boolean('whatsapp_enabled')->default(false);
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_business_account_id')) {
                $table->string('whatsapp_business_account_id')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_phone_number_id')) {
                $table->string('whatsapp_phone_number_id')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_sender_phone')) {
                $table->string('whatsapp_sender_phone')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_access_token')) {
                $table->text('whatsapp_access_token')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_app_secret')) {
                $table->text('whatsapp_app_secret')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_webhook_verify_token')) {
                $table->string('whatsapp_webhook_verify_token')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_default_language')) {
                $table->string('whatsapp_default_language')->default('es_MX');
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_template_payment_reminder')) {
                $table->string('whatsapp_template_payment_reminder')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_razon_social',
                'tipo_persona',
                'tipo_identificacion',
                'identificacion',
                'curp',
                'rfc',
                'regimen_fiscal',
                'uso_cfdi',
                'calle',
                'numero_exterior',
                'numero_interior',
                'colonia',
                'codigo_postal',
                'municipio',
                'estado',
                'pais',
                'whatsapp_enabled',
                'whatsapp_business_account_id',
                'whatsapp_phone_number_id',
                'whatsapp_sender_phone',
                'whatsapp_access_token',
                'whatsapp_app_secret',
                'whatsapp_webhook_verify_token',
                'whatsapp_default_language',
                'whatsapp_template_payment_reminder',
            ]);
        });
    }
};
