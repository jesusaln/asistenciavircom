<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('phone_number_id')->unique();
            $table->string('display_phone')->nullable();
            $table->string('access_token')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Migrar el número existente de la empresa a la nueva tabla
        DB::table('empresas')->whereNotNull('whatsapp_phone_number_id')->orderBy('id')->each(function ($empresa) {
            DB::table('empresa_whatsapp_numbers')->insert([
                'empresa_id' => $empresa->id,
                'phone_number_id' => $empresa->whatsapp_phone_number_id,
                'display_phone' => $empresa->whatsapp_sender_phone ?? $empresa->whatsapp_phone_number_id,
                'access_token' => $empresa->whatsapp_access_token,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_whatsapp_numbers');
    }
};
