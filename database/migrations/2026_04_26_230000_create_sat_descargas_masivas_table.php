<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sat_descargas_masivas')) {
            Schema::create('sat_descargas_masivas', function (Blueprint $table) {
                $table->id();
                $table->string('direccion')->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->string('status')->nullable();
                $table->string('request_id')->nullable();
                $table->json('paquetes')->nullable();
                $table->integer('total_cfdis')->default(0);
                $table->integer('inserted_cfdis')->default(0);
                $table->integer('duplicate_cfdis')->default(0);
                $table->integer('error_cfdis')->default(0);
                $table->text('last_error')->nullable();
                $table->json('errors')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_descargas_masivas');
    }
};
