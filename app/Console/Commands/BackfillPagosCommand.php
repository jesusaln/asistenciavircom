<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Venta;
use App\Models\Cobranza;

class BackfillPagosCommand extends Command
{
    protected $signature = 'pagos:backfill {--dry-run : No guarda cambios, solo muestra conteos}';

    protected $description = 'Corrige pagos antiguos: fecha_pago con hora, metodo_pago, cobrador y notas en ventas/cobranzas';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->info('Iniciando backfill de pagos ' . ($dry ? '(dry-run)' : ''));

        $res = [
            'ventas' => ['total' => 0, 'actualizadas' => 0],
            'cobranzas' => ['total' => 0, 'actualizadas' => 0],
        ];

        // Ventas pagadas con fecha/hora faltante o vacía
        $ventas = Venta::where('pagado', true)->get();
        $res['ventas']['total'] = $ventas->count();

        DB::beginTransaction();
        try {
            foreach ($ventas as $venta) {
                $dirty = false;

                // fecha_pago: si es null o está a medianoche, usar updated_at (o created_at)
                if (!$venta->fecha_pago) {
                    $venta->fecha_pago = $venta->updated_at ?? $venta->created_at ?? now();
                    $dirty = true;
                } else {
                    $hora = $venta->fecha_pago->format('H:i:s');
                    if ($hora === '00:00:00') {
                        // Reemplazar solo la hora conservando la fecha
                        $venta->fecha_pago = $venta->fecha_pago->setTime(
                            (int) optional($venta->updated_at)->format('H') ?? 12,
                            (int) optional($venta->updated_at)->format('i') ?? 0,
                            (int) optional($venta->updated_at)->format('s') ?? 0
                        );
                        $dirty = true;
                    }
                }

                // metodo_pago por defecto
                if (empty($venta->metodo_pago)) {
                    $venta->metodo_pago = 'otros';
                    $dirty = true;
                }

                // notas_pago por defecto
                if ($venta->notas_pago === null) {
                    $venta->notas_pago = '';
                    $dirty = true;
                }

                if ($dirty && !$dry) {
                    $venta->save();
                    $res['ventas']['actualizadas']++;
                } elseif ($dirty) {
                    $res['ventas']['actualizadas']++;
                }
            }

            // Cobranzas pagadas/parciales
            $cobranzas = Cobranza::whereIn('estado', ['pagado', 'parcial'])->get();
            $res['cobranzas']['total'] = $cobranzas->count();

            foreach ($cobranzas as $c) {
                $dirty = false;

                if (!$c->fecha_pago) {
                    $c->fecha_pago = $c->updated_at ?? $c->created_at ?? now();
                    $dirty = true;
                } else {
                    $hora = $c->fecha_pago->format('H:i:s');
                    if ($hora === '00:00:00') {
                        $c->fecha_pago = $c->fecha_pago->setTime(
                            (int) optional($c->updated_at)->format('H') ?? 12,
                            (int) optional($c->updated_at)->format('i') ?? 0,
                            (int) optional($c->updated_at)->format('s') ?? 0
                        );
                        $dirty = true;
                    }
                }

                if (empty($c->metodo_pago)) {
                    $c->metodo_pago = 'otros';
                    $dirty = true;
                }

                // Si no hay responsable_cobro, lo dejamos tal cual (no hay trazabilidad histórica fiable)

                if ($dirty && !$dry) {
                    $c->save();
                    $res['cobranzas']['actualizadas']++;
                } elseif ($dirty) {
                    $res['cobranzas']['actualizadas']++;
                }
            }

            if ($dry) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->line("Ventas: {$res['ventas']['actualizadas']} / {$res['ventas']['total']} actualizadas");
        $this->line("Cobranzas: {$res['cobranzas']['actualizadas']} / {$res['cobranzas']['total']} actualizadas");

        $this->info('Backfill completado ' . ($dry ? '(dry-run)' : '(aplicado)'));
        return self::SUCCESS;
    }
}

