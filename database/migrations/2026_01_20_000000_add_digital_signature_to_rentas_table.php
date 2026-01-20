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
        Schema::table('rentas', function (Blueprint $table) {
            $table->text('firma_digital')->nullable()->after('referencia_pago');
            $table->dateTime('firmado_at')->nullable()->after('firma_digital');
            $table->string('firmado_ip')->nullable()->after('firmado_at');
            $table->string('firmado_nombre')->nullable()->after('firmado_ip');
            $table->string('firma_hash')->nullable()->after('firmado_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            $table->dropColumn([
                'firma_digital',
                'firmado_at',
                'firmado_ip',
                'firmado_nombre',
                'firma_hash',
            ]);
        });
    }
};
