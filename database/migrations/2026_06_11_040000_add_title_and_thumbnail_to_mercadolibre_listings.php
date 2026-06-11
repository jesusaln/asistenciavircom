<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mercadolibre_listings', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->string('thumbnail')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mercadolibre_listings', function (Blueprint $table) {
            $table->dropColumn(['title', 'thumbnail']);
        });
    }
};
