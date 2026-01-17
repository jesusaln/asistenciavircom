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
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->boolean('consume_poliza')->default(true)->after('activo')
                ->comment('Determina si los tickets de esta categoría descuentan folios de la póliza');
        });

        // Por defecto, lo que suene a asesoría o consulta no debería consumir póliza
        \DB::table('ticket_categories')
            ->where('nombre', 'like', '%Asesoría%')
            ->orWhere('nombre', 'like', '%Consultoría%')
            ->orWhere('nombre', 'like', '%Duda%')
            ->update(['consume_poliza' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->dropColumn(['consume_poliza']);
        });
    }
};
