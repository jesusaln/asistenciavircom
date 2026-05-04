<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add two-factor authentication columns to users (Laravel Fortify)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('empresa_id');
            }
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
            if (!Schema::hasColumn('users', 'current_team_id')) {
                $table->unsignedBigInteger('current_team_id')->nullable()->after('two_factor_confirmed_at');
            }
            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path', 2048)->nullable()->after('current_team_id');
            }
        });

        // Create backup_logs table if it doesn't exist
        if (!Schema::hasTable('backup_logs')) {
            Schema::create('backup_logs', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('path');
                $table->string('type')->nullable();
                $table->string('method')->nullable();
                $table->string('status')->default('pending');
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->timestamps();
            });
        }

        // Create cuentas_por_cobrar table if it doesn't exist
        if (!Schema::hasTable('cuentas_por_cobrar')) {
            Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->unsignedBigInteger('cliente_id')->nullable()->index();
                $table->string('cobrable_type')->nullable();
                $table->unsignedBigInteger('cobrable_id')->nullable();
                $table->string('tipo')->default('venta'); // venta, renta, servicio
                $table->decimal('monto', 15, 2)->default(0);
                $table->decimal('saldo', 15, 2)->default(0);
                $table->decimal('pagado', 15, 2)->default(0);
                $table->date('fecha_emision')->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->date('fecha_pago')->nullable();
                $table->string('estado')->default('pendiente'); // pendiente, pagada, vencida, cancelada
                $table->string('metodo_pago')->nullable();
                $table->text('notas')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create personal_access_tokens table if it doesn't exist (Laravel Sanctum)
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

        // Create password_resets table if it doesn't exist
        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Create failed_jobs table if it doesn't exist
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // Create jobs table if it doesn't exist
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        // Create job_batches table if it doesn't exist
        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->text('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        // Create notifications table if it doesn't exist
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
    }

    public function down(): void
    {
        // No need to implement down() for this migration
    }
};
