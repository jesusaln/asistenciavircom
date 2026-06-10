<?php

namespace App\Console\Commands;

use App\Models\Cfdi;
use App\Models\Contab\PolizaContable;
use App\Services\Contab\ContabilidadService;
use App\Services\Cfdi\CfdiFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ContabBulkSyncCfdis extends Command
{
    protected $signature = 'contab:bulk-sync-cfdis {--limit=500 : Maximum number of CFDIs to sync} {--dry-run : Only show what would be synced}';
    protected $description = 'Sincroniza masivamente CFDIs faltantes en contabilidad';

    protected $contabilidadService;
    protected $fileService;

    public function __construct(ContabilidadService $contabilidadService, CfdiFileService $fileService)
    {
        parent::__construct();
        $this->contabilidadService = $contabilidadService;
        $this->fileService = $fileService;
    }

    public function handle()
    {
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info("Buscando CFDIs no contabilizados...");

        // Usamos la misma lógica que el controlador para encontrar los faltantes
        $subQuery = 'EXISTS (SELECT 1 FROM contab_polizas WHERE CAST(contab_polizas.cfdi_uuid AS TEXT) = cfdis.uuid OR contab_polizas.xml_content LIKE \'%\' || cfdis.uuid || \'%\')';
        
        $cfdis = Cfdi::whereRaw('NOT ' . $subQuery)
            ->where('estatus', 'vigente')
            ->whereYear('fecha_emision', date('Y'))
            ->orderBy('fecha_emision', 'asc')
            ->take($limit)
            ->get();

        $total = $cfdis->count();
        $this->info("Se encontraron {$total} CFDIs por sincronizar.");

        if ($total === 0) {
            $this->info("Nada que sincronizar.");
            if (!$dryRun) {
                $report = [
                    'resumen' => [
                        'total' => 0,
                        'exitos' => 0,
                        'errores' => 0,
                        'fecha' => now()->toDateString()
                    ],
                    'exitosas' => [],
                    'errores' => []
                ];
                \Illuminate\Support\Facades\Cache::put('daily_contab_summary', $report, now()->addHours(24));
            }
            return 0;
        }

        $success = 0;
        $errors = 0;
        $skipped = 0;
        $report = ['exitosas' => [], 'errores' => []];

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($cfdis as $cfdi) {
            try {
                if ($dryRun) {
                    $this->line("\n[Dry Run] Sincronizaría: {$cfdi->uuid} - {$cfdi->serie}{$cfdi->folio} ({$cfdi->tipo_comprobante})");
                    $success++;
                    $bar->advance();
                    continue;
                }

                // Obtener contenido XML
                $xmlContent = $this->fileService->getXmlContent($cfdi);
                if (!$xmlContent) {
                    $this->error("\nNo se encontró el XML para el CFDI {$cfdi->uuid}");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // Generar póliza
                // Resolver empresa_id: del CFDI → del EmpresaResolver → empresa con config FIEL
                $empresaId = $cfdi->empresa_id ?: \App\Support\EmpresaResolver::resolveId();
                if (!$empresaId) {
                    $empresaId = \App\Models\EmpresaConfiguracion::whereNotNull('fiel_cer_path')
                        ->whereNotNull('fiel_key_path')
                        ->value('id');
                }
                if (!$empresaId) {
                    $this->error("\nNo se pudo resolver empresa_id para {$cfdi->uuid}. Omitiendo.");
                    $errors++;
                    $bar->advance();
                    continue;
                }
                $userId = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'super-admin')->orWhere('name', 'admin'))->first()?->id ?? \App\Models\User::first()?->id; 
                if (!$userId) {
                    $this->error("\nNo se encontró usuario admin para {$cfdi->uuid}. Omitiendo.");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $poliza = $this->contabilidadService->generarPolizaDesdeXml(
                    $xmlContent,
                    $empresaId,
                    $userId
                );

                if ($poliza) {
                    $success++;
                    $poliza->load(['asientos.cuenta']);
                    $report['exitosas'][] = [
                        'uuid' => $cfdi->uuid,
                        'folio' => $cfdi->folio ?? $cfdi->uuid,
                        'poliza_numero' => $poliza->numero,
                        'concepto' => $poliza->concepto,
                        'total' => $cfdi->total,
                        'asientos' => $poliza->asientos->map(fn($a) => [
                            'cuenta' => $a->cuenta ? trim($a->cuenta->codigo . ' ' . $a->cuenta->nombre) : 'Cuenta Desconocida',
                            'debe' => $a->debe,
                            'haber' => $a->haber
                        ])->toArray()
                    ];
                } else {
                    $errors++;
                    $report['errores'][] = [
                        'uuid' => $cfdi->uuid,
                        'folio' => $cfdi->folio ?? $cfdi->uuid,
                        'error' => 'No se generó la póliza por un error interno (retornó null).'
                    ];
                }
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23505) { // Unique violation (Duplicado)
                    $skipped++;
                    // Vincular el UUID en alguna póliza existente del mismo día para que ya no vuelva a aparecer como pendiente
                    try {
                    \Illuminate\Support\Facades\DB::statement("
                        UPDATE contab_polizas 
                        SET xml_content = xml_content || '\n<!-- Auto-vinculado UUID: ' || ? || ' -->' 
                        WHERE empresa_id = ? AND fecha = ? AND total = ?
                    ", [$cfdi->uuid, $empresaId, substr($cfdi->fecha_emision, 0, 10), $cfdi->total]);
                    } catch (\Throwable $linkErr) {
                        // Silently ignore linking errors
                    }
                } else {
                    $this->error("\nError de BD al sincronizar {$cfdi->uuid}: " . $e->getMessage());
                    Log::error("Bulk Sync DB Error: " . $e->getMessage(), ['uuid' => $cfdi->uuid]);
                    $errors++;
                    $report['errores'][] = [
                        'uuid' => $cfdi->uuid,
                        'folio' => $cfdi->folio ?? $cfdi->uuid,
                        'error' => 'Error de Base de Datos: ' . $e->getMessage()
                    ];
                }
            } catch (\Exception $e) {
                $this->error("\nError al sincronizar {$cfdi->uuid}: " . $e->getMessage());
                Log::error("Bulk Sync Error: " . $e->getMessage(), ['uuid' => $cfdi->uuid]);
                $errors++;
                $report['errores'][] = [
                    'uuid' => $cfdi->uuid,
                    'folio' => $cfdi->folio ?? $cfdi->uuid,
                    'error' => $e->getMessage()
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Proceso completado.");
        $this->info("Exitosos: {$success}");
        $this->info("Errores: {$errors}");
        $this->info("Omitidos: {$skipped}");

        if (!$dryRun && ($success > 0 || $errors > 0)) {
            $report['resumen'] = [
                'total' => $total,
                'exitos' => $success,
                'errores' => $errors,
                'fecha' => now()->toDateString()
            ];
            \Illuminate\Support\Facades\Cache::put('daily_contab_summary', $report, now()->addHours(24));
        }

        return 0;
    }
}
