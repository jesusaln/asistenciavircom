<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Añadir columnas polimórficas de Laravel si no existen
            if (!Schema::hasColumn('notifications', 'notifiable_id')) {
                $table->uuid('notifiable_id')->nullable()->after('data');
            }
            if (!Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->string('notifiable_type')->nullable()->after('notifiable_id');
            }
            
            // Asegurarnos de que el ID sea UUID si Laravel lo está intentando usar así
            // El error mostraba: values (67c710fe-a082-4994-af21-31adc767de27, ...)
            // Así que el ID de la tabla debe ser UUID o String.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['notifiable_id', 'notifiable_type']);
        });
    }
};
