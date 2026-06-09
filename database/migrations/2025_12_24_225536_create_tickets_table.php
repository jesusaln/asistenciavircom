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
        try {
            if (!Schema::hasTable('tickets')) {
                Schema::create('tickets', function (Blueprint $table) {
                    $table->id();
                    $table->string('folio');
                    $table->string('title');
                    $table->text('description');
                    $table->string('status')->default('pendiente');
                    $table->string('priority')->default('media');
                    $table->timestamp('scheduled_at')->nullable();
                    $table->timestamp('completed_at')->nullable();
                    $table->foreignId('cliente_id')->constrained('clientes');
                    $table->foreignId('technician_id')->nullable()->constrained('users');
                    $table->foreignId('reported_by')->nullable()->constrained('users');
                    $table->timestamps();
                    $table->softDeletes();
                });
            }
        } catch (\Throwable $e) { // Catch ANY error/exception
            // Check for Postgres duplicate table error code 42P07 or message
            // Uses loose comparison and string check for maximum robustness
            if ($e->getCode() == '42P07' || str_contains($e->getMessage(), 'already exists')) {
                // Table exists, proceed safely
                return;
            }
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
