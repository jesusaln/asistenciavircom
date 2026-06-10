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
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('empresas')) {
            Schema::create('empresas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre_fiscal')->nullable();
                $table->string('nombre_comercial')->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('almacenes')) {
            Schema::create('almacenes', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->foreignId('empresa_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('empresa_configuracion')) {
            Schema::create('empresa_configuracion', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->string('nombre_empresa')->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->string('logo_url')->nullable();
                $table->string('favicon_url')->nullable();
                $table->string('color_principal')->default('#000000');
                $table->string('color_secundario')->default('#ffffff');
                //$table->string('color_terciario')->default('#cccccc'); // Removed to avoid conflict
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cliente_id')->index(); // FK added later if needed or via constraint
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('almacen_id')->nullable();
                $table->unsignedBigInteger('vendedor_id')->nullable(); // user_id

                $table->string('numero_venta')->nullable();
                $table->string('folio')->nullable();
                $table->dateTime('fecha');
                $table->string('estado')->default('pendiente');
                $table->decimal('total', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('impuestos', 15, 2)->default(0);

                $table->boolean('pagado')->default(false);
                $table->dateTime('fecha_pago')->nullable();
                $table->dateTime('fecha_vencimiento')->nullable();
                $table->string('metodo_pago')->nullable();
                $table->text('notas')->nullable();
                $table->string('notas_pago')->nullable();

                $table->decimal('saldo_pendiente', 15, 2)->default(0);
                $table->string('moneda')->default('MXN');
                $table->decimal('tipo_cambio', 12, 4)->default(1);

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

        // Mock producto_series to unblock other migrations if needed (risky but unblocks dev)
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
                $table->boolean('activo')->default(true);
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
                $table->text('descripcion')->nullable();
                $table->date('fecha_programada')->nullable();
                $table->string('estado')->default('pendiente');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('compras')) {
            Schema::create('compras', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->nullable();
                $table->unsignedBigInteger('proveedor_id')->nullable();
                $table->decimal('total', 15, 2)->default(0);
                $table->string('estado')->default('pendiente');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('productos')) {
            Schema::create('productos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('descripcion')->nullable();
                $table->decimal('precio', 15, 2)->default(0);
                $table->integer('stock')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();
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

        if (!Schema::hasTable('venta_items')) {
            Schema::create('venta_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venta_id')->index();
                $table->string('ventable_type')->nullable(); // Para polimorfismo con productos/servicios
                $table->unsignedBigInteger('ventable_id')->nullable();
                $table->decimal('cantidad', 15, 2)->default(1);
                $table->decimal('precio', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('cuentas_bancarias')) {
            Schema::create('cuentas_bancarias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre');
                $table->string('banco')->nullable();
                $table->string('numero_cuenta')->nullable();
                $table->string('clabe')->nullable();
                $table->decimal('saldo_inicial', 15, 2)->default(0);
                $table->decimal('saldo_actual', 15, 2)->default(0);
                $table->string('moneda', 5)->default('MXN');
                $table->string('tipo', 20)->default('corriente'); // corriente, ahorro, tarjeta_credito, nomina, paypal, etc.
                $table->boolean('activa')->default(true);
                $table->text('notas')->nullable();
                $table->string('color', 10)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('movimientos_bancarios')) {
            Schema::create('movimientos_bancarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cuenta_bancaria_id')->index();
                $table->date('fecha');
                $table->string('concepto');
                $table->string('referencia')->nullable();
                $table->decimal('monto', 15, 2);
                $table->decimal('saldo', 15, 2)->default(0);
                $table->string('tipo', 10); // deposito, retiro
                $table->string('origen_tipo')->nullable(); // venta, compra, nomina, prestamo, etc.
                $table->string('banco')->nullable();
                $table->string('estado', 20)->default('pendiente'); // pendiente, conciliado, ignorado
                $table->string('conciliable_type')->nullable();
                $table->unsignedBigInteger('conciliable_id')->nullable();
                $table->string('archivo_origen')->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->unsignedBigInteger('conciliado_por')->nullable();
                $table->timestamp('conciliado_at')->nullable();
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['conciliable_type', 'conciliable_id'], 'movimiento_conciliable_index');
            });
        }
    }

    public function down(): void
    {
        // Don't drop tables in this rescue migration to be safe
    }
};
