<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->string('profile_photo_path', 2048)->nullable();
                $table->foreignId('current_team_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->boolean('es_tecnico')->default(false);
                $table->boolean('activo')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('empresas')) {
            Schema::create('empresas', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->string('nombre_razon_social')->nullable();
                $table->string('tipo_persona')->nullable();
                $table->string('tipo_identificacion')->nullable();
                $table->string('identificacion')->nullable();
                $table->string('curp')->nullable();
                $table->string('rfc')->nullable();
                $table->string('regimen_fiscal')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->string('email')->nullable();
                $table->string('telefono')->nullable();
                $table->string('calle')->nullable();
                $table->string('numero_exterior')->nullable();
                $table->string('numero_interior')->nullable();
                $table->string('colonia')->nullable();
                $table->string('codigo_postal')->nullable();
                $table->string('municipio')->nullable();
                $table->string('estado')->nullable();
                $table->string('pais')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('almacenes')) {
            Schema::create('almacenes', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->string('ubicacion')->nullable();
                $table->string('direccion')->nullable();
                $table->string('telefono')->nullable();
                $table->string('responsable')->nullable();
                $table->foreignId('empresa_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->string('estado')->default('activo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('slug')->nullable();
                $table->text('descripcion')->nullable();
                $table->string('estado')->default('activo');
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('marcas')) {
            Schema::create('marcas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->string('estado')->default('activo');
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('empresa_configuracion')) {
            Schema::create('empresa_configuracion', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->string('nombre_empresa')->nullable();
                $table->string('razon_social')->nullable();
                $table->string('rfc')->nullable();
                $table->string('regimen_fiscal', 10)->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->string('sitio_web')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('favicon_path')->nullable();
                $table->string('logo_reportes')->nullable();
                $table->string('color_principal')->default('#000000');
                $table->string('color_secundario')->default('#ffffff');
                $table->decimal('iva_porcentaje', 5, 2)->default(16);
                $table->decimal('isr_porcentaje', 5, 2)->default(0);
                $table->string('moneda')->default('MXN');
                $table->boolean('enable_retencion_iva')->default(false);
                $table->boolean('enable_retencion_isr')->default(false);
                $table->decimal('retencion_iva', 5, 2)->default(0);
                $table->decimal('retencion_isr', 5, 2)->default(0);
                $table->string('formato_numeros')->default('es-ES');
                $table->string('formato_fecha')->default('d/m/Y');
                $table->string('formato_hora')->default('H:i:s');
                $table->string('calle')->nullable();
                $table->string('numero_exterior')->nullable();
                $table->string('numero_interior')->nullable();
                $table->string('colonia')->nullable();
                $table->string('ciudad')->nullable();
                $table->string('estado')->nullable();
                $table->string('codigo_postal')->nullable();
                $table->string('pais')->nullable();
                $table->boolean('dark_mode_enabled')->default(false);
                $table->boolean('requerir_2fa')->default(false);

                // Nuevos campos para evitar fallos en EmpresaConfiguracion::getConfig()
                $table->boolean('backup_automatico')->default(true);
                $table->string('backup_hora_completo')->default('18:00');
                $table->integer('frecuencia_backup')->default(7);
                $table->integer('retencion_backups')->default(30);
                $table->boolean('mantenimiento')->default(false);
                $table->text('mensaje_mantenimiento')->nullable();
                $table->boolean('registro_usuarios')->default(true);
                $table->boolean('notificaciones_email')->default(true);

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cliente_id')->index();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('almacen_id')->nullable();
                $table->unsignedBigInteger('vendedor_id')->nullable();

                $table->unsignedBigInteger('cotizacion_id')->nullable();
                $table->string('numero_venta')->nullable();
                $table->string('folio')->nullable();
                $table->dateTime('fecha');
                $table->string('estado')->default('pendiente');
                $table->decimal('total', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento_general', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('impuestos', 15, 2)->default(0);
                $table->decimal('isr', 15, 2)->default(0);
                $table->decimal('retencion_iva', 15, 2)->default(0);
                $table->decimal('retencion_isr', 15, 2)->default(0);

                $table->boolean('pagado')->default(false);
                $table->dateTime('fecha_pago')->nullable();
                $table->dateTime('fecha_vencimiento')->nullable();
                $table->string('metodo_pago')->nullable();
                $table->string('forma_pago_sat', 10)->nullable();
                $table->string('metodo_pago_sat', 10)->nullable();
                $table->text('notas')->nullable();
                $table->string('notas_pago')->nullable();

                $table->decimal('saldo_pendiente', 15, 2)->default(0);
                $table->string('moneda')->default('MXN');
                $table->decimal('tipo_cambio', 12, 4)->default(1);

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('venta_items')) {
            Schema::create('venta_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('venta_id')->nullable();
                $table->unsignedBigInteger('ventable_id')->nullable();
                $table->string('ventable_type')->nullable();
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('costo_unitario', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('impuestos', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->decimal('descuento_monto', 15, 2)->default(0);
                $table->unsignedBigInteger('price_list_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // Mock producto_series to unblock other migrations if needed
        if (!Schema::hasTable('producto_series')) {
            Schema::create('producto_series', function (Blueprint $table) {
                $table->id();
                $table->string('serie')->nullable();
                $table->unsignedBigInteger('producto_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('servicios')) {
            Schema::create('servicios', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('descripcion')->nullable();
                $table->decimal('precio', 10, 2)->default(0);
                $table->decimal('comision_vendedor', 10, 2)->default(0);
                $table->string('estado')->default('activo');
                $table->boolean('activo')->default(true);
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('equipos')) {
            Schema::create('equipos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('marca')->nullable();
                $table->string('modelo')->nullable();
                $table->string('serie')->nullable();
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mantenimientos')) {
            Schema::create('mantenimientos', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->nullable();
                $table->unsignedBigInteger('equipo_id')->nullable();
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->text('descripcion')->nullable();
                $table->date('fecha_programada')->nullable();
                $table->string('estado')->default('pendiente');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function (Blueprint $table) {
                $table->id();
                $table->string('nombre_razon_social');
                $table->string('codigo')->nullable();
                $table->string('rfc')->nullable();
                $table->string('email')->nullable();
                $table->string('telefono')->nullable();
                $table->string('tipo_persona')->nullable();
                $table->string('regimen_fiscal')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->string('calle')->nullable();
                $table->string('numero_exterior')->nullable();
                $table->string('numero_interior')->nullable();
                $table->string('colonia')->nullable();
                $table->string('codigo_postal')->nullable();
                $table->string('municipio')->nullable();
                $table->string('estado')->nullable();
                $table->string('pais')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('compras')) {
            Schema::create('compras', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->nullable();
                $table->unsignedBigInteger('proveedor_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->decimal('total', 15, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('productos')) {
            Schema::create('productos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('sku')->nullable();
                $table->string('codigo')->nullable();
                $table->string('codigo_barras')->nullable();
                $table->string('numero_serie')->nullable();
                $table->text('descripcion')->nullable();
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('precio_compra', 15, 2)->default(0);
                $table->decimal('precio_venta', 15, 2)->default(0);
                $table->decimal('impuesto', 5, 2)->default(0);
                $table->decimal('margen_ganancia', 10, 2)->default(0);
                $table->decimal('comision_vendedor', 10, 2)->default(0);
                $table->string('unidad_medida')->nullable();
                $table->string('tipo_producto')->nullable();
                $table->decimal('stock', 15, 2)->default(0);
                $table->decimal('stock_minimo', 15, 2)->default(0);
                $table->decimal('reservado', 15, 2)->default(0);
                $table->unsignedBigInteger('categoria_id')->nullable();
                $table->unsignedBigInteger('marca_id')->nullable();
                $table->unsignedBigInteger('proveedor_id')->nullable();
                $table->unsignedBigInteger('almacen_id')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->string('sat_clave_prod_serv', 20)->nullable();
                $table->string('sat_clave_unidad', 10)->nullable();
                $table->string('sat_objeto_imp', 5)->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->boolean('expires')->default(false);
                $table->boolean('requiere_serie')->default(false);
                $table->string('imagen')->nullable();
                $table->string('estado')->default('activo');
                $table->boolean('activo')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('prestamos')) {
            Schema::create('prestamos', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->nullable();
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->string('estado')->default('pendiente');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crm_prospectos')) {
            Schema::create('crm_prospectos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('email')->nullable();
                $table->string('telefono')->nullable();
                $table->string('estado')->default('nuevo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('citas')) {
            Schema::create('citas', function (Blueprint $table) {
                $table->id();
                $table->string('titulo');
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('tecnico_id')->nullable();
                $table->dateTime('fecha_inicio')->nullable();
                $table->dateTime('fecha_fin')->nullable();
                $table->string('estado')->default('pendiente');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->string('name');
                $table->boolean('personal_team');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('team_user')) {
            Schema::create('team_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id');
                $table->foreignId('user_id');
                $table->string('role')->nullable();
                $table->timestamps();
                $table->unique(['team_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('team_invitations')) {
            Schema::create('team_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('email');
                $table->string('role')->nullable();
                $table->timestamps();
                $table->unique(['team_id', 'email']);
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        if (!Schema::hasTable('price_lists')) {
            Schema::create('price_lists', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('clave')->unique();
                $table->text('descripcion')->nullable();
                $table->boolean('activa')->default(true);
                $table->integer('orden')->default(0);
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_prices')) {
            Schema::create('product_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->unsignedBigInteger('price_list_id');
                $table->decimal('precio', 15, 2)->default(0);
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kit_items')) {
            Schema::create('kit_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kit_id');
                $table->string('item_type'); // producto o servicio
                $table->unsignedBigInteger('item_id');
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio_unitario', 15, 2)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('inventarios')) {
            Schema::create('inventarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->unsignedBigInteger('almacen_id');
                $table->decimal('cantidad', 15, 2)->default(0);
                $table->decimal('stock_minimo', 15, 2)->default(0);
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventario_movimientos')) {
            Schema::create('inventario_movimientos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->string('producto_nombre')->nullable();
                $table->unsignedBigInteger('almacen_id');
                $table->string('almacen_nombre')->nullable();
                $table->string('tipo'); // entrada, salida, traspaso, ajuste
                $table->decimal('cantidad', 15, 2);
                $table->decimal('stock_anterior', 15, 2)->default(0);
                $table->decimal('stock_posterior', 15, 2)->default(0);
                $table->string('motivo')->nullable();
                $table->string('referencia_type')->nullable();
                $table->unsignedBigInteger('referencia_id')->nullable();
                $table->json('detalles')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('usuario_nombre')->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lotes')) {
            Schema::create('lotes', function (Blueprint $table) {
                $table->id();
                $table->string('codigo')->nullable();
                $table->unsignedBigInteger('producto_id');
                $table->unsignedBigInteger('almacen_id');
                $table->decimal('cantidad_inicial', 15, 2);
                $table->decimal('cantidad_actual', 15, 2);
                $table->date('fecha_caducidad')->nullable();
                $table->decimal('costo_unitario', 15, 2)->nullable();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('producto_precio_historial')) {
            Schema::create('producto_precio_historial', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->decimal('precio_anterior', 15, 2);
                $table->decimal('precio_nuevo', 15, 2);
                $table->string('motivo')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('unidades_medida')) {
            Schema::create('unidades_medida', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('abreviatura')->nullable();
                $table->text('descripcion')->nullable();
                $table->string('estado')->default('activo');
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('venta_audit_logs')) {
            Schema::create('venta_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venta_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('status_before')->nullable();
                $table->string('status_after')->nullable();
                $table->json('changes')->nullable();
                $table->text('notes')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cuentas_por_cobrar')) {
            Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('cobrable_id');
                $table->string('cobrable_type');
                $table->decimal('monto_total', 15, 2);
                $table->decimal('monto_pagado', 15, 2)->default(0);
                $table->decimal('monto_pendiente', 15, 2);
                $table->date('fecha_vencimiento');
                $table->string('estado')->default('pendiente');
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('venta_item_series')) {
            Schema::create('venta_item_series', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venta_item_id');
                $table->unsignedBigInteger('producto_serie_id');
                $table->string('numero_serie');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cuentas_bancarias')) {
            Schema::create('cuentas_bancarias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->string('nombre');
                $table->string('banco')->nullable();
                $table->string('numero_cuenta')->nullable();
                $table->string('clabe')->nullable();
                $table->decimal('saldo_inicial', 15, 2)->default(0);
                $table->decimal('saldo_actual', 15, 2)->default(0);
                $table->string('moneda', 3)->default('MXN');
                $table->string('tipo')->nullable();
                $table->boolean('activa')->default(true);
                $table->text('notas')->nullable();
                $table->string('color', 7)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('movimientos_bancarios')) {
            Schema::create('movimientos_bancarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cuenta_bancaria_id');
                $table->dateTime('fecha');
                $table->string('concepto');
                $table->decimal('monto', 15, 2);
                $table->string('tipo'); // deposito, retiro
                $table->string('origen_tipo')->nullable(); // venta, renta, etc
                $table->string('banco')->nullable();
                $table->string('estado')->default('pendiente'); // conciliado, pendiente
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('entregas_dinero')) {
            Schema::create('entregas_dinero', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->date('fecha_entrega')->nullable();
                $table->decimal('monto_efectivo', 15, 2)->default(0);
                $table->decimal('monto_transferencia', 15, 2)->default(0);
                $table->decimal('monto_cheques', 15, 2)->default(0);
                $table->decimal('monto_tarjetas', 15, 2)->default(0);
                $table->decimal('monto_otros', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->text('notas')->nullable();
                $table->string('tipo_origen')->nullable();
                $table->unsignedBigInteger('id_origen')->nullable();
                $table->unsignedBigInteger('recibido_por')->nullable();
                $table->datetime('fecha_recibido')->nullable();
                $table->text('notas_recibido')->nullable();
                $table->unsignedBigInteger('cuenta_bancaria_id')->nullable();
                $table->boolean('entregado_responsable')->default(false);
                $table->datetime('fecha_entregado_responsable')->nullable();
                $table->string('responsable_organizacion')->nullable();
                $table->text('notas_entrega_responsable')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cfdis')) {
            Schema::create('cfdis', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('venta_id')->nullable();
                $table->unsignedBigInteger('factura_id')->nullable();
                $table->unsignedBigInteger('cfdiable_id')->nullable();
                $table->string('cfdiable_type')->nullable();
                $table->unsignedBigInteger('cfdi_relacionado_id')->nullable();

                $table->string('tipo_comprobante', 1)->default('I');
                $table->string('direccion', 10)->default('emitido');
                $table->string('rfc_emisor')->nullable();
                $table->string('nombre_emisor')->nullable();
                $table->string('regimen_fiscal_emisor')->nullable();
                $table->string('rfc_receptor')->nullable();
                $table->string('nombre_receptor')->nullable();

                $table->string('serie')->nullable();
                $table->string('folio')->nullable();
                $table->string('uuid')->nullable()->index();
                $table->datetime('fecha_timbrado')->nullable();
                $table->string('no_certificado_sat')->nullable();
                $table->string('no_certificado_cfdi')->nullable();
                $table->text('sello_sat')->nullable();
                $table->text('sello_cfdi')->nullable();
                $table->text('cadena_original')->nullable();
                $table->string('estatus')->default('borrador');
                $table->datetime('fecha_emision')->nullable();
                $table->datetime('fecha_cancelacion')->nullable();

                $table->string('moneda', 3)->default('MXN');
                $table->decimal('tipo_cambio', 15, 4)->default(1);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('descuento', 15, 2)->default(0);
                $table->decimal('total_impuestos_trasladados', 15, 2)->default(0);
                $table->decimal('total_impuestos_retenidos', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);

                $table->string('metodo_pago')->nullable();
                $table->string('forma_pago')->nullable();
                $table->string('condiciones_pago')->nullable();
                $table->string('uso_cfdi')->nullable();
                $table->json('complementos')->nullable();

                $table->string('pac_rfc')->nullable();
                $table->string('pac_nombre')->nullable();
                $table->string('xml_url')->nullable();
                $table->string('pdf_url')->nullable();
                $table->text('observaciones')->nullable();
                $table->json('datos_adicionales')->nullable();

                $table->unsignedBigInteger('creado_por')->nullable();
                $table->unsignedBigInteger('actualizado_por')->nullable();
                $table->unsignedBigInteger('cancelado_por')->nullable();
                $table->string('motivo_cancelacion')->nullable();
                $table->string('folio_sustitucion')->nullable();
                $table->text('acuse_cancelacion')->nullable();

                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cfdi_conceptos')) {
            Schema::create('cfdi_conceptos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cfdi_id');
                $table->string('clave_prod_serv');
                $table->string('no_identificacion')->nullable();
                $table->decimal('cantidad', 18, 6);
                $table->string('clave_unidad');
                $table->string('unidad')->nullable();
                $table->text('descripcion');
                $table->decimal('valor_unitario', 18, 6);
                $table->decimal('importe', 18, 6);
                $table->decimal('descuento', 18, 6)->default(0);
                $table->json('impuestos')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Don't drop tables in this rescue migration to be safe
    }
};
