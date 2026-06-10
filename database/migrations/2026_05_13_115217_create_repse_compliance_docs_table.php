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
        Schema::create('repse_compliance_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('type'); // sat_opinion, imss_opinion, infonavit_opinion, sua, idse, payroll
            $table->integer('month');
            $table->integer('year');
            $table->string('file_path');
            $table->string('status')->default('pending'); // pending, validated, rejected
            $table->text('observations')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repse_compliance_docs');
    }
};
