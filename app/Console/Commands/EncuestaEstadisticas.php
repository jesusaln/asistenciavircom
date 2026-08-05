<?php

namespace App\Console\Commands;

use App\Models\EncuestaSatisfaccion;
use Illuminate\Console\Command;

class EncuestaEstadisticas extends Command
{
    protected $signature = 'encuesta:estadisticas
                            {--empresa_id= : Filtrar por empresa}
                            {--dias=30 : Días hacia atrás}';

    protected $description = 'Muestra estadísticas de encuestas de satisfacción: tasa de respuesta, NPS, calificación promedio.';

    public function handle(): int
    {
        $empresaId = $this->option('empresa_id');
        $dias = (int) $this->option('dias');

        $query = EncuestaSatisfaccion::query()
            ->where('created_at', '>=', now()->subDays($dias));

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $todas = $query->count();
        $enviadas = $query->whereNotNull('enviada_at')->count();
        $completadas = $query->where('estado', EncuestaSatisfaccion::ESTADO_COMPLETADA)->count();
        $canceladas = $query->where('estado', EncuestaSatisfaccion::ESTADO_CANCELADA)->count();
        $expiradas = $query->where('estado', EncuestaSatisfaccion::ESTADO_EXPIRADA)->count();

        $promCalificacion = $query->whereNotNull('calificacion_global')->avg('calificacion_global');
        $promNps = $query->whereNotNull('nps_score')->avg('nps_score');

        $this->info("📊 Estadísticas de encuestas (últimos {$dias} días)");
        $this->line('');
        $this->line("  Creadas:        {$todas}");
        $this->line("  Enviadas:       {$enviadas}");
        $this->line("  Completadas:    {$completadas}");
        $this->line("  Canceladas:     {$canceladas}");
        $this->line("  Expiradas:      {$expiradas}");

        if ($enviadas > 0) {
            $tasa = round(($completadas / $enviadas) * 100, 1);
            $this->line("  Tasa respuesta: {$tasa}%");
        }

        if ($promCalificacion) {
            $this->line("  Calificación:   ".round($promCalificacion, 2).'/5');
        }
        if ($promNps) {
            $this->line("  NPS promedio:   ".round($promNps, 1).'/10');
        }

        $this->line('');
        $this->info('Últimas 10 encuestas completadas:');

        $ultimas = EncuestaSatisfaccion::query()
            ->where('created_at', '>=', now()->subDays($dias))
            ->where('estado', EncuestaSatisfaccion::ESTADO_COMPLETADA)
            ->orderByDesc('completada_at')
            ->limit(10)
            ->get(['folio', 'calificacion_global', 'nps_score', 'codigo_promocional', 'codigo_usado', 'completada_at']);

        if ($empresaId) {
            $ultimas = $ultimas->where('empresa_id', $empresaId);
        }

        foreach ($ultimas as $e) {
            $usado = $e->codigo_usado ? '✓' : '○';
            $this->line("  {$e->folio} | calif: {$e->calificacion_global}/5 | nps: {$e->nps_score}/10 | código {$usado} | {$e->completada_at->format('d/m/Y H:i')}");
        }

        return self::SUCCESS;
    }
}