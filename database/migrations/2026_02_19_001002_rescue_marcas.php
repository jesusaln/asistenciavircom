<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marcas', function (Blueprint $table) {
            if (!Schema::hasColumn('marcas', 'estado')) {
                $table->string('estado')->default('activo');
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'marca_id')) {
                $table->unsignedBigInteger('marca_id')->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
