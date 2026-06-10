<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CuentaBancaria;
use App\Models\Bancos\BancoCuenta;

class SincronizarSaldosBancariosOficiales extends Command
{
    protected $signature = 'bancos:sincronizar-saldos-oficiales';
    protected $description = 'Sincroniza los saldos oficiales de las cuentas bancarias según auditoría contable';

    public function handle()
    {
        $this->info("Sincronizando saldos oficiales de auditoría en BancoCuenta y CuentaBancaria...");

        $saldos = [
            '303' => 0.00,        // Electropartes
            '7504' => 37204.00,   // Bancomer empresarial
            '1010101' => 65686.77,// Banco Home
            '20202' => 28654.94,  // Banco Opata
            '2006' => 0.00,       // American Express Platinum
        ];

        foreach ($saldos as $num => $saldo) {
            BancoCuenta::where('numero_cuenta', $num)->update(['saldo_actual' => $saldo]);
            CuentaBancaria::where('numero_cuenta', $num)->update(['saldo_actual' => $saldo]);
            $this->info("Cuenta {$num} ajustada a \${$saldo}");
        }

        // Banamex (sin numero_cuenta)
        BancoCuenta::where('alias', 'Banamex')->update(['saldo_actual' => 222858.82]);
        CuentaBancaria::where('nombre', 'Banamex')->update(['saldo_actual' => 222858.82]);
        $this->info("Cuenta Banamex ajustada a \$222,858.82");

        $this->info("Saldos oficiales alineados con éxito en ambas tablas.");
        return 0;
    }
}
