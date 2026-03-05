<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUsersColumns();
        $this->ensureEmpresasColumns();
        $this->ensureCitasColumns();
        $this->ensurePrestamosColumns();
        $this->ensureOperationalFixes();
    }

    public function down(): void
    {
        // Migracion de consolidacion no destructiva.
    }

    private function ensurePrestamosColumns(): void
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

    private function ensureCitasColumns(): void
    {
        if (!Schema::hasTable('citas')) {
            return;
        }

        Schema::table('citas', function (Blueprint $table) {
            if (!Schema::hasColumn('citas', 'tipo_servicio')) {
                $table->string('tipo_servicio')->nullable()->after('tecnico_id');
            }
            if (!Schema::hasColumn('citas', 'fecha_hora')) {
                $table->dateTime('fecha_hora')->nullable()->after('tipo_servicio');
            }
            if (!Schema::hasColumn('citas', 'tipo_equipo')) {
                $table->string('tipo_equipo')->nullable();
            }
            if (!Schema::hasColumn('citas', 'marca_equipo')) {
                $table->string('marca_equipo')->nullable();
            }
            if (!Schema::hasColumn('citas', 'modelo_equipo')) {
                $table->string('modelo_equipo')->nullable();
            }
            if (!Schema::hasColumn('citas', 'problema_reportado')) {
                $table->text('problema_reportado')->nullable();
            }
            if (!Schema::hasColumn('citas', 'prioridad')) {
                $table->string('prioridad')->default('media');
            }
            if (!Schema::hasColumn('citas', 'evidencias')) {
                $table->text('evidencias')->nullable();
            }
            if (!Schema::hasColumn('citas', 'descripcion') && !Schema::hasColumn('citas', 'titulo')) {
                 $table->text('descripcion')->nullable();
            } elseif (Schema::hasColumn('citas', 'titulo') && !Schema::hasColumn('citas', 'descripcion')) {
                 // Rename titulo to descripcion if needed, or just add it
                 $table->text('descripcion')->nullable();
            }
        });
    }

    private function ensureUsersColumns(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'checkin_token')) {
                $table->string('checkin_token', 64)->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'ine')) {
                $table->string('ine', 30)->nullable();
            }
            if (!Schema::hasColumn('users', 'imss')) {
                $table->string('imss', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'dias_trabajo')) {
                $table->json('dias_trabajo')->nullable();
            }
            if (!Schema::hasColumn('users', 'dias_descanso')) {
                $table->json('dias_descanso')->nullable();
            }
            if (!Schema::hasColumn('users', 'face_reference_path')) {
                $table->string('face_reference_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'face_enrolled_at')) {
                $table->timestamp('face_enrolled_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'face_last_verified_at')) {
                $table->timestamp('face_last_verified_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'face_provider')) {
                $table->string('face_provider', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'face_descriptor')) {
                $table->json('face_descriptor')->nullable();
            }
        });
    }

    private function ensureEmpresasColumns(): void
    {
        if (!Schema::hasTable('empresas')) {
            return;
        }

        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'nombre_razon_social')) {
                $table->string('nombre_razon_social')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'tipo_persona')) {
                $table->string('tipo_persona')->nullable();
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
            if (!Schema::hasColumn('empresas', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'telefono')) {
                $table->string('telefono')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'calle')) {
                $table->string('calle')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'numero_exterior')) {
                $table->string('numero_exterior')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'codigo_postal')) {
                $table->string('codigo_postal')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'colonia')) {
                $table->string('colonia')->nullable();
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
            if (!Schema::hasColumn('empresas', 'whatsapp_default_language')) {
                $table->string('whatsapp_default_language')->default('es_MX');
            }
            if (!Schema::hasColumn('empresas', 'whatsapp_template_maintenance')) {
                $table->string('whatsapp_template_maintenance')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('empresas', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private function ensureOperationalFixes(): void
    {
        if (!Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre_razon_social');
                $table->string('rfc')->nullable();
                $table->string('email')->nullable();
                $table->string('tipo_persona')->default('fisica');
                $table->string('regimen_fiscal')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->string('telefono')->nullable();
                $table->string('calle')->nullable();
                $table->string('numero_exterior')->nullable();
                $table->string('numero_interior')->nullable();
                $table->string('colonia')->nullable();
                $table->string('codigo_postal')->nullable();
                $table->string('municipio')->nullable();
                $table->string('estado')->nullable();
                $table->string('pais')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('prestamos')) {
            Schema::create('prestamos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->unsignedBigInteger('empleado_id')->nullable()->index();
                $table->string('folio')->nullable();
                $table->string('estado')->default('pendiente');
                $table->decimal('monto_total', 15, 2)->default(0);
                $table->decimal('monto_pendiente', 15, 2)->default(0);
                $table->timestamps();
            });
        }

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

        if (!Schema::hasTable('backup_logs')) {
            Schema::create('backup_logs', function (Blueprint $table) {
                $table->id();
                $table->string('filename')->nullable();
                $table->string('path')->nullable();
                $table->string('type')->nullable();
                $table->string('method')->nullable();
                $table->string('status')->nullable();
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->integer('size')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('prestamos')) {
            Schema::table('prestamos', function (Blueprint $table) {
                if (!Schema::hasColumn('prestamos', 'empleado_id')) {
                    $table->unsignedBigInteger('empleado_id')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('citas') && !Schema::hasColumn('citas', 'deleted_at')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('almacenes') && !Schema::hasColumn('almacenes', 'estado')) {
            Schema::table('almacenes', function (Blueprint $table) {
                $table->string('estado')->default('activo');
            });
        }

        if (!Schema::hasTable('rentas')) {
            Schema::create('rentas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->string('numero_contrato')->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->decimal('monto_mensual', 12, 2)->default(0);
                $table->string('estado')->default('activo');
                $table->softDeletes();
                $table->timestamps();
            });
        }

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
                $table->unsignedBigInteger('usuario_autoriza')->nullable();
                $table->unsignedBigInteger('usuario_envia')->nullable();
                $table->unsignedBigInteger('usuario_recibe')->nullable();
                $table->timestamp('fecha_envio')->nullable();
                $table->timestamp('fecha_recepcion')->nullable();
                $table->text('observaciones')->nullable();
                $table->string('referencia')->nullable();
                $table->decimal('costo_transporte', 15, 2)->nullable();
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
                $table->json('series_ids')->nullable();
                $table->timestamps();
            });
        }

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
    }
};
