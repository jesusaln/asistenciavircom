<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. CRM Campañas
        if (!Schema::hasTable('crm_campanias')) {
            Schema::create('crm_campanias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->string('nombre');
                $table->unsignedBigInteger('producto_id')->nullable()->index();
                $table->text('descripcion')->nullable();
                $table->text('objetivo')->nullable();
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->integer('meta_actividades_dia')->default(5);
                $table->boolean('activa')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 2. CRM Metas
        if (!Schema::hasTable('crm_metas')) {
            Schema::create('crm_metas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('tipo')->default('actividades');
                $table->integer('meta_diaria')->default(10);
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->boolean('activa')->default(true);
                $table->unsignedBigInteger('campania_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 3. CRM Scripts
        if (!Schema::hasTable('crm_scripts')) {
            Schema::create('crm_scripts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('campania_id')->nullable()->index();
                $table->string('nombre');
                $table->string('tipo')->default('apertura'); // apertura, presentacion, objecion, cierre, seguimiento
                $table->string('etapa')->default('general');
                $table->text('contenido');
                $table->text('tips')->nullable();
                $table->integer('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 4. CRM Actividades
        if (!Schema::hasTable('crm_actividades')) {
            Schema::create('crm_actividades', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('prospecto_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('tipo'); // llamada, email, whatsapp, visita, etc
                $table->string('resultado')->nullable(); // interesado, no_contesta, equivocado, etc
                $table->text('notas')->nullable();
                $table->integer('duracion_minutos')->nullable();
                $table->dateTime('proxima_actividad_at')->nullable();
                $table->string('proxima_actividad_tipo')->nullable();
                $table->timestamps();
            });
        }

        // 5. CRM Tareas
        if (!Schema::hasTable('crm_tareas')) {
            Schema::create('crm_tareas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('prospecto_id')->nullable()->index();
                $table->string('titulo');
                $table->text('descripcion')->nullable();
                $table->string('tipo')->default('llamar');
                $table->string('prioridad')->default('media');
                $table->dateTime('fecha_limite');
                $table->dateTime('completada_at')->nullable();
                $table->text('notas_resultado')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 6. WhatsApp Messages
        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->string('to');
                $table->string('template_name')->nullable();
                $table->json('template_params')->nullable();
                $table->string('message_id')->nullable();
                $table->string('status')->default('queued'); // queued, sent, delivered, read, failed
                $table->json('response')->nullable();
                $table->string('error_code')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('crm_tareas');
        Schema::dropIfExists('crm_actividades');
        Schema::dropIfExists('crm_scripts');
        Schema::dropIfExists('crm_metas');
        Schema::dropIfExists('crm_campanias');
    }
};
