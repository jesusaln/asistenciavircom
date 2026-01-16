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
            // Drop redundant English columns if they exist
            if (Schema::hasColumn('tickets', 'title')) {
                $table->dropColumn('title');
            }

            // Handle description -> descripcion transition
            if (Schema::hasColumn('tickets', 'description') && !Schema::hasColumn('tickets', 'descripcion')) {
                $table->renameColumn('description', 'descripcion');
            } elseif (Schema::hasColumn('tickets', 'description') && Schema::hasColumn('tickets', 'descripcion')) {
                $table->dropColumn('description'); // Drop duplicate if both exist
            } elseif (!Schema::hasColumn('tickets', 'description') && !Schema::hasColumn('tickets', 'descripcion')) {
                $table->text('descripcion')->nullable(); // Create if neither exist
            }

            // Make sure descripcion is nullable or has default
            if (Schema::hasColumn('tickets', 'descripcion')) {
                $table->text('descripcion')->nullable()->change();
            }

            // Clean up other potential old columns
            if (Schema::hasColumn('tickets', 'status') && Schema::hasColumn('tickets', 'estado')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('tickets', 'priority') && Schema::hasColumn('tickets', 'prioridad')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('tickets', 'technician_id') && Schema::hasColumn('tickets', 'asignado_id')) {
                $table->dropColumn('technician_id');
            }
            if (Schema::hasColumn('tickets', 'reported_by') && Schema::hasColumn('tickets', 'user_id')) {
                $table->dropColumn('reported_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Irreversible cleanup for production safety
            // We don't want to restore 'title' as it conflicts with 'titulo'
        });
    }
};
