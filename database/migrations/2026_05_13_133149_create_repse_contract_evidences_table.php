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
        Schema::create('repse_contract_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repse_contract_id')->constrained('repse_contracts')->onDelete('cascade');
            $table->string('file_path');
            $table->string('description')->nullable();
            $table->date('evidence_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repse_contract_evidences');
    }
};
