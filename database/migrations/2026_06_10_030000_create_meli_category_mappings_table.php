<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meli_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('cva_grupo')->unique();
            $table->string('meli_category_id', 50);
            $table->jsonb('attributes_template')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meli_category_mappings');
    }
};
