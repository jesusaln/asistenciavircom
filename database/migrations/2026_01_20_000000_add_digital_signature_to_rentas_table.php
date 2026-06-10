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
        if (!Schema::hasTable('rentas')) {
            return;
        }

        Schema::table('rentas', function (Blueprint $table) {
            if (!Schema::hasColumn('rentas', 'firma_digital')) {
                $table->text('firma_digital')->nullable()->after('referencia_pago');
            }
            if (!Schema::hasColumn('rentas', 'firmado_at')) {
                $table->dateTime('firmado_at')->nullable()->after('firma_digital');
            }
            if (!Schema::hasColumn('rentas', 'firmado_ip')) {
                $table->string('firmado_ip')->nullable()->after('firmado_at');
            }
            if (!Schema::hasColumn('rentas', 'firmado_nombre')) {
                $table->string('firmado_nombre')->nullable()->after('firmado_ip');
            }
            if (!Schema::hasColumn('rentas', 'firma_hash')) {
                $table->string('firma_hash')->nullable()->after('firmado_nombre');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('rentas')) {
            return;
        }

        Schema::table('rentas', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('rentas', 'firma_digital')) {
                $dropColumns[] = 'firma_digital';
            }
            if (Schema::hasColumn('rentas', 'firmado_at')) {
                $dropColumns[] = 'firmado_at';
            }
            if (Schema::hasColumn('rentas', 'firmado_ip')) {
                $dropColumns[] = 'firmado_ip';
            }
            if (Schema::hasColumn('rentas', 'firmado_nombre')) {
                $dropColumns[] = 'firmado_nombre';
            }
            if (Schema::hasColumn('rentas', 'firma_hash')) {
                $dropColumns[] = 'firma_hash';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
