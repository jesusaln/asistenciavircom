<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\Clientes\ClienteUnificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UnificarClientesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientes:unificar
                            {--master= : ID del cliente maestro}
                            {--merge= : ID o IDs de los clientes duplicados (separados por coma)}
                            {--auto : Buscar y unificar duplicados exactos por nombre automáticamente}
                            {--empresa=all : ID de la empresa para la búsqueda automática (o "all" para todas, "0" para globales sin empresa)}
                            {--force : No pedir confirmación en modo manual}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unifica clientes duplicados transfiriendo todas sus relaciones (ventas, cotizaciones, citas, etc.) al cliente maestro';

    /**
     * Execute the console command.
     */
    public function handle(ClienteUnificationService $unificationService)
    {
        $masterId = $this->option('master');
        $mergeIds = $this->option('merge');
        $auto = $this->option('auto');
        $empresaId = $this->option('empresa');
        $force = $this->option('force');

        if (!$auto && (!$masterId || !$mergeIds)) {
            $this->error('Debe proporcionar --master y --merge en modo manual, o utilizar la opción --auto.');
            $this->info('Ejemplo manual: php artisan clientes:unificar --master=150 --merge=151,152');
            $this->info('Ejemplo automático: php artisan clientes:unificar --auto --empresa=1');
            return 1;
        }

        if ($auto) {
            return $this->handleAutoUnification($unificationService, (string) $empresaId, (bool) $force);
        }

        return $this->handleManualUnification($unificationService, (int) $masterId, $mergeIds, $force);
    }

    private function handleManualUnification(ClienteUnificationService $unificationService, int $masterId, string $mergeIds, bool $force)
    {
        $duplicateIds = array_filter(array_map('intval', explode(',', $mergeIds)));

        $this->info("🔍 Buscando cliente maestro ID: {$masterId}...");
        $masterCliente = Cliente::withoutGlobalScopes()->find($masterId);

        if (!$masterCliente) {
            $this->error("El cliente maestro ID {$masterId} no existe.");
            return 1;
        }

        $this->info("✨ Cliente Maestro encontrado: {$masterCliente->nombre_razon_social} (RFC: {$masterCliente->rfc}, Email: {$masterCliente->email})");

        $duplicates = Cliente::withoutGlobalScopes()->whereIn('id', $duplicateIds)->get();

        if ($duplicates->isEmpty()) {
            $this->error("Ninguno de los clientes duplicados proporcionados fue encontrado.");
            return 1;
        }

        $this->warn("\n⚠️  Los siguientes clientes serán fusionados hacia el ID {$masterId} y posteriormente eliminados:");
        foreach ($duplicates as $dup) {
            $this->line(" - ID {$dup->id}: {$dup->nombre_razon_social} (Creado: {$dup->created_at->format('Y-m-d H:i')})");
        }

        if (!$force && !$this->confirm("\n¿Desea proceder con la unificación de estos {$duplicates->count()} clientes?")) {
            $this->info("Operación cancelada por el usuario.");
            return 0;
        }

        $this->info("\n🚀 Ejecutando unificación...");
        $totalMerged = 0;

        foreach ($duplicates as $dup) {
            $this->line("Unificando ID {$dup->id} -> ID {$masterId}...");
            try {
                $summary = $unificationService->unify($masterCliente, $dup);
                $this->info("✅ ID {$dup->id} unificado exitosamente.");
                foreach ($summary['tablas_actualizadas'] as $tabla => $count) {
                    $this->line("   - {$count} registros actualizados en '{$tabla}'");
                }
                $totalMerged++;
            } catch (\Exception $e) {
                $this->error("❌ Error al unificar ID {$dup->id}: " . $e->getMessage());
            }
        }

        $this->info("\n🎉 Proceso completado. {$totalMerged} clientes unificados hacia el maestro ID {$masterId}.");
        return 0;
    }

    private function handleAutoUnification(ClienteUnificationService $unificationService, string $empresaId, bool $force)
    {
        $this->info("🔍 Buscando grupos de clientes duplicados (" . ($empresaId === 'all' ? 'en todas las empresas y globales' : ($empresaId === '0' ? 'globales sin empresa' : "empresa ID {$empresaId}")) . ")...");

        $query = DB::table('clientes')
            ->whereNull('deleted_at');

        if ($empresaId !== 'all') {
            if ($empresaId === 'null' || $empresaId === '0') {
                $query->whereNull('empresa_id');
            } else {
                $query->where('empresa_id', (int) $empresaId);
            }
        }

        // Agrupar por empresa (o 0 si es null) y por nombre normalizado
        $gruposDuplicados = $query
            ->selectRaw('COALESCE(empresa_id, 0) as emp_id, unaccent(lower(trim(nombre_razon_social))) as nombre_limpio, COUNT(*) as total, array_agg(id ORDER BY id ASC) as ids')
            ->groupByRaw('COALESCE(empresa_id, 0), unaccent(lower(trim(nombre_razon_social)))')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($gruposDuplicados->isEmpty()) {
            $this->info("✅ No se detectaron clientes duplicados con el mismo nombre exacto en la empresa ID {$empresaId}.");
            return 0;
        }

        $this->warn("🚨 Se detectaron {$gruposDuplicados->count()} grupos de clientes con nombres duplicados:");
        foreach ($gruposDuplicados as $grupo) {
            $idsStr = trim($grupo->ids, '{}');
            $this->line(" - '{$grupo->nombre_limpio}' ({$grupo->total} registros: IDs [{$idsStr}])");
        }

        if (!$force && !$this->confirm("\n¿Desea unificar automáticamente estos grupos (el ID más antiguo de cada grupo será el maestro)?")) {
            $this->info("Operación automática cancelada.");
            return 0;
        }

        $this->info("\n🚀 Iniciando unificación automática en lote...");
        $gruposExitosos = 0;
        $totalClientesFusionados = 0;

        foreach ($gruposDuplicados as $grupo) {
            $idsArray = array_filter(array_map('intval', explode(',', trim($grupo->ids, '{}'))));
            // El ID más pequeño (más antiguo) será el maestro
            $masterId = (int) array_shift($idsArray);
            $masterCliente = Cliente::withoutGlobalScopes()->find($masterId);

            if (!$masterCliente) {
                continue;
            }

            $this->warn("\n👉 Grupo: {$masterCliente->nombre_razon_social} (Maestro ID: {$masterId})");

            foreach ($idsArray as $dupId) {
                $dupCliente = Cliente::withoutGlobalScopes()->find($dupId);
                if (!$dupCliente) {
                    continue;
                }

                $this->line("   Fusionando duplicado ID {$dupId}...");
                try {
                    $summary = $unificationService->unify($masterCliente, $dupCliente);
                    $this->info("   ✅ Duplicado ID {$dupId} fusionado y eliminado.");
                    $totalClientesFusionados++;
                } catch (\Exception $e) {
                    $this->error("   ❌ Error en duplicado ID {$dupId}: " . $e->getMessage());
                }
            }
            $gruposExitosos++;
        }

        $this->info("\n🎉 Unificación automática completada. Se procesaron {$gruposExitosos} grupos y se eliminaron/fusionaron {$totalClientesFusionados} duplicados.");
        return 0;
    }
}
