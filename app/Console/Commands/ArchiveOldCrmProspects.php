<?php

namespace App\Console\Commands;

use App\Models\CrmProspecto;
use Illuminate\Console\Command;

class ArchiveOldCrmProspects extends Command
{
    protected $signature = 'crm:archive-old-prospects {--months=3 : Meses a conservar antes de archivar}';

    protected $description = 'Archiva prospectos CRM cerrados con antiguedad mayor al limite configurado.';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $cutoffDate = now()->subMonths($months);

        $query = CrmProspecto::query()
            ->whereNull('deleted_at')
            ->whereIn('etapa', ['cerrado_ganado', 'cerrado_perdido'])
            ->whereNotNull('cerrado_at')
            ->where('cerrado_at', '<=', $cutoffDate);

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info("No hay prospectos cerrados para archivar antes de {$cutoffDate->toDateString()}.");
            return self::SUCCESS;
        }

        $query->chunkById(100, function ($prospectos) {
            foreach ($prospectos as $prospecto) {
                $prospecto->delete();
            }
        });

        $this->info("Se archivaron {$total} prospectos cerrados con mas de {$months} meses.");

        return self::SUCCESS;
    }
}
