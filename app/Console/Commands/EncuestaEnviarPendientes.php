<?php

namespace App\Console\Commands;

use App\Jobs\EnviarEncuestaSatisfaccionJob;
use App\Models\EncuestaSatisfaccion;
use App\Support\EmpresaResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EncuestaEnviarPendientes extends Command
{
    protected $signature = 'encuesta:enviar-pendientes
                            {--empresa_id= : Filtrar por empresa específica}
                            {--limit=100 : Máximo de encuestas a procesar}
                            {--force : Reenviar aunque intentos_envio >= INTENTOS_MAX_ENVIO}';

    protected $description = 'Envía encuestas pendientes cuya fecha programada ya pasó. Útil como respaldo del job con delay.';

    public function handle(): int
    {
        $empresaId = $this->option('empresa_id');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $query = EncuestaSatisfaccion::where('estado', EncuestaSatisfaccion::ESTADO_PENDIENTE)
            ->whereNotNull('programada_para');

        if (! $force) {
            $query->where('programada_para', '<=', now());
            $maxIntentos = \App\Services\EncuestaSatisfaccionService::INTENTOS_MAX_ENVIO;
            $query->where('intentos_envio', '<', $maxIntentos);
        }

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $encuestas = $query->orderBy('programada_para')->limit($limit)->get();

        if ($encuestas->isEmpty()) {
            $this->info('No hay encuestas pendientes para enviar.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$encuestas->count()} encuestas pendientes...");

        $enviadas = 0;
        $errores = 0;

        foreach ($encuestas as $encuesta) {
            EmpresaResolver::setContext($encuesta->empresa_id);

            try {
                EnviarEncuestaSatisfaccionJob::dispatch($encuesta->id);
                $enviadas++;
                $this->line("  ✓ Encuesta {$encuesta->id} ({$encuesta->folio}) encolada");
            } catch (\Throwable $e) {
                $errores++;
                Log::error('encuesta:enviar-pendientes: error encolando', [
                    'encuesta_id' => $encuesta->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Encuesta {$encuesta->id}: {$e->getMessage()}");
            } finally {
                EmpresaResolver::clearContext();
            }
        }

        $this->info("Encoladas: {$enviadas}, Errores: {$errores}");
        return self::SUCCESS;
    }
}