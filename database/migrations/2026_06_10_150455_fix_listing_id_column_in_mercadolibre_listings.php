<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("mercadolibre_listings", function (Blueprint $table) {
            $table->string("listing_id", 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table("mercadolibre_listings", function (Blueprint $table) {
            $table->bigInteger("listing_id")->change();
        });
    }
};
