<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            if (!Schema::hasColumn('prestamos', 'empleado_id')) {
                $table->foreignId('empleado_id')->nullable()->after('cliente_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            if (Schema::hasColumn('prestamos', 'empleado_id')) {
                $table->dropConstrainedForeignId('empleado_id');
            }
        });
    }
};
