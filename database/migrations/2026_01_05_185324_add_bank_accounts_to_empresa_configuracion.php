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
        if (!Schema::hasTable('empresa_configuracion')) {
            return;
        }

        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_configuracion', 'cuenta_id_paypal')) {
                $table->unsignedBigInteger('cuenta_id_paypal')->nullable()->after('paypal_sandbox');
            }
            if (!Schema::hasColumn('empresa_configuracion', 'cuenta_id_mercadopago')) {
                $table->unsignedBigInteger('cuenta_id_mercadopago')->nullable()->after('mercadopago_sandbox');
            }
            if (!Schema::hasColumn('empresa_configuracion', 'cuenta_id_stripe')) {
                $table->unsignedBigInteger('cuenta_id_stripe')->nullable()->after('stripe_sandbox');
            }
        });

        if (Schema::hasTable('cuentas_bancarias')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $hasPaypal = \Illuminate\Support\Facades\DB::select("SELECT 1 FROM pg_constraint WHERE conname = 'empresa_configuracion_cuenta_id_paypal_foreign'");
                if (empty($hasPaypal)) {
                    $table->foreign('cuenta_id_paypal')->references('id')->on('cuentas_bancarias')->nullOnDelete();
                }
                $hasMp = \Illuminate\Support\Facades\DB::select("SELECT 1 FROM pg_constraint WHERE conname = 'empresa_configuracion_cuenta_id_mercadopago_foreign'");
                if (empty($hasMp)) {
                    $table->foreign('cuenta_id_mercadopago')->references('id')->on('cuentas_bancarias')->nullOnDelete();
                }
                $hasStripe = \Illuminate\Support\Facades\DB::select("SELECT 1 FROM pg_constraint WHERE conname = 'empresa_configuracion_cuenta_id_stripe_foreign'");
                if (empty($hasStripe)) {
                    $table->foreign('cuenta_id_stripe')->references('id')->on('cuentas_bancarias')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('empresa_configuracion')) {
            return;
        }

        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (Schema::hasTable('cuentas_bancarias')) {
                try {
                    $table->dropForeign(['cuenta_id_paypal']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropForeign(['cuenta_id_mercadopago']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropForeign(['cuenta_id_stripe']);
                } catch (\Throwable $e) {
                }
            }

            $dropColumns = [];
            if (Schema::hasColumn('empresa_configuracion', 'cuenta_id_paypal')) {
                $dropColumns[] = 'cuenta_id_paypal';
            }
            if (Schema::hasColumn('empresa_configuracion', 'cuenta_id_mercadopago')) {
                $dropColumns[] = 'cuenta_id_mercadopago';
            }
            if (Schema::hasColumn('empresa_configuracion', 'cuenta_id_stripe')) {
                $dropColumns[] = 'cuenta_id_stripe';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
