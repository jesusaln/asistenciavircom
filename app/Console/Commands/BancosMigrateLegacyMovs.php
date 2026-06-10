<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MovimientoBancario;
use App\Models\Bancos\BancoCuenta;
use App\Models\Bancos\BancoMovimiento;
use App\Models\User;

class BancosMigrateLegacyMovs extends Command
{
    protected $signature = 'bancos:migrate-legacy-movs';
    protected $description = 'Migra todos los movimientos históricos del módulo clásico a la nueva estructura de bancos';

    public function handle()
    {
        $this->info('Iniciando migración de movimientos bancarios históricos...');

        $movimientos = MovimientoBancario::orderBy('fecha', 'asc')->get();
        $this->info("Se encontraron {$movimientos->count()} movimientos en el historial clásico.");

        $migrados = 0;
        $existentes = 0;
        $sinCuenta = 0;

        $usuariosValidos = User::pluck('id')->toArray();
        $defaultUserId = !empty($usuariosValidos) && in_array(1, $usuariosValidos) ? 1 : ($usuariosValidos[0] ?? null);

        foreach ($movimientos as $mov) {
            $bancoCuenta = null;
            if ($mov->cuentaBancaria) {
                $bancoCuenta = $mov->cuentaBancaria->getBancoCuentaCorrespondiente();
            } else {
                $bancoCuenta = BancoCuenta::where('empresa_id', $mov->empresa_id ?? 1)
                    ->where(function($q) use ($mov) {
                        if ($mov->cuenta_bancaria) {
                            $q->where('numero_cuenta', $mov->cuenta_bancaria)
                              ->orWhere('alias', $mov->cuenta_bancaria);
                        }
                    })->first();
            }

            if (!$bancoCuenta) {
                $bancoNombre = $mov->banco ?: 'Otro';
                $bancoCuenta = BancoCuenta::where('empresa_id', $mov->empresa_id ?? 1)
                    ->where('nombre_banco', $bancoNombre)
                    ->first();

                if (!$bancoCuenta) {
                    $bancoCuenta = BancoCuenta::where('empresa_id', $mov->empresa_id ?? 1)->first();
                }
            }

            if (!$bancoCuenta) {
                $sinCuenta++;
                continue;
            }

            $montoAbs = abs($mov->monto);
            $tipoBanco = $mov->tipo === 'deposito' ? 'ingreso' : 'egreso';

            $userId = $mov->usuario_id;
            if (!$userId || !in_array($userId, $usuariosValidos)) {
                $userId = $defaultUserId;
            }

            // Verificar si ya existe en la nueva tabla
            $exists = BancoMovimiento::where('cuenta_bancaria_id', $bancoCuenta->id)
                ->where('fecha', $mov->fecha->format('Y-m-d'))
                ->where('monto', $montoAbs)
                ->where('concepto', $mov->concepto)
                ->exists();

            if ($exists) {
                $existentes++;
            } else {
                BancoMovimiento::create([
                    'cuenta_bancaria_id' => $bancoCuenta->id,
                    'fecha' => $mov->fecha,
                    'tipo' => $tipoBanco,
                    'forma_pago_sat' => '03',
                    'monto' => $montoAbs,
                    'concepto' => $mov->concepto ?: 'Movimiento histórico',
                    'referencia' => $mov->referencia,
                    'estado_conciliacion' => $mov->estado === 'conciliado' ? 'conciliado' : 'pendiente',
                    'created_by' => $userId,
                ]);
                $migrados++;
            }
        }

        $this->info("================ RESULTADOS ================");
        $this->info("Migrados exitosamente: {$migrados}");
        $this->info("Ya existentes (omitidos): {$existentes}");
        $this->info("Sin cuenta bancaria mapeable: {$sinCuenta}");
        $this->info("============================================");

        return 0;
    }
}
