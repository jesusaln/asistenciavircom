<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movimientos_bancarios', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos_bancarios', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_bancarios', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos_bancarios', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
