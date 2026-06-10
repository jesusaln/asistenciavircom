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
        Schema::create('nom035_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('folio')->unique();
            $table->string('type'); // violencia, condiciones, acoso, etc.
            $table->text('description');
            $table->date('incident_date')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('status')->default('pending'); // pending, in_review, resolved, dismissed
            $table->text('admin_notes')->nullable();
            $table->text('resolution_details')->nullable();
            $table->json('evidence_paths')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nom035_complaints');
    }
};
