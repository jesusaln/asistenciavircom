<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EntregaDinero;
use App\Models\CuentaBancaria;
use App\Models\Bancos\BancoCuenta;
use App\Models\Bancos\BancoMovimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BancosMigrateEntregasDinero extends Command
{
    protected $signature = 'bancos:migrate-entregas-dinero';
    protected $description = 'Migra todo el historial de entregas de dinero recibidas hacia el nuevo módulo de Bancos (bancos_movimientos) unificando en Banco Home';

    public function handle()
    {
        $this->info("Iniciando migración del historial de Entregas de Dinero recibidas hacia Bancos...");

        // Unificar cualquier "Caja General (Home)" previa con "Banco Home"
        $cajasGenerales = BancoCuenta::where('alias', 'Caja General (Home)')->get();
        foreach ($cajasGenerales as $caja) {
            $home = BancoCuenta::where('empresa_id', $caja->empresa_id)
                ->where(function($q) {
                    $q->where('alias', 'Banco Home')->orWhere('numero_cuenta', '1010101');
                })->first();

            if ($home && $home->id !== $caja->id) {
                BancoMovimiento::where('cuenta_bancaria_id', $caja->id)->update(['cuenta_bancaria_id' => $home->id]);
                $home->increment('saldo_actual', $caja->saldo_actual);
                $caja->delete();
                $this->info("Se unificó la cuenta Caja General con Banco Home (ID {$home->id}).");
            } elseif (!$home) {
                $caja->update([
                    'nombre_banco' => 'Otro',
                    'alias' => 'Banco Home',
                    'numero_cuenta' => '1010101',
                ]);
                $this->info("Se renombró Caja General a Banco Home.");
            }
        }

        $entregas = EntregaDinero::where('estado', 'recibido')->orderBy('fecha_recibido', 'asc')->get();
        $this->info("Se encontraron {$entregas->count()} entregas recibidas en el historial.");

        $migrados = 0;
        $existentes = 0;

        $usuariosValidos = User::pluck('id')->toArray();
        $defaultUserId = !empty($usuariosValidos) && in_array(1, $usuariosValidos) ? 1 : ($usuariosValidos[0] ?? 1);

        foreach ($entregas as $entrega) {
            $empresaId = $entrega->empresa_id ?? 1;
            $bancoCuenta = null;

            if ($entrega->cuenta_bancaria_id) {
                $legacyCuenta = CuentaBancaria::find($entrega->cuenta_bancaria_id);
                if ($legacyCuenta) {
                    $bancoCuenta = $legacyCuenta->getBancoCuentaCorrespondiente();
                }
            }

            // Si no tiene cuenta_bancaria_id o no se pudo mapear, buscar o crear la cuenta "Banco Home"
            if (!$bancoCuenta) {
                $bancoCuenta = BancoCuenta::where('empresa_id', $empresaId)
                    ->where(function ($q) {
                        $q->where('alias', 'Banco Home')
                          ->orWhere('numero_cuenta', '1010101');
                    })
                    ->first();

                if (!$bancoCuenta) {
                    $bancoCuenta = BancoCuenta::create([
                        'empresa_id' => $empresaId,
                        'nombre_banco' => 'Otro',
                        'alias' => 'Banco Home',
                        'numero_cuenta' => '1010101',
                        'moneda' => 'MXN',
                        'saldo_inicial' => 0,
                        'saldo_actual' => 0,
                        'es_fiscal' => false,
                        'tipo' => 'caja_chica',
                    ]);
                }
            }

            $userId = $entrega->recibido_por ?? $entrega->user_id;
            if (!$userId || !in_array($userId, $usuariosValidos)) {
                $userId = $defaultUserId;
            }

            $monto = (float) $entrega->total;
            if ($monto <= 0) {
                continue;
            }

            $fecha = $entrega->fecha_recibido ? ($entrega->fecha_recibido instanceof \Carbon\Carbon ? $entrega->fecha_recibido->format('Y-m-d') : substr($entrega->fecha_recibido, 0, 10)) : ($entrega->fecha_entrega ? ($entrega->fecha_entrega instanceof \Carbon\Carbon ? $entrega->fecha_entrega->format('Y-m-d') : substr($entrega->fecha_entrega, 0, 10)) : $entrega->created_at->format('Y-m-d'));

            // Verificar si ya existe este movimiento bancario
            $movExistente = BancoMovimiento::where('cuenta_bancaria_id', $bancoCuenta->id)
                ->where('conciliable_type', EntregaDinero::class)
                ->where('conciliable_id', $entrega->id)
                ->first();

            if (!$movExistente) {
                // También buscar por fecha y monto exacto para evitar duplicados si no tenían el morph
                $movSimilar = BancoMovimiento::where('cuenta_bancaria_id', $bancoCuenta->id)
                    ->where('fecha', $fecha)
                    ->where('monto', $monto)
                    ->where('concepto', 'like', "%Entrega #{$entrega->id}%")
                    ->first();

                if ($movSimilar) {
                    $movSimilar->update([
                        'conciliable_type' => EntregaDinero::class,
                        'conciliable_id' => $entrega->id,
                    ]);
                    $existentes++;
                } else {
                    $formaPago = ((float)$entrega->monto_efectivo > 0) ? '01' : (((float)$entrega->monto_transferencia > 0) ? '03' : (((float)$entrega->monto_cheques > 0) ? '02' : '99'));

                    BancoMovimiento::create([
                        'cuenta_bancaria_id' => $bancoCuenta->id,
                        'fecha' => $fecha,
                        'tipo' => 'ingreso',
                        'forma_pago_sat' => $formaPago,
                        'monto' => $monto,
                        'concepto' => "Entrega de dinero #{$entrega->id} recibida - " . ($entrega->notas ?? 'Cobranza/Venta general'),
                        'referencia' => "ENTREGA-{$entrega->id}",
                        'conciliable_type' => EntregaDinero::class,
                        'conciliable_id' => $entrega->id,
                        'estado_conciliacion' => 'conciliado',
                        'created_by' => $userId,
                    ]);

                    // Actualizar el saldo actual de la cuenta bancaria
                    $bancoCuenta->increment('saldo_actual', $monto);
                    $migrados++;
                }
            } else {
                $existentes++;
            }
        }

        $this->info("================ RESULTADOS ================");
        $this->info("Movimientos de entregas en Banco Home: {$migrados}");
        $this->info("Movimientos ya existentes o enlazados: {$existentes}");
        $this->info("============================================");

        return 0;
    }
}
