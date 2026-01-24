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
        Schema::table('plan_polizas', function (Blueprint $table) {
            // Financiero / IFRS15
            $table->string('moneda')->default('MXN')->after('precio_anual');
            $table->decimal('precio_trimestral', 12, 2)->nullable()->after('precio_anual');
            $table->decimal('precio_semestral', 12, 2)->nullable()->after('precio_trimestral');
            $table->decimal('iva_tasa', 5, 2)->default(16.00)->after('moneda');
            $table->boolean('iva_incluido')->default(false)->after('iva_tasa');

            // Cobranza
            $table->integer('limit_dia_pago')->default(5)->after('iva_incluido');
            $table->integer('dias_gracia_cobranza')->default(3)->after('limit_dia_pago');
            $table->decimal('recargo_pago_tardio', 12, 2)->default(0)->after('dias_gracia_cobranza');
            $table->enum('tipo_recargo', ['fijo', 'porcentaje'])->default('fijo')->after('recargo_pago_tardio');

            // Límites de Servicio
            $table->integer('limite_usuarios_soporte')->nullable()->after('max_equipos');
            $table->integer('limite_ubicaciones')->default(1)->after('limite_usuarios_soporte');
            $table->boolean('soporte_remoto_ilimitado')->default(true)->after('visitas_sitio_mensuales');
            $table->boolean('soporte_presencial_incluido')->default(false)->after('soporte_remoto_ilimitado');

            // Facturación
            $table->boolean('requiere_orden_compra')->default(false);
            $table->string('metodo_pago_sugerido')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_polizas', function (Blueprint $table) {
            $table->dropColumn([
                'moneda',
                'precio_trimestral',
                'precio_semestral',
                'iva_tasa',
                'iva_incluido',
                'limit_dia_pago',
                'dias_gracia_cobranza',
                'recargo_pago_tardio',
                'tipo_recargo',
                'limite_usuarios_soporte',
                'limite_ubicaciones',
                'soporte_remoto_ilimitado',
                'soporte_presencial_incluido',
                'requiere_orden_compra',
                'metodo_pago_sugerido'
            ]);
        });
    }
};
