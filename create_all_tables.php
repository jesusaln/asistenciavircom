<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

$tables = [
    'landing_ofertas' => function (Blueprint $table) {
        $table->id();
        $table->string('titulo');
        $table->text('descripcion')->nullable();
        $table->string('imagen')->nullable();
        $table->decimal('precio_original', 15, 2)->nullable();
        $table->decimal('precio_oferta', 15, 2)->nullable();
        $table->integer('descuento_porcentaje')->nullable();
        $table->dateTime('fecha_inicio')->nullable();
        $table->dateTime('fecha_fin')->nullable();
        $table->boolean('activo')->default(true);
        $table->integer('orden')->default(0);
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'categorias' => function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('slug')->nullable();
        $table->text('descripcion')->nullable();
        $table->string('imagen')->nullable();
        $table->boolean('activo')->default(true);
        $table->integer('orden')->default(0);
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'proveedores' => function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('rfc')->nullable();
        $table->string('email')->nullable();
        $table->string('telefono')->nullable();
        $table->boolean('activo')->default(true);
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'proyectos' => function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->text('descripcion')->nullable();
        $table->string('estado')->default('activo');
        $table->unsignedBigInteger('cliente_id')->nullable();
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'polizas_servicio' => function (Blueprint $table) {
        $table->id();
        $table->string('folio')->nullable();
        $table->unsignedBigInteger('cliente_id')->index();
        $table->unsignedBigInteger('plan_id')->nullable();
        $table->string('estado')->default('activo');
        $table->date('fecha_inicio')->nullable();
        $table->date('fecha_fin')->nullable();
        $table->decimal('monto', 15, 2)->default(0);
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    },
    'cuentas_por_cobrar' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('cliente_id')->index();
        $table->unsignedBigInteger('venta_id')->nullable();
        $table->decimal('monto_original', 15, 2)->default(0);
        $table->decimal('monto_pendiente', 15, 2)->default(0);
        $table->date('fecha_vencimiento')->nullable();
        $table->string('estado')->default('pendiente');
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'herramientas' => function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('descripcion')->nullable();
        $table->string('estado')->default('disponible');
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'blog_posts' => function (Blueprint $table) {
        $table->id();
        $table->string('titulo');
        $table->string('slug')->unique();
        $table->text('contenido')->nullable();
        $table->string('imagen')->nullable();
        $table->boolean('publicado')->default(false);
        $table->dateTime('fecha_publicacion')->nullable();
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'disponibilidad_tecnicos' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->index();
        $table->integer('dia_semana'); // 0=Domingo, 1=Lunes, etc.
        $table->time('hora_inicio');
        $table->time('hora_fin');
        $table->boolean('activo')->default(true);
        $table->timestamps();
    },
    'dias_bloqueados' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable(); // null = aplica a todos
        $table->date('fecha');
        $table->string('motivo')->nullable();
        $table->timestamps();
    },
    'pedidos_online' => function (Blueprint $table) {
        $table->id();
        $table->string('numero_pedido')->unique();
        $table->string('nombre');
        $table->string('email');
        $table->string('telefono')->nullable();
        $table->text('direccion')->nullable();
        $table->json('items')->nullable();
        $table->decimal('subtotal', 15, 2)->default(0);
        $table->decimal('costo_envio', 15, 2)->default(0);
        $table->decimal('total', 15, 2)->default(0);
        $table->string('estado')->default('pendiente');
        $table->string('metodo_pago')->nullable();
        $table->string('referencia_pago')->nullable();
        $table->unsignedBigInteger('cliente_id')->nullable();
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'clientes_tienda' => function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('email')->unique();
        $table->string('password')->nullable();
        $table->string('telefono')->nullable();
        $table->string('provider')->nullable();
        $table->string('provider_id')->nullable();
        $table->timestamps();
    },
    'credenciales' => function (Blueprint $table) {
        $table->id();
        $table->string('titulo');
        $table->string('usuario')->nullable();
        $table->text('password_encrypted')->nullable();
        $table->text('notas')->nullable();
        $table->unsignedBigInteger('cliente_id')->nullable();
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'ticket_categories' => function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('color')->default('#3B82F6');
        $table->boolean('activo')->default(true);
        $table->timestamps();
    },
    'knowledge_base_articles' => function (Blueprint $table) {
        $table->id();
        $table->string('titulo');
        $table->text('contenido');
        $table->unsignedBigInteger('categoria_id')->nullable();
        $table->boolean('publicado')->default(true);
        $table->timestamps();
    },
    'ticket_comments' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ticket_id')->index();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('cliente_id')->nullable();
        $table->text('contenido');
        $table->boolean('es_interno')->default(false);
        $table->timestamps();
    },
    'cita_items' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('cita_id')->index();
        $table->string('tipo')->default('servicio');
        $table->string('descripcion');
        $table->decimal('cantidad', 15, 2)->default(1);
        $table->decimal('precio', 15, 2)->default(0);
        $table->decimal('total', 15, 2)->default(0);
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'proyecto_productos' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('proyecto_id')->index();
        $table->unsignedBigInteger('producto_id')->index();
        $table->decimal('cantidad', 15, 2)->default(1);
        $table->timestamps();
    },
    'poliza_servicio_items' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('poliza_servicio_id')->index();
        $table->unsignedBigInteger('servicio_id')->nullable();
        $table->string('descripcion')->nullable();
        $table->decimal('cantidad', 15, 2)->default(1);
        $table->timestamps();
    },
    'poliza_servicio_equipos' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('poliza_servicio_id')->index();
        $table->unsignedBigInteger('equipo_id')->nullable();
        $table->timestamps();
    },
    'equipo_poliza_servicio' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('poliza_servicio_id')->index();
        $table->unsignedBigInteger('equipo_id')->index();
        $table->timestamps();
    },
    'folio_configs' => function (Blueprint $table) {
        $table->id();
        $table->string('tipo')->unique();
        $table->string('prefijo')->default('');
        $table->integer('siguiente')->default(1);
        $table->integer('longitud')->default(5);
        $table->unsignedBigInteger('empresa_id')->nullable();
        $table->timestamps();
    },
    'sat_regimen_fiscal' => function (Blueprint $table) {
        $table->id();
        $table->string('clave')->unique();
        $table->string('descripcion');
        $table->boolean('persona_fisica')->default(true);
        $table->boolean('persona_moral')->default(true);
        $table->timestamps();
    },
    'sat_uso_cfdi' => function (Blueprint $table) {
        $table->id();
        $table->string('clave')->unique();
        $table->string('descripcion');
        $table->boolean('persona_fisica')->default(true);
        $table->boolean('persona_moral')->default(true);
        $table->timestamps();
    },
    'sat_estados' => function (Blueprint $table) {
        $table->id();
        $table->string('clave')->unique();
        $table->string('nombre');
        $table->string('pais')->default('MEX');
        $table->timestamps();
    },
    'roles' => function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('guard_name')->default('web');
        $table->timestamps();
    },
    'permissions' => function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('guard_name')->default('web');
        $table->timestamps();
    },
    'model_has_roles' => function (Blueprint $table) {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->primary(['role_id', 'model_id', 'model_type']);
    },
    'model_has_permissions' => function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->primary(['permission_id', 'model_id', 'model_type']);
    },
    'role_has_permissions' => function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
        $table->primary(['permission_id', 'role_id']);
    },
    'personal_access_tokens' => function (Blueprint $table) {
        $table->id();
        $table->morphs('tokenable');
        $table->string('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    },
    'teams' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->index();
        $table->string('name');
        $table->boolean('personal_team')->default(false);
        $table->timestamps();
    },
    'team_user' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id');
        $table->unsignedBigInteger('user_id');
        $table->string('role')->nullable();
        $table->timestamps();
    },
    'team_invitations' => function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id');
        $table->string('email');
        $table->string('role')->nullable();
        $table->timestamps();
    },
];

$created = 0;
$skipped = 0;

foreach ($tables as $name => $callback) {
    if (!Schema::hasTable($name)) {
        try {
            Schema::create($name, $callback);
            echo "✓ $name\n";
            $created++;
        } catch (\Exception $e) {
            echo "✗ $name: " . $e->getMessage() . "\n";
        }
    } else {
        $skipped++;
    }
}

echo "\nCreated: $created, Skipped (existed): $skipped\n";
