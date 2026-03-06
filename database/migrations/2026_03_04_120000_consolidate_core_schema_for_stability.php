<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de consolidación para estabilizar el esquema del sistema.
 * 
 * Esta migración asegura que todas las tablas y columnas críticas existan
 * sin importar en qué estado se encuentre la base de datos actual.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tablas Core y sus columnas
        $this->ensureUsersColumns();
        $this->ensureEmpresasColumns();
        $this->ensureClientesColumns();
        
        // 2. Módulos Operacionales
        $this->ensureCitasColumns();
        $this->ensurePrestamosTables();
        $this->ensureCotizacionesTables();
        $this->ensureNotificationsTables();
        
        // 3. Otros ajustes operacionales (Inventarios, Compras, etc)
        $this->ensureOperationalFixes();
    }

    public function down(): void
    {
        // Migración de consolidación no destructiva.
    }

    private function ensureUsersColumns(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) $table->string('username')->nullable()->index();
            if (!Schema::hasColumn('users', 'checkin_token')) $table->string('checkin_token', 64)->nullable()->unique();
            if (!Schema::hasColumn('users', 'ine')) $table->string('ine', 30)->nullable();
            if (!Schema::hasColumn('users', 'imss')) $table->string('imss', 50)->nullable();
            if (!Schema::hasColumn('users', 'dias_trabajo')) $table->json('dias_trabajo')->nullable();
            if (!Schema::hasColumn('users', 'dias_descanso')) $table->json('dias_descanso')->nullable();
            if (!Schema::hasColumn('users', 'face_reference_path')) $table->string('face_reference_path')->nullable();
            if (!Schema::hasColumn('users', 'face_enrolled_at')) $table->timestamp('face_enrolled_at')->nullable();
            if (!Schema::hasColumn('users', 'face_last_verified_at')) $table->timestamp('face_last_verified_at')->nullable();
            if (!Schema::hasColumn('users', 'face_provider')) $table->string('face_provider', 50)->nullable();
            if (!Schema::hasColumn('users', 'face_descriptor')) $table->json('face_descriptor')->nullable();
        });
    }

    private function ensureEmpresasColumns(): void
    {
        if (!Schema::hasTable('empresas')) return;

        Schema::table('empresas', function (Blueprint $table) {
            $cols = [
                'nombre_razon_social' => 'string',
                'tipo_persona' => 'string',
                'rfc' => 'string',
                'regimen_fiscal' => 'string',
                'uso_cfdi' => 'string',
                'email' => 'string',
                'telefono' => 'string',
                'calle' => 'string',
                'numero_exterior' => 'string',
                'codigo_postal' => 'string',
                'colonia' => 'string',
                'municipio' => 'string',
                'estado' => 'string',
                'pais' => 'string',
                'whatsapp_enabled' => 'boolean',
                'whatsapp_default_language' => 'string',
                'whatsapp_template_maintenance' => 'string',
            ];

            foreach ($cols as $col => $type) {
                if (!Schema::hasColumn('empresas', $col)) {
                    if ($type == 'boolean') $table->boolean($col)->default(false);
                    else $table->string($col)->nullable();
                }
            }
        });
    }

    private function ensureClientesColumns(): void
    {
        if (!Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre_razon_social');
                $table->string('rfc')->nullable();
                $table->string('email')->nullable();
                $table->string('tipo_persona')->default('fisica');
                $table->string('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('clientes', function (Blueprint $table) {
            $cols = [
                'tipo_identificacion', 'identificacion', 'curp', 'forma_pago_default',
                'residencia_fiscal', 'num_reg_id_trib', 'requiere_factura', 'notas',
                'whatsapp_optin', 'whatsapp_consent_date', 'whatsapp_consent_method',
                'whatsapp_consent_source', 'cfdi_default_use', 'payment_form_default'
            ];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('clientes', $col)) {
                    if ($col == 'requiere_factura' || $col == 'whatsapp_optin') $table->boolean($col)->default(false);
                    elseif ($col == 'whatsapp_consent_date') $table->dateTime($col)->nullable();
                    else $table->text($col)->nullable();
                }
            }
        });
    }

    private function ensureCitasColumns(): void
    {
        if (!Schema::hasTable('citas')) return;

        Schema::table('citas', function (Blueprint $table) {
            if (!Schema::hasColumn('citas', 'tipo_servicio')) $table->string('tipo_servicio')->nullable();
            if (!Schema::hasColumn('citas', 'fecha_hora')) $table->dateTime('fecha_hora')->nullable();
            if (!Schema::hasColumn('citas', 'tipo_equipo')) $table->string('tipo_equipo')->nullable();
            if (!Schema::hasColumn('citas', 'marca_equipo')) $table->string('marca_equipo')->nullable();
            if (!Schema::hasColumn('citas', 'modelo_equipo')) $table->string('modelo_equipo')->nullable();
            if (!Schema::hasColumn('citas', 'problema_reportado')) $table->text('problema_reportado')->nullable();
            if (!Schema::hasColumn('citas', 'prioridad')) $table->string('prioridad')->default('media');
            if (!Schema::hasColumn('citas', 'evidencias')) $table->text('evidencias')->nullable();
            if (!Schema::hasColumn('citas', 'descripcion')) $table->text('descripcion')->nullable();
            if (Schema::hasColumn('citas', 'titulo')) $table->string('titulo')->nullable()->change();
            
            // Campos de seguimiento y fotos
            $extraCols = ["foto_equipo", "foto_hoja_servicio", "foto_identificacion", "subtotal", "descuento_general", "descuento_items", "iva", "total", "notas", "inicio_servicio", "fin_servicio", "tiempo_servicio"];
            foreach($extraCols as $col) {
                if (!Schema::hasColumn("citas", $col)) {
                    if (in_array($col, ["subtotal", "descuento_general", "descuento_items", "iva", "total"])) $table->decimal($col, 15, 2)->default(0);
                    elseif (in_array($col, ["inicio_servicio", "fin_servicio"])) $table->dateTime($col)->nullable();
                    elseif ($col == "tiempo_servicio") $table->integer($col)->nullable();
                    else $table->text($col)->nullable();
                }
            }
        });
    }

    private function ensurePrestamosTables(): void
    {
        if (!Schema::hasTable('prestamos')) {
            Schema::create('prestamos', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->unsignedBigInteger('empleado_id')->nullable()->index();
                $table->decimal('monto_prestado', 15, 2)->default(0);
                $table->decimal('tasa_interes_mensual', 5, 2)->default(0);
                $table->integer('numero_pagos')->default(0);
                $table->string('frecuencia_pago')->default('mensual');
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_primer_pago')->nullable();
                $table->decimal('monto_interes_total', 15, 2)->default(0);
                $table->decimal('monto_total_pagar', 15, 2)->default(0);
                $table->decimal('pago_periodico', 15, 2)->default(0);
                $table->string('estado')->default('activo');
                $table->integer('pagos_realizados')->default(0);
                $table->integer('pagos_pendientes')->default(0);
                $table->decimal('monto_pagado', 15, 2)->default(0);
                $table->decimal('monto_pendiente', 15, 2)->default(0);
                $table->text('descripcion')->nullable();
                $table->text('notas')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('prestamos', function (Blueprint $table) {
                if (!Schema::hasColumn('prestamos', 'empresa_id')) $table->unsignedBigInteger('empresa_id')->nullable()->index();
                if (!Schema::hasColumn('prestamos', 'empleado_id')) $table->unsignedBigInteger('empleado_id')->nullable()->index();
                if (!Schema::hasColumn('prestamos', 'monto_prestado')) $table->decimal('monto_prestado', 15, 2)->default(0);
                if (!Schema::hasColumn('prestamos', 'tasa_interes_mensual')) $table->decimal('tasa_interes_mensual', 5, 2)->default(0);
                if (!Schema::hasColumn('prestamos', 'numero_pagos')) $table->integer('numero_pagos')->default(0);
                if (!Schema::hasColumn('prestamos', 'frecuencia_pago')) $table->string('frecuencia_pago')->default('mensual');
                if (!Schema::hasColumn('prestamos', 'fecha_inicio')) $table->date('fecha_inicio')->nullable();
                if (!Schema::hasColumn('prestamos', 'fecha_primer_pago')) $table->date('fecha_primer_pago')->nullable();
                if (!Schema::hasColumn('prestamos', 'monto_interes_total')) $table->decimal('monto_interes_total', 15, 2)->default(0);
                if (!Schema::hasColumn('prestamos', 'monto_total_pagar')) $table->decimal('monto_total_pagar', 15, 2)->default(0);
                if (!Schema::hasColumn('prestamos', 'pago_periodico')) $table->decimal('pago_periodico', 15, 2)->default(0);
                if (!Schema::hasColumn('prestamos', 'pagos_realizados')) $table->integer('pagos_realizados')->default(0);
                if (!Schema::hasColumn('prestamos', 'pagos_pendientes')) $table->integer('pagos_pendientes')->default(0);
                if (!Schema::hasColumn('prestamos', 'monto_pagado')) $table->decimal('monto_pagado', 15, 2)->default(0);
                if (!Schema::hasColumn('prestamos', 'monto_pendiente')) $table->decimal('monto_pendiente', 15, 2)->default(0);
                if (!Schema::hasColumn('prestamos', 'descripcion')) $table->text('descripcion')->nullable();
                if (!Schema::hasColumn('prestamos', 'notas')) $table->text('notas')->nullable();
                if (!Schema::hasColumn('prestamos', 'activo')) $table->boolean('activo')->default(true);
                if (!Schema::hasColumn('prestamos', 'deleted_at')) $table->softDeletes();
            });
        }

        if (!Schema::hasTable('pago_prestamos')) {
            Schema::create('pago_prestamos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prestamo_id')->index();
                $table->integer('numero_pago');
                $table->decimal('monto_programado', 15, 2);
                $table->decimal('monto_pagado', 15, 2)->default(0);
                $table->date('fecha_programada');
                $table->date('fecha_pago')->nullable();
                $table->string('estado')->default('pendiente');
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function ensureCotizacionesTables(): void
    {
        if (!Schema::hasTable('cotizaciones')) {
            Schema::create('cotizaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->unsignedBigInteger('almacen_id')->nullable()->index();
                $table->string('numero_cotizacion')->nullable()->index();
                $table->date('fecha_cotizacion')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento_general', 15, 2)->default(0);
                $table->decimal('descuento_items', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('retencion_iva', 15, 2)->default(0);
                $table->decimal('retencion_isr', 15, 2)->default(0);
                $table->decimal('isr', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->text('notas')->nullable();
                $table->string('estado')->default('pendiente');
                $table->boolean('email_enviado')->default(false);
                $table->dateTime('email_enviado_fecha')->nullable();
                $table->unsignedBigInteger('email_enviado_por')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('cotizacion_items')) {
            Schema::create('cotizacion_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cotizacion_id')->index();
                $table->morphs('cotizable');
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->text('notas')->nullable();
                $table->timestamps();
            });
        }
    }

    private function ensureNotificationsTables(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('type')->default('system');
                $table->string('title');
                $table->text('message')->nullable();
                $table->json('data')->nullable();
                $table->string('action_url')->nullable();
                $table->string('icon')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function ensureOperationalFixes(): void
    {
        // 1. Cuentas por Cobrar
        if (!Schema::hasTable('cuentas_por_cobrar')) {
            Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->unsignedBigInteger('cobrable_id')->nullable();
                $table->string('cobrable_type')->nullable();
                $table->decimal('monto_total', 15, 2)->default(0);
                $table->decimal('monto_pagado', 15, 2)->default(0);
                $table->decimal('monto_pendiente', 15, 2)->default(0);
                $table->date('fecha_vencimiento')->nullable();
                $table->string('estado')->default('pendiente');
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Pedidos
        if (!Schema::hasTable('pedidos')) {
            Schema::create('pedidos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->string('numero_pedido')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->dateTime('fecha')->nullable();
                $table->string('estado')->default('pendiente');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('pedido_items')) {
            Schema::create('pedido_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pedido_id')->nullable()->index();
                $table->unsignedBigInteger('pedible_id')->nullable();
                $table->string('pedible_type')->nullable();
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // 3. Inventarios y Traspasos
        if (!Schema::hasTable('inventarios')) {
            Schema::create('inventarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('producto_id')->nullable()->index();
                $table->unsignedBigInteger('almacen_id')->nullable()->index();
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('stock_minimo', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('traspasos')) {
            Schema::create('traspasos', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->nullable()->index();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('producto_id')->nullable()->index();
                $table->unsignedBigInteger('almacen_origen_id')->nullable()->index();
                $table->unsignedBigInteger('almacen_destino_id')->nullable()->index();
                $table->integer('cantidad')->default(0);
                $table->integer('cantidad_total')->default(0);
                $table->string('estado')->default('pendiente');
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('traspasos', 'cantidad_total')) {
            Schema::table('traspasos', function (Blueprint $table) {
                $table->integer('cantidad_total')->default(0)->after('cantidad');
            });
        }

        if (!Schema::hasTable('traspaso_items')) {
            Schema::create('traspaso_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('traspaso_id')->nullable()->index();
                $table->unsignedBigInteger('producto_id')->nullable()->index();
                $table->integer('cantidad')->default(0);
                $table->timestamps();
            });
        }

        // 4. Backup Logs
        if (!Schema::hasTable('backup_logs')) {
            Schema::create('backup_logs', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->nullable();
                $table->string('path')->nullable();
                $table->string('type')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        // 5. Relaciones de Compras y Series
        if (Schema::hasTable('compra_items') && !Schema::hasColumn('compra_items', 'compra_id')) {
            Schema::table('compra_items', function (Blueprint $table) {
                $table->unsignedBigInteger('compra_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('producto_series') && !Schema::hasColumn('producto_series', 'compra_id')) {
            Schema::table('producto_series', function (Blueprint $table) {
                $table->unsignedBigInteger('compra_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('cuentas_por_pagar') && !Schema::hasColumn('cuentas_por_pagar', 'compra_id')) {
            Schema::table('cuentas_por_pagar', function (Blueprint $table) {
                $table->unsignedBigInteger('compra_id')->nullable()->index();
            });
        }
        
        // 6. Almacenes estado
        if (Schema::hasTable('almacenes') && !Schema::hasColumn('almacenes', 'estado')) {
            Schema::table('almacenes', function (Blueprint $table) {
                $table->string('estado')->default('activo');
            });
        }
    }
};
