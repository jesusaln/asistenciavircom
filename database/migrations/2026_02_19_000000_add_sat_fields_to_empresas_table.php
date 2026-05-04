<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = Schema::getConnection()->getTablePrefix() . 'empresas';
        $columns = Schema::getColumnListing($table);

        // Nombre y identificación fiscal
        if (!in_array('nombre_razon_social', $columns)) {
            Schema::table('empresas', function (Blueprint $table) use ($columns) {
                $after = in_array('nombre_comercial', $columns) ? 'nombre_comercial' : (in_array('nombre_fiscal', $columns) ? 'nombre_fiscal' : null);
                if ($after) {
                    $table->string('nombre_razon_social')->nullable()->after($after);
                } else {
                    $table->string('nombre_razon_social')->nullable();
                }
            });
        }

        if (!in_array('tipo_persona', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('tipo_persona')->nullable()->after('nombre_razon_social');
            });
        }

        if (!in_array('tipo_identificacion', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('tipo_identificacion')->nullable()->after('tipo_persona');
            });
        }

        if (!in_array('identificacion', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('identificacion')->nullable()->after('tipo_identificacion');
            });
        }

        if (!in_array('curp', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('curp')->nullable()->after('identificacion');
            });
        }

        if (!in_array('rfc', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('rfc')->nullable()->after('curp');
            });
        }

        if (!in_array('regimen_fiscal', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('regimen_fiscal')->nullable()->after('rfc');
            });
        }

        if (!in_array('uso_cfdi', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('uso_cfdi')->nullable()->after('regimen_fiscal');
            });
        }

        // Domicilio fiscal
        if (!in_array('calle', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('calle')->nullable()->after('uso_cfdi');
            });
        }

        if (!in_array('numero_exterior', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('numero_exterior')->nullable()->after('calle');
            });
        }

        if (!in_array('numero_interior', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('numero_interior')->nullable()->after('numero_exterior');
            });
        }

        if (!in_array('colonia', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('colonia')->nullable()->after('numero_interior');
            });
        }

        if (!in_array('codigo_postal', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('codigo_postal')->nullable()->after('colonia');
            });
        }

        if (!in_array('municipio', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('municipio')->nullable()->after('codigo_postal');
            });
        }

        if (!in_array('estado', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('estado')->nullable()->after('municipio');
            });
        }

        if (!in_array('pais', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('pais')->nullable()->after('estado');
            });
        }

        // WhatsApp Business fields
        if (!in_array('whatsapp_enabled', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->boolean('whatsapp_enabled')->default(false)->after('pais');
            });
        }

        if (!in_array('whatsapp_business_account_id', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('whatsapp_business_account_id')->nullable()->after('whatsapp_enabled');
            });
        }

        if (!in_array('whatsapp_phone_number_id', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('whatsapp_phone_number_id')->nullable()->after('whatsapp_business_account_id');
            });
        }

        if (!in_array('whatsapp_sender_phone', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('whatsapp_sender_phone')->nullable()->after('whatsapp_phone_number_id');
            });
        }

        if (!in_array('whatsapp_access_token', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->text('whatsapp_access_token')->nullable()->after('whatsapp_sender_phone');
            });
        }

        if (!in_array('whatsapp_app_secret', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->text('whatsapp_app_secret')->nullable()->after('whatsapp_access_token');
            });
        }

        if (!in_array('whatsapp_webhook_verify_token', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('whatsapp_webhook_verify_token')->nullable()->after('whatsapp_app_secret');
            });
        }

        if (!in_array('whatsapp_default_language', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('whatsapp_default_language')->nullable()->after('whatsapp_webhook_verify_token');
            });
        }

        if (!in_array('whatsapp_template_payment_reminder', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->text('whatsapp_template_payment_reminder')->nullable()->after('whatsapp_default_language');
            });
        }

        if (!in_array('whatsapp_template_maintenance', $columns)) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->text('whatsapp_template_maintenance')->nullable()->after('whatsapp_template_payment_reminder');
            });
        }
    }

    public function down(): void
    {
        // No need to implement down() as this is a schema enhancement
    }
};
