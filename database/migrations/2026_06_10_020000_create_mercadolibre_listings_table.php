<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mercadolibre_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('producto_id')->constrained('productos');
            $table->bigInteger('listing_id')->unique();
            $table->string('permalink')->nullable();
            $table->string('status')->default('active'); // active, paused, closed
            $table->decimal('price', 15, 2);
            $table->integer('stock_published')->default(0);
            $table->string('meli_category_id', 50)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadolibre_listings');
    }
};
