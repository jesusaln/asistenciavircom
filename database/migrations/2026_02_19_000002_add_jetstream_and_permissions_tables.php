<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add activo column to clientes
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'activo')) {
                $table->boolean('activo')->default(true)->after('deleted_at');
            }
        });

        // Add cfdis columns
        $cfdisColumns = Schema::getColumnListing('cfdis');

        if (!in_array('estado', $cfdisColumns)) {
            Schema::table('cfdis', function (Blueprint $table) {
                $table->string('estado')->nullable()->after('xml_contenido');
            });
        }

        if (!in_array('rfc_emisor', $cfdisColumns)) {
            Schema::table('cfdis', function (Blueprint $table) {
                $table->string('rfc_emisor')->nullable()->after('estado');
            });
        }

        if (!in_array('rfc_receptor', $cfdisColumns)) {
            Schema::table('cfdis', function (Blueprint $table) {
                $table->string('rfc_receptor')->nullable()->after('rfc_emisor');
            });
        }

        if (!in_array('fecha_timbrado', $cfdisColumns)) {
            Schema::table('cfdis', function (Blueprint $table) {
                $table->timestamp('fecha_timbrado')->nullable()->after('rfc_receptor');
            });
        }

        // Create teams table (Laravel Jetstream) - with owned_by column directly
        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->foreignId('owned_by')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('personal_team');
                $table->timestamps();
            });
        }

        // Create team_user pivot table
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

        // Create team_invitations table
        if (!Schema::hasTable('team_invitations')) {
            Schema::create('team_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->index();
                $table->foreignId('user_id')->index();
                $table->string('email');
                $table->string('role')->nullable();
                $table->timestamps();
                $table->unique(['team_id', 'email']);
            });
        }

        // Create permissions table (Spatie)
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->foreignId('parent_id')->nullable()->index();
                $table->integer('sort_order')->default(1);
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        // Create roles table (Spatie)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        // Create model_has_permissions table (Spatie)
        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id')->index();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id')->index();
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        // Create model_has_roles table (Spatie)
        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->index();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id')->index();
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        // Create role_has_permissions table (Spatie)
        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id')->index();
                $table->unsignedBigInteger('role_id')->index();
                $table->primary(['permission_id', 'role_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
    }
};
