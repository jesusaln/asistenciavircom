<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            // Hero Section - Contenido configurable del landing
            $table->string('hero_titulo', 500)->nullable()->after('descripcion_empresa');
            $table->string('hero_subtitulo', 500)->nullable()->after('hero_titulo');
            $table->text('hero_descripcion')->nullable()->after('hero_subtitulo');
            $table->string('hero_cta_primario', 255)->nullable()->after('hero_descripcion');
            $table->string('hero_cta_primario_url', 500)->nullable()->after('hero_cta_primario');
            $table->string('hero_cta_secundario', 255)->nullable()->after('hero_cta_primario_url');
            $table->string('hero_cta_secundario_url', 500)->nullable()->after('hero_cta_secundario');
            $table->string('hero_imagen_url', 500)->nullable()->after('hero_cta_secundario_url');
            $table->string('hero_badge_texto', 255)->nullable()->after('hero_imagen_url');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'hero_titulo',
                'hero_subtitulo',
                'hero_descripcion',
                'hero_cta_primario',
                'hero_cta_primario_url',
                'hero_cta_secundario',
                'hero_cta_secundario_url',
                'hero_imagen_url',
                'hero_badge_texto',
            ]);
        });
    }
};
