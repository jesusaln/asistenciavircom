<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bancos\BancoCuenta;
use App\Models\Contab\CuentaContable;
use Illuminate\Support\Facades\DB;

class BancosSyncCuentasContables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bancos:sync-cuentas-contables {--empresa= : ID de la empresa a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-empareja cuentas bancarias históricas en bancos_cuentas con el catálogo contable (102.xx)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaIdParam = $this->option('empresa');

        $query = BancoCuenta::whereNull('cuenta_contable_id')->where('es_fiscal', true);
        if ($empresaIdParam) {
            $query->where('empresa_id', $empresaIdParam);
        }

        $cuentasPendientes = $query->get();

        if ($cuentasPendientes->isEmpty()) {
            $this->info("No se encontraron cuentas bancarias fiscales pendientes de mapeo.");
            return 0;
        }

        $this->info("Iniciando auto-mapeo para {$cuentasPendientes->count()} cuentas bancarias...");

        $actualizadas = 0;

        foreach ($cuentasPendientes as $bancoCuenta) {
            $empresaId = $bancoCuenta->empresa_id ?? 1;
            
            // Buscar una cuenta de detalle de Bancos (102) que coincida por nombre, alias o número de cuenta
            $cuentaContable = CuentaContable::where('empresa_id', $empresaId)
                ->where('codigo', 'like', '102%')
                ->where('es_detalle', true)
                ->where(function ($q) use ($bancoCuenta) {
                    if ($bancoCuenta->numero_cuenta) {
                        $q->where('nombre', 'ilike', '%' . substr($bancoCuenta->numero_cuenta, -4) . '%');
                    }
                    if ($bancoCuenta->alias) {
                        $q->orWhere('nombre', 'ilike', '%' . $bancoCuenta->alias . '%');
                    }
                    $q->orWhere('nombre', 'ilike', '%' . $bancoCuenta->nombre_banco . '%');
                })
                ->first();

            // Si no encuentra por coincidencia de nombre/número, toma la primera disponible de detalle
            if (!$cuentaContable) {
                $cuentaContable = CuentaContable::where('empresa_id', $empresaId)
                    ->where('codigo', 'like', '102%')
                    ->where('es_detalle', true)
                    ->first();
            }

            if ($cuentaContable) {
                $bancoCuenta->update(['cuenta_contable_id' => $cuentaContable->id]);
                $this->line("[OK] Cuenta bancaria '{$bancoCuenta->nombre_banco}' (#{$bancoCuenta->numero_cuenta}) ligada a {$cuentaContable->codigo} - {$cuentaContable->nombre}");
                $actualizadas++;
            } else {
                $this->warn("[!] No se encontró cuenta contable 102.xx disponible para '{$bancoCuenta->nombre_banco}'");
            }
        }

        $this->info("Proceso completado. {$actualizadas} cuentas bancarias vinculadas exitosamente.");
        return 0;
    }
}
