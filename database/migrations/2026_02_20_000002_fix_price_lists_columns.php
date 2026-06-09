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
        if (Schema::hasTable('price_lists')) {
            Schema::table('price_lists', function (Blueprint $table) {
                if (Schema::hasColumn('price_lists', 'activo') && !Schema::hasColumn('price_lists', 'activa')) {
                    $table->renameColumn('activo', 'activa');
                } elseif (!Schema::hasColumn('price_lists', 'activa')) {
                    $table->boolean('activa')->default(true);
                }

                if (!Schema::hasColumn('price_lists', 'clave')) {
                    $table->string('clave')->nullable()->after('nombre');
                }
                if (!Schema::hasColumn('price_lists', 'descripcion')) {
                    $table->text('descripcion')->nullable()->after('clave');
                }
                if (!Schema::hasColumn('price_lists', 'orden')) {
                    $table->integer('orden')->default(0)->after('activa');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('price_lists')) {
            Schema::table('price_lists', function (Blueprint $table) {
                if (Schema::hasColumn('price_lists', 'activa') && !Schema::hasColumn('price_lists', 'activo')) {
                    $table->renameColumn('activa', 'activo');
                }
                $table->dropColumn(['clave', 'descripcion', 'orden']);
            });
        }
    }
};
