<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trading_orders_queue')) {
            Schema::create('trading_orders_queue', function (Blueprint $table) {
                $table->id();
                $table->string('symbol');
                $table->string('side');
                $table->decimal('amount', 16, 8);
                $table->string('status')->default('pending'); // pending, success, error
                $table->text('response_log')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_orders_queue');
    }
};
