<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CuentasPorCobrar;
use App\Models\Renta;
use App\Services\EmpresaConfiguracionService;

class ActualizarCobranzasRentasConIva extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rentas:agregar-iva 
                            {--dry-run : Simular sin hacer cambios}
                            {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las cobranzas pendientes de rentas para agregar el IVA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ivaRate = EmpresaConfiguracionService::getIvaPorcentaje();
        $ivaPorcentaje = $ivaRate / 100;

        $this->info("=== Actualización de Cobranzas de Rentas con IVA ({$ivaRate}%) ===");
        $this->newLine();

        // Obtener cobranzas pendientes de rentas (mensualidades)
        $cobranzas = CuentasPorCobrar::where('cobrable_type', Renta::class)
            ->where('notas', 'Mensualidad')
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->with('cobrable')
            ->get();

        if ($cobranzas->isEmpty()) {
            $this->warn('No se encontraron cobranzas pendientes de rentas para actualizar.');
            return 0;
        }

        $this->info("Se encontraron {$cobranzas->count()} cobranzas pendientes de rentas.");
        $this->newLine();

        // Mostrar resumen
        $totalAnterior = 0;
        $totalNuevo = 0;
        $cambios = [];

        foreach ($cobranzas as $cobranza) {
            $renta = $cobranza->cobrable;
            if (!$renta) {
                continue;
            }

            // El monto actual debería ser sin IVA (el monto_mensual de la renta)
            $montoSinIva = $cobranza->monto_total;
            $montoConIva = round($montoSinIva * (1 + $ivaPorcentaje), 2);

            // Verificar si ya tiene IVA (comparar con monto_mensual de la renta)
            $montoMensualRenta = (float) $renta->monto_mensual;
            $montoEsperadoConIva = round($montoMensualRenta * (1 + $ivaPorcentaje), 2);

            // Si el monto actual es igual al monto_mensual, necesita IVA
            if (abs($montoSinIva - $montoMensualRenta) < 0.01) {
                $totalAnterior += $montoSinIva;
                $totalNuevo += $montoEsperadoConIva;

                $cambios[] = [
                    'id' => $cobranza->id,
                    'renta' => $renta->numero_contrato,
                    'monto_anterior' => $montoSinIva,
                    'monto_nuevo' => $montoEsperadoConIva,
                    'monto_pendiente_anterior' => $cobranza->monto_pendiente,
                    'monto_pendiente_nuevo' => round($cobranza->monto_pendiente * (1 + $ivaPorcentaje), 2),
                    'fecha' => $cobranza->fecha_vencimiento,
                ];
            }
        }

        if (empty($cambios)) {
            $this->warn('Todas las cobranzas ya tienen el IVA aplicado o no requieren actualización.');
            return 0;
        }

        // Mostrar tabla de cambios
        $this->table(
            ['ID', 'Contrato', 'Monto Anterior', 'Monto Nuevo (+IVA)', 'Fecha Venc.'],
            collect($cambios)->map(fn($c) => [
                $c['id'],
                $c['renta'],
                '$' . number_format($c['monto_anterior'], 2),
                '$' . number_format($c['monto_nuevo'], 2),
                $c['fecha'],
            ])->toArray()
        );

        $this->newLine();
        $this->info("Total de cobranzas a actualizar: " . count($cambios));
        $this->info("Total anterior: $" . number_format($totalAnterior, 2));
        $this->info("Total nuevo (con IVA): $" . number_format($totalNuevo, 2));
        $this->info("Diferencia (IVA agregado): $" . number_format($totalNuevo - $totalAnterior, 2));
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 Modo simulación (--dry-run). No se realizaron cambios.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('¿Deseas aplicar estos cambios?')) {
            $this->warn('Operación cancelada.');
            return 0;
        }

        // Aplicar cambios
        $actualizados = 0;
        foreach ($cambios as $cambio) {
            CuentasPorCobrar::where('id', $cambio['id'])->update([
                'monto_total' => $cambio['monto_nuevo'],
                'monto_pendiente' => $cambio['monto_pendiente_nuevo'],
            ]);
            $actualizados++;
        }

        $this->newLine();
        $this->info("✅ Se actualizaron {$actualizados} cobranzas con el IVA.");

        return 0;
    }
}
