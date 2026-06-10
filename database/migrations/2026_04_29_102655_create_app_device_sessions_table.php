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
        if (!Schema::hasTable('app_device_sessions')) {
            Schema::create('app_device_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('uuid')->unique();
                $table->string('platform')->nullable(); // android, ios, web
                $table->string('version')->nullable();  // e.g. 0.0.1
                $table->string('os_version')->nullable();
                $table->string('model')->nullable();
                $table->string('manufacturer')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->json('attributes')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_device_sessions');
    }
};
