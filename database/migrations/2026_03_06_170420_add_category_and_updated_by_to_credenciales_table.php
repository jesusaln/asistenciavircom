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
        Schema::table('credenciales', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('nombre'); // Wifi, Router, DVR, etc.
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            $table->timestamp('last_revealed_at')->nullable()->after('updated_at');
        });

        Schema::create('credencial_acceso_logs', function (Blueprint $table) {
            // Ya existía según el modelo, pero por si acaso o para mejorarla
            if (!Schema::hasTable('credencial_acceso_logs')) {
                $table->id();
                $table->foreignId('credencial_id')->constrained('credenciales')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('accion'); // revelado, editado, creado, eliminado
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credenciales', function (Blueprint $table) {
            $table->dropColumn(['categoria', 'updated_by', 'last_revealed_at']);
        });
    }
};
