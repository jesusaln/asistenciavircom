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
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            // Blog Robot Configuration
            $table->string('blog_robot_token')->nullable()->after('n8n_webhook_blog'); // Token de seguridad para que el robot se autentique
            $table->boolean('blog_robot_enabled')->default(false)->after('blog_robot_token'); // Interruptor general
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            //
        });
    }
};
