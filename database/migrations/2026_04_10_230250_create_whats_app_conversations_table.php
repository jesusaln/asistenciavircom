<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whats_app_conversations')) {
            Schema::create('whats_app_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->string('wa_id');
                $table->string('contact_name')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
                $table->string('status')->default('open'); // open, closed, archived
                $table->json('tags')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                $table->unique(['empresa_id', 'wa_id']);
                $table->index(['empresa_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_conversations');
    }
};
