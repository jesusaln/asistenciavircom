<?php

namespace App\Console\Commands;

use App\Models\Compra;
use App\Models\CuentaBancaria;
use Illuminate\Console\Command;

class RevertirMovimientoCompraCancelada extends Command
{
    protected $signature = 'compra:revertir-movimiento {compra_id}';
    protected $description = 'Devuelve el dinero a la cuenta bancaria de una compra que fue cancelada pero no se revirtió el movimiento';

    public function handle()
    {
        $compraId = $this->argument('compra_id');
        
        $compra = Compra::withTrashed()->with('cuentaBancaria')->find($compraId);
        
        if (!$compra) {
            $this->error("No se encontró la compra con ID: {$compraId}");
            return 1;
        }

        $this->info("Compra encontrada:");
        $this->info("  - Número: {$compra->numero_compra}");
        $this->info("  - Estado: {$compra->estado}");
        $this->info("  - Total: $" . number_format($compra->total, 2));
        $this->info("  - Cuenta Bancaria ID: " . ($compra->cuenta_bancaria_id ?? 'Sin asignar'));

        if ($compra->estado !== 'cancelada') {
            $this->warn("La compra no está cancelada. Estado actual: {$compra->estado}");
            if (!$this->confirm('¿Deseas continuar de todos modos?')) {
                return 0;
            }
        }

        if (!$compra->cuenta_bancaria_id) {
            $this->error("La compra no tiene una cuenta bancaria asociada. No hay movimiento que revertir.");
            return 1;
        }

        $cuentaBancaria = CuentaBancaria::find($compra->cuenta_bancaria_id);
        
        if (!$cuentaBancaria) {
            $this->error("No se encontró la cuenta bancaria con ID: {$compra->cuenta_bancaria_id}");
            return 1;
        }

        $this->info("\nCuenta Bancaria:");
        $this->info("  - Banco: {$cuentaBancaria->banco}");
        $this->info("  - Número: {$cuentaBancaria->numero_cuenta}");
        $this->info("  - Saldo Actual: $" . number_format($cuentaBancaria->saldo_actual, 2));

        $nuevoSaldo = $cuentaBancaria->saldo_actual + $compra->total;
        $this->info("\nSe creará un DEPÓSITO (devolución) por: $" . number_format($compra->total, 2));
        $this->info("Nuevo saldo será: $" . number_format($nuevoSaldo, 2));

        if (!$this->confirm('¿Confirmas la devolución del dinero?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        try {
            // Registrar el depósito (devolución)
            $movimiento = $cuentaBancaria->registrarMovimiento(
                'deposito',
                $compra->total,
                "Devolución por cancelación de compra #{$compra->numero_compra}",
                'devolucion'
            );

            $this->info("\n✅ Movimiento registrado exitosamente!");
            $this->info("  - ID Movimiento: {$movimiento->id}");
            $this->info("  - Nuevo saldo de cuenta: $" . number_format($cuentaBancaria->saldo_actual, 2));

            return 0;
        } catch (\Exception $e) {
            $this->error("Error al registrar el movimiento: " . $e->getMessage());
            return 1;
        }
    }
}
