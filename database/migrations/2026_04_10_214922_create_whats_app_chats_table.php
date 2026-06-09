<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whats_app_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('wa_id')->nullable(); 
            $table->string('from_name')->nullable();
            $table->text('body')->nullable();
            $table->string('type')->default('text'); 
            $table->string('direction')->default('inbound'); 
            $table->string('message_id')->unique();
            $table->string('status')->nullable(); 
            $table->json('metadata')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['empresa_id', 'wa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_chats');
    }
};
