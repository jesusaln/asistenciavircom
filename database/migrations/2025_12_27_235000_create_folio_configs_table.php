<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        try {
            if (!Schema::hasTable('folio_configs')) {
                Schema::create('folio_configs', function (Blueprint $table) {
                    $table->id();
                    $table->string('document_type')->unique();
                    $table->string('prefix')->nullable();
                    $table->unsignedInteger('current_number')->default(0);
                    $table->unsignedInteger('padding')->default(3);
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            if ($e->getCode() == '42P07' || str_contains($e->getMessage(), 'already exists')) {
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_configs');
    }
};
