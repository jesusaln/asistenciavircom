<?php

namespace App\Console\Commands;

use App\Models\Venta;
use App\Models\CuentasPorCobrar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SincronizarEstadoVentas extends Command
{
    protected $signature = 'app:sincronizar-estado-ventas 
                            {--fix : Aplicar correcciones (sin esto solo muestra reporte)}
                            {--fix-folios : Generar números de venta faltantes}
                            {--fix-pagos : Sincronizar estado de pago con CuentasPorCobrar}
                            {--fix-cxc : Sincronizar estado de CxC con ventas pagadas}';

    protected $description = 'Sincroniza el estado de ventas: genera folios faltantes, sincroniza pagos y CxC';

    public function handle()
    {
        $fix = $this->option('fix');
        $fixFolios = $this->option('fix-folios') || $fix;
        $fixPagos = $this->option('fix-pagos') || $fix;
        $fixCxC = $this->option('fix-cxc') || $fix;

        $this->info('=== Análisis de Ventas ===');
        $this->newLine();

        // 1. Ventas sin número de venta
        $ventasSinFolio = Venta::whereNull('numero_venta')
            ->orWhere('numero_venta', '')
            ->get();

        $this->warn("Ventas sin folio (numero_venta): {$ventasSinFolio->count()}");
        
        if ($ventasSinFolio->count() > 0) {
            $this->table(
                ['ID', 'Cliente', 'Total', 'Fecha', 'Pagado'],
                $ventasSinFolio->map(fn($v) => [
                    $v->id,
                    $v->cliente?->nombre_razon_social ?? 'Sin cliente',
                    '$' . number_format($v->total, 2),
                    $v->created_at?->format('d/m/Y'),
                    $v->pagado ? 'Sí' : 'No'
                ])->toArray()
            );

            if ($fixFolios) {
                $this->info('Generando folios...');
                $this->generarFoliosFaltantes($ventasSinFolio);
            }
        }

        $this->newLine();

        // 2. Ventas sin cliente
        $ventasSinCliente = Venta::whereNull('cliente_id')->get();
        $this->warn("Ventas sin cliente: {$ventasSinCliente->count()}");
        
        if ($ventasSinCliente->count() > 0) {
            $this->table(
                ['ID', 'Numero Venta', 'Total', 'Fecha'],
                $ventasSinCliente->map(fn($v) => [
                    $v->id,
                    $v->numero_venta ?? 'Sin folio',
                    '$' . number_format($v->total, 2),
                    $v->created_at?->format('d/m/Y'),
                ])->toArray()
            );
            $this->comment('→ Estas ventas necesitan asignarse a un cliente manualmente o eliminarlas si son de prueba.');
        }

        $this->newLine();

        // 3. Ventas con estado de pago desincronizado
        $this->info('Analizando sincronización de pagos...');
        
        $ventasDesincronizadas = collect();
        
        Venta::with('cuentaPorCobrar')->chunk(100, function ($ventas) use (&$ventasDesincronizadas) {
            foreach ($ventas as $venta) {
                $cxc = $venta->cuentaPorCobrar;
                
                if (!$cxc) continue;
                
                // Caso 1: CxC pagada pero venta marcada como no pagada
                $cxcPagada = $cxc->estado === 'pagado' || $cxc->monto_pendiente <= 0;
                $ventaPagada = $venta->pagado;
                
                if ($cxcPagada && !$ventaPagada) {
                    $ventasDesincronizadas->push([
                        'venta' => $venta,
                        'cxc' => $cxc,
                        'problema' => 'CxC pagada pero venta marcada como pendiente'
                    ]);
                }
                
                // Caso 2: Venta pagada pero CxC pendiente (menos común, pero posible)
                if ($ventaPagada && !$cxcPagada && $cxc->monto_pendiente > 0) {
                    $ventasDesincronizadas->push([
                        'venta' => $venta,
                        'cxc' => $cxc,
                        'problema' => 'Venta marcada como pagada pero CxC tiene saldo pendiente'
                    ]);
                }
            }
        });

        $this->warn("Ventas con estado desincronizado: {$ventasDesincronizadas->count()}");

        if ($ventasDesincronizadas->count() > 0) {
            $this->table(
                ['ID', 'Folio', 'Total', 'Venta Pagada', 'CxC Estado', 'CxC Pendiente', 'Problema'],
                $ventasDesincronizadas->map(fn($item) => [
                    $item['venta']->id,
                    $item['venta']->numero_venta ?? '-',
                    '$' . number_format($item['venta']->total, 2),
                    $item['venta']->pagado ? 'Sí' : 'No',
                    $item['cxc']->estado,
                    '$' . number_format($item['cxc']->monto_pendiente, 2),
                    $item['problema']
                ])->toArray()
            );

            if ($fixPagos) {
                $this->info('Sincronizando estados de pago...');
                $this->sincronizarPagos($ventasDesincronizadas);
            }
        }

        $this->newLine();

        // 4. Sincronizar CxC con ventas pagadas
        $this->reportarCxCHuerfanas();
        
        if ($fixCxC) {
            $this->sincronizarCxCConVentas();
        }

        $this->newLine();
        
        if (!$fix && !$fixFolios && !$fixPagos && !$fixCxC) {
            $this->comment('Ejecuta con --fix para aplicar todas las correcciones');
            $this->comment('O usa --fix-folios, --fix-pagos, o --fix-cxc para correcciones específicas');
        }

        $this->info('=== Análisis completado ===');
        return Command::SUCCESS;
    }

    private function generarFoliosFaltantes($ventas)
    {
        $count = 0;
        
        foreach ($ventas as $venta) {
            try {
                DB::beginTransaction();
                
                // Obtener el siguiente número disponible
                if (DB::getDriverName() === 'pgsql') {
                    $max = DB::table('ventas')
                        ->selectRaw("COALESCE(MAX(NULLIF(regexp_replace(numero_venta, '\\D', '', 'g'), '')::int), 0) as max_num")
                        ->value('max_num');
                } else {
                    $ultimo = Venta::whereNotNull('numero_venta')
                        ->where('numero_venta', '!=', '')
                        ->orderByDesc('id')
                        ->value('numero_venta');
                    $max = 0;
                    if ($ultimo && preg_match('/(\d+)$/', $ultimo, $m)) {
                        $max = (int) $m[1];
                    }
                }

                $siguiente = $max + 1;
                $numeroVenta = 'V' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);

                $venta->update(['numero_venta' => $numeroVenta]);
                
                DB::commit();
                $this->line("  ✓ Venta #{$venta->id} → {$numeroVenta}");
                $count++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  ✗ Error en venta #{$venta->id}: {$e->getMessage()}");
            }
        }

        $this->info("Folios generados: {$count}");
    }

    private function sincronizarPagos($ventasDesincronizadas)
    {
        $count = 0;

        foreach ($ventasDesincronizadas as $item) {
            $venta = $item['venta'];
            $cxc = $item['cxc'];

            try {
                DB::beginTransaction();

                // Si CxC está pagada, marcar venta como pagada
                if ($cxc->estado === 'pagado' || $cxc->monto_pendiente <= 0) {
                    $venta->update([
                        'pagado' => true,
                        'fecha_pago' => $cxc->updated_at ?? now()
                    ]);
                    $this->line("  ✓ Venta #{$venta->id} ({$venta->numero_venta}) → Marcada como PAGADA");
                }
                // Si venta está pagada pero CxC no, actualizar CxC
                elseif ($venta->pagado && $cxc->monto_pendiente > 0) {
                    // Esto es más complejo - necesitaría registrar el pago en CxC
                    // Por seguridad, solo reportamos
                    $this->warn("  ! Venta #{$venta->id} tiene discrepancia - revisar manualmente");
                    DB::rollBack();
                    continue;
                }

                DB::commit();
                $count++;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  ✗ Error en venta #{$venta->id}: {$e->getMessage()}");
            }
        }

        $this->info("Ventas sincronizadas: {$count}");
    }

    /**
     * Sincroniza CxC estado basado en venta.pagado
     */
    private function sincronizarCxCConVentas()
    {
        $this->info('Sincronizando estado de CxC con ventas...');
        $count = 0;

        // Ventas pagadas cuya CxC está pendiente
        $ventasPagadas = Venta::where('pagado', true)
            ->whereHas('cuentaPorCobrar', function($q) {
                $q->where('estado', '!=', 'pagado');
            })
            ->with('cuentaPorCobrar')
            ->get();

        foreach ($ventasPagadas as $venta) {
            $cxc = $venta->cuentaPorCobrar;
            if ($cxc) {
                try {
                    $cxc->update([
                        'estado' => 'pagado',
                        'monto_pagado' => $cxc->monto_total,
                        'monto_pendiente' => 0
                    ]);
                    $this->line("  ✓ CxC #{$cxc->id} (Venta {$venta->numero_venta}) → Estado: PAGADO");
                    $count++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Error CxC #{$cxc->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("CxC sincronizadas: {$count}");
    }

    /**
     * Encuentra y reporta CxC huérfanas (sin venta asociada)
     */
    private function reportarCxCHuerfanas()
    {
        $huerfanas = CuentasPorCobrar::where('cobrable_type', Venta::class)
            ->whereDoesntHave('cobrable')
            ->get();

        if ($huerfanas->count() > 0) {
            $this->warn("\nCxC huérfanas (sin venta asociada): {$huerfanas->count()}");
            $this->table(
                ['CxC ID', 'Cobrable ID', 'Monto', 'Estado'],
                $huerfanas->map(fn($cxc) => [
                    $cxc->id,
                    $cxc->cobrable_id,
                    '$' . number_format($cxc->monto_total, 2),
                    $cxc->estado
                ])->toArray()
            );
            $this->comment('→ Estas CxC apuntan a ventas que ya no existen o fueron eliminadas.');
        }
    }
}
