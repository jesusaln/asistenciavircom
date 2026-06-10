<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('id')->index();
            }
        });

        Schema::table('producto_series', function (Blueprint $table) {
            if (!Schema::hasColumn('producto_series', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('empresa_id');
        });

        Schema::table('producto_series', function (Blueprint $table) {
            $table->dropColumn('empresa_id');
        });
    }
};
