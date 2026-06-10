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
        if (!Schema::hasTable('caja_chica')) {
            Schema::create('caja_chica', function (Blueprint $table) {
                $table->id();
                $table->string('concepto');
                $table->decimal('monto', 10, 2);
                $table->string('tipo');
                $table->date('fecha');
                $table->string('comprobante_path')->nullable();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('categoria')->nullable();
                $table->text('nota')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_chica');
    }
};
