<?php

namespace App\Console\Commands;

use App\Models\CuentasPorCobrar;
use App\Models\Venta;
use Illuminate\Console\Command;

class GenerarCuentasPorCobrar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generar-cuentas-por-cobrar {--force : Forzar recreación de cuentas existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera cuentas por cobrar para ventas no pagadas que no tienen cuenta asociada';

    /**
     * Execute the console command.
     * ✅ MEDIUM PRIORITY FIX #7: Validaciones completas y transacciones
     */
    public function handle()
    {
        $force = $this->option('force');

        $this->info('Buscando ventas sin cuentas por cobrar...');

        $ventasSinCuenta = Venta::whereDoesntHave('cuentaPorCobrar')
            ->where('pagado', false)
            ->whereIn('estado', ['borrador', 'pendiente', 'aprobada'])
            ->get();

        if ($ventasSinCuenta->isEmpty()) {
            $this->info('No hay ventas sin cuentas por cobrar.');
            return;
        }

        $this->info("Encontradas {$ventasSinCuenta->count()} ventas sin cuentas por cobrar.");

        if (!$force) {
            if (!$this->confirm('¿Deseas continuar con la creación de cuentas por cobrar?')) {
                return;
            }
        }

        $creadas = 0;
        $errores = 0;
        $omitidas = 0;

        foreach ($ventasSinCuenta as $venta) {
            try {
                // ✅ FIX #7: Validación 1 - Total debe ser mayor a cero
                if (!$venta->total || $venta->total <= 0) {
                    $this->warn("Venta {$venta->numero_venta} omitida: total inválido ($venta->total)");
                    $omitidas++;
                    continue;
                }

                // ✅ FIX #7: Validación 2 - Fecha debe existir
                if (!$venta->fecha) {
                    $this->warn("Venta {$venta->numero_venta} omitida: sin fecha");
                    $omitidas++;
                    continue;
                }

                // ✅ FIX #7: Usar transacción para cada cuenta
                \DB::transaction(function () use ($venta) {
                    CuentasPorCobrar::create([
                        'venta_id' => $venta->id,
                        'monto_total' => $venta->total,
                        'monto_pagado' => 0,
                        'monto_pendiente' => $venta->total,
                        // ✅ FIX #7: Clonar fecha para no modificar original
                        'fecha_vencimiento' => $venta->fecha->copy()->addDays(30),
                        'estado' => 'pendiente',
                        'notas' => 'Cuenta por cobrar generada por comando',
                    ]);
                });

                $creadas++;
                $this->line("✓ Creada cuenta para venta {$venta->numero_venta}");
            } catch (\Exception $e) {
                $errores++;
                $this->error("✗ Error creando cuenta para venta {$venta->numero_venta}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Proceso completado:");
        $this->info("  Creadas: {$creadas}");
        $this->warn("  Omitidas: {$omitidas}");
        $this->error("  Errores: {$errores}");
    }
}
