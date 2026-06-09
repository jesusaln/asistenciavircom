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
            if (!Schema::hasColumn('tickets', 'numero')) {
                $table->string('numero')->nullable()->after('folio');
            }
            if (!Schema::hasColumn('tickets', 'resuelto_at')) {
                $table->timestamp('resuelto_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('tickets', 'fecha_limite')) {
                $table->timestamp('fecha_limite')->nullable()->after('resuelto_at');
            }
            if (!Schema::hasColumn('tickets', 'primera_respuesta_at')) {
                $table->timestamp('primera_respuesta_at')->nullable()->after('fecha_limite');
            }
            if (!Schema::hasColumn('tickets', 'categoria_id')) {
                $table->foreignId('categoria_id')->nullable()->after('reported_by')->constrained('ticket_categories')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn(['numero', 'resuelto_at', 'fecha_limite', 'primera_respuesta_at', 'categoria_id']);
        });
    }
};
