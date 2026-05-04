<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('venta_audit_logs')) {
            Schema::create('venta_audit_logs', function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->unsignedBigInteger('venta_id')->nullable();
                $blueprint->unsignedBigInteger('user_id')->nullable();
                $blueprint->string('action');
                $blueprint->string('status_before')->nullable();
                $blueprint->string('status_after')->nullable();
                $blueprint->jsonb('changes')->nullable();
                $blueprint->text('notes')->nullable();
                $blueprint->ipAddress('ip_address')->nullable();
                $blueprint->text('user_agent')->nullable();
                $blueprint->timestamps();

                $blueprint->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
                $blueprint->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_audit_logs');
    }
};
