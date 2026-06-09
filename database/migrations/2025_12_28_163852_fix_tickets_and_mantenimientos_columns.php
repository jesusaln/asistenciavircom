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
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'title')) {
                $table->renameColumn('title', 'titulo');
            }
            if (Schema::hasColumn('tickets', 'status')) {
                $table->renameColumn('status', 'estado');
            }
            if (Schema::hasColumn('tickets', 'priority')) {
                $table->renameColumn('priority', 'prioridad');
            }
            if (Schema::hasColumn('tickets', 'technician_id')) {
                $table->renameColumn('technician_id', 'asignado_id');
            }
            if (Schema::hasColumn('tickets', 'reported_by')) {
                $table->renameColumn('reported_by', 'user_id');
            }
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimientos', 'tecnico_id')) {
                $table->foreignId('tecnico_id')->nullable()->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'titulo')) {
                $table->renameColumn('titulo', 'title');
            }
            if (Schema::hasColumn('tickets', 'estado')) {
                $table->renameColumn('estado', 'status');
            }
            if (Schema::hasColumn('tickets', 'prioridad')) {
                $table->renameColumn('prioridad', 'priority');
            }
            if (Schema::hasColumn('tickets', 'asignado_id')) {
                $table->renameColumn('asignado_id', 'technician_id');
            }
            if (Schema::hasColumn('tickets', 'user_id')) {
                $table->renameColumn('user_id', 'reported_by');
            }
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['tecnico_id']);
            $table->dropColumn('tecnico_id');
        });
    }
};
