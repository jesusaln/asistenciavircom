<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            // Firma del cliente
            $table->text('firma_cliente')->nullable()->after('clausulas_especiales');
            $table->timestamp('firmado_at')->nullable()->after('firma_cliente');
            $table->string('firmado_ip', 45)->nullable()->after('firmado_at');
            $table->string('firma_hash', 64)->nullable()->after('firmado_ip'); // SHA-256
            $table->string('firmado_nombre')->nullable()->after('firma_hash');

            // Firma del representante de la empresa
            $table->text('firma_empresa')->nullable()->after('firmado_nombre');
            $table->timestamp('firma_empresa_at')->nullable()->after('firma_empresa');
            $table->foreignId('firma_empresa_usuario_id')->nullable()->constrained('users')->nullOnDelete()->after('firma_empresa_at');
        });
    }

    public function down(): void
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn([
                'firma_cliente',
                'firmado_at',
                'firmado_ip',
                'firma_hash',
                'firmado_nombre',
                'firma_empresa',
                'firma_empresa_at',
            ]);
            $table->dropConstrainedForeignId('firma_empresa_usuario_id');
        });
    }
};
