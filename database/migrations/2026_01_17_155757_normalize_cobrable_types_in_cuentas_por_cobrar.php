<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Normalizar cobrable_type para usar alias del MorphMap en lugar de FQCN.
     */
    public function up(): void
    {
        // Actualizar App\Models\Venta -> venta
        DB::table('cuentas_por_cobrar')
            ->where('cobrable_type', 'App\Models\Venta')
            ->update(['cobrable_type' => 'venta']);

        // Actualizar App\Models\Renta -> renta
        DB::table('cuentas_por_cobrar')
            ->where('cobrable_type', 'App\Models\Renta')
            ->update(['cobrable_type' => 'renta']);

        // Actualizar App\Models\PolizaServicio -> poliza_servicio
        DB::table('cuentas_por_cobrar')
            ->where('cobrable_type', 'App\Models\PolizaServicio')
            ->update(['cobrable_type' => 'poliza_servicio']);

        // También en otras tablas que usen polimorfismo
        // cuentas_por_pagar
        DB::table('cuentas_por_pagar')
            ->where('cobrable_type', 'App\Models\Compra')
            ->update(['cobrable_type' => 'compra']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir si es necesario
        DB::table('cuentas_por_cobrar')
            ->where('cobrable_type', 'venta')
            ->update(['cobrable_type' => 'App\Models\Venta']);

        DB::table('cuentas_por_cobrar')
            ->where('cobrable_type', 'renta')
            ->update(['cobrable_type' => 'App\Models\Renta']);

        DB::table('cuentas_por_cobrar')
            ->where('cobrable_type', 'poliza_servicio')
            ->update(['cobrable_type' => 'App\Models\PolizaServicio']);
    }
};
