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
        Schema::table('clientes', function (Blueprint $blueprint) {
            $blueprint->after('dias_gracia', function (Blueprint $table) {
                $table->longText('credito_firma')->nullable();
                $table->dateTime('credito_firmado_at')->nullable();
                $table->string('credito_firmado_ip')->nullable();
                $table->string('credito_firmado_nombre')->nullable();
                $table->string('credito_firma_hash')->nullable();
                $table->decimal('credito_solicitado_monto', 15, 2)->default(0);
                $table->integer('credito_solicitado_dias')->default(0);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'credito_firma',
                'credito_firmado_at',
                'credito_firmado_ip',
                'credito_firmado_nombre',
                'credito_firma_hash',
                'credito_solicitado_monto',
                'credito_solicitado_dias',
            ]);
        });
    }
};
