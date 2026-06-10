<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nom035_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->string('type'); // capacitación, medida_control, evento_bienestar, difusion
            $table->string('title');
            $table->text('description');
            $table->date('activity_date');
            $table->string('participants_count')->nullable();
            $table->string('evidence_file')->nullable();
            $table->string('status')->default('completed'); // planned, completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nom035_activities');
    }
};
