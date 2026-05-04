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
        Schema::create('credential_rotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->string('field_name'); // e.g. 'cva_password', 'stripe_secret_key'
            $table->string('provider')->nullable(); // e.g. 'CVA', 'Stripe', 'Google'
            $table->timestamp('rotated_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->json('metadata')->nullable(); // Para guardar info adicional no sensible
            $table->timestamps();

            $table->index(['field_name', 'rotated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_rotations');
    }
};
