<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('empresa_configuracion')) {
            return;
        }

        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_configuracion', 'images_webp_enabled')) {
                $table->boolean('images_webp_enabled')->default(true)->after('favicon_path');
            }

            if (!Schema::hasColumn('empresa_configuracion', 'images_webp_quality')) {
                $table->unsignedTinyInteger('images_webp_quality')->default(80)->after('images_webp_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('empresa_configuracion')) {
            return;
        }

        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_configuracion', 'images_webp_quality')) {
                $table->dropColumn('images_webp_quality');
            }

            if (Schema::hasColumn('empresa_configuracion', 'images_webp_enabled')) {
                $table->dropColumn('images_webp_enabled');
            }
        });
    }
};

