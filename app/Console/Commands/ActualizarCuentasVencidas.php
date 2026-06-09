<?php

namespace App\Console\Commands;

use App\Models\CuentasPorPagar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

class ActualizarCuentasVencidas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuentas:actualizar-vencidas {--dry-run : Solo mostrar qué se actualizaría sin hacer cambios} {--confirm= : Confirmación explícita}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estado de las cuentas por pagar vencidas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Buscando cuentas por pagar vencidas...');

        // Buscar cuentas que deberían estar vencidas
        $cuentasAVencer = CuentasPorPagar::whereIn('estado', ['pendiente', 'parcial'])
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now()->startOfDay())
            ->get();

        if ($cuentasAVencer->isEmpty()) {
            $this->info('No hay cuentas por vencer.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$cuentasAVencer->count()} cuentas vencidas.");

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Modo dry-run: No se realizarán cambios.');
            $this->table(
                ['ID', 'Compra ID', 'Monto Pendiente', 'Fecha Vencimiento', 'Estado Actual'],
                $cuentasAVencer->map(fn($c) => [
                    $c->id,
                    $c->compra_id,
                    '$' . number_format($c->monto_pendiente, 2),
                    $c->fecha_vencimiento->format('Y-m-d'),
                    $c->estado,
                ])
            );
            return self::SUCCESS;
        }

        $confirm = (string) $this->option('confirm');
        if ($confirm !== 'ACTUALIZAR-VENCIDAS') {
            $this->warn('Para aplicar cambios usa --confirm=ACTUALIZAR-VENCIDAS (o --dry-run).');
            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $actualizadas = 0;
            foreach ($cuentasAVencer as $cuenta) {
                $cuenta->update(['estado' => 'vencido']);
                $actualizadas++;

                $this->line("  - Cuenta #{$cuenta->id} marcada como vencida (Monto: \${$cuenta->monto_pendiente})");
            }

            Log::info('Cuentas por pagar actualizadas a vencidas', [
                'cantidad' => $actualizadas,
                'cuentas_ids' => $cuentasAVencer->pluck('id')->toArray(),
                'fecha' => now()->toDateTimeString(),
            ]);

            DB::commit();
            $this->info("✅ {$actualizadas} cuentas actualizadas a estado 'vencido'.");
            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error actualizando cuentas: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
