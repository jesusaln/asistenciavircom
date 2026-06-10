<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TraspasoBancario;
use App\Models\CuentaBancaria;
use App\Models\Bancos\BancoCuenta;
use App\Models\Bancos\BancoMovimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BancosMigrateTraspasos extends Command
{
    protected $signature = 'bancos:migrate-traspasos';
    protected $description = 'Migra todo el historial de traspasos bancarios clásicos hacia la nueva tabla de bancos_movimientos';

    public function handle()
    {
        $this->info("Iniciando migración de traspasos bancarios históricos al nuevo módulo de Bancos...");

        $traspasos = TraspasoBancario::withTrashed()->get();
        $this->info("Se encontraron {$traspasos->count()} traspasos en el historial clásico.");

        $migrados = 0;
        $omitidos = 0;
        $sinCuentas = 0;

        foreach ($traspasos as $traspaso) {
            $origenLegacy = CuentaBancaria::find($traspaso->cuenta_origen_id);
            $destinoLegacy = CuentaBancaria::find($traspaso->cuenta_destino_id);

            if (!$origenLegacy || !$destinoLegacy) {
                $sinCuentas++;
                continue;
            }

            // Buscar las cuentas correspondientes en el nuevo módulo
            $origenNueva = BancoCuenta::where('empresa_id', $traspaso->empresa_id)
                ->where(function($q) use ($origenLegacy) {
                    $q->where('numero_cuenta', $origenLegacy->numero_cuenta)
                      ->orWhere('alias', $origenLegacy->nombre);
                })
                ->first();

            $destinoNueva = BancoCuenta::where('empresa_id', $traspaso->empresa_id)
                ->where(function($q) use ($destinoLegacy) {
                    $q->where('numero_cuenta', $destinoLegacy->numero_cuenta)
                      ->orWhere('alias', $destinoLegacy->nombre);
                })
                ->first();

            if (!$origenNueva || !$destinoNueva) {
                $sinCuentas++;
                continue;
            }

            $userId = $traspaso->user_id ?? 1;
            if (!User::where('id', $userId)->exists()) {
                $userId = User::first()->id ?? 1;
            }

            $monto = (float) $traspaso->monto;

            // 1. Verificar o crear retiro en origen
            $retiroExistente = BancoMovimiento::where('cuenta_bancaria_id', $origenNueva->id)
                ->where('fecha', $traspaso->fecha->format('Y-m-d'))
                ->where('monto', $monto)
                ->where('tipo', 'egreso')
                ->where('concepto', 'like', '%Traspaso%')
                ->first();

            if (!$retiroExistente) {
                BancoMovimiento::create([
                    'cuenta_bancaria_id' => $origenNueva->id,
                    'fecha' => $traspaso->fecha,
                    'tipo' => 'egreso',
                    'forma_pago_sat' => '03',
                    'monto' => $monto,
                    'concepto' => "Traspaso a {$destinoNueva->nombre_banco} - " . ($destinoNueva->alias ?: $destinoNueva->numero_cuenta) . ($traspaso->referencia ? " // Ref: {$traspaso->referencia}" : ""),
                    'referencia' => $traspaso->referencia,
                    'conciliable_type' => TraspasoBancario::class,
                    'conciliable_id' => $traspaso->id,
                    'estado_conciliacion' => 'conciliado',
                    'created_by' => $userId,
                ]);
                $migrados++;
            } else {
                if (!$retiroExistente->conciliable_type) {
                    $retiroExistente->update([
                        'conciliable_type' => TraspasoBancario::class,
                        'conciliable_id' => $traspaso->id,
                    ]);
                }
                $omitidos++;
            }

            // 2. Verificar o crear depósito en destino
            $depositoExistente = BancoMovimiento::where('cuenta_bancaria_id', $destinoNueva->id)
                ->where('fecha', $traspaso->fecha->format('Y-m-d'))
                ->where('monto', $monto)
                ->where('tipo', 'ingreso')
                ->where('concepto', 'like', '%Traspaso%')
                ->first();

            if (!$depositoExistente) {
                BancoMovimiento::create([
                    'cuenta_bancaria_id' => $destinoNueva->id,
                    'fecha' => $traspaso->fecha,
                    'tipo' => 'ingreso',
                    'forma_pago_sat' => '03',
                    'monto' => $monto,
                    'concepto' => "Traspaso recibido de {$origenNueva->nombre_banco} - " . ($origenNueva->alias ?: $origenNueva->numero_cuenta),
                    'referencia' => $traspaso->referencia,
                    'conciliable_type' => TraspasoBancario::class,
                    'conciliable_id' => $traspaso->id,
                    'estado_conciliacion' => 'conciliado',
                    'created_by' => $userId,
                ]);
                $migrados++;
            } else {
                if (!$depositoExistente->conciliable_type) {
                    $depositoExistente->update([
                        'conciliable_type' => TraspasoBancario::class,
                        'conciliable_id' => $traspaso->id,
                    ]);
                }
                $omitidos++;
            }
        }

        $this->info("================ RESULTADOS ================");
        $this->info("Movimientos de traspaso creados: {$migrados}");
        $this->info("Movimientos ya existentes (enlazados): {$omitidos}");
        $this->info("Traspasos sin cuentas mapeables: {$sinCuentas}");
        $this->info("============================================");
    }
}
