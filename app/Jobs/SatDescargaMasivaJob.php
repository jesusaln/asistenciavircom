<?php

namespace App\Jobs;

use App\Models\SatDescargaMasiva;
use App\Services\SatDescargaMasivaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SatDescargaMasivaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    private int $descargaId;
    private string $mode;

    private const VERIFY_BACKOFF = [120, 300, 600, 900, 1200];

    public function __construct(int $descargaId, string $mode = 'request')
    {
        $this->descargaId = $descargaId;
        $this->mode = $mode;
    }

    public function handle(SatDescargaMasivaService $service): void
    {
        $descarga = SatDescargaMasiva::find($this->descargaId);
        if (!$descarga) {
            return;
        }

        // SET TENANT CONTEXT manually because we don't pass empresa_id to constructor
        if ($descarga->empresa_id) {
            \App\Support\EmpresaResolver::setContext($descarga->empresa_id);
        }

        if ($this->mode === 'recheck') {
            $descarga->update([
                'status' => 'revalidando',
                'last_checked_at' => now(),
            ]);

            $result = $service->revalidarEstatusSat($descarga);
            if (!$result['success']) {
                $descarga->update([
                    'status' => 'error',
                    'last_error' => $result['message'] ?? 'Error al revalidar estatus SAT.',
                    'finished_at' => now(),
                ]);
                return;
            }

            $descarga->update([
                'status' => 'completado',
                'errors' => array_merge($descarga->errors ?? [], [
                    'revalidacion' => $result['stats'] ?? [],
                ]),
                'last_checked_at' => now(),
            ]);
            return;
        }

        if ($this->mode === 'request') {
            $descarga->update([
                'status' => 'solicitando',
                'started_at' => now(),
            ]);

            $result = $service->procesarSolicitud($descarga);
            if (!$result['success']) {
                if (!empty($result['is_limit'])) {
                    // Detectar tipo de límite
                    $message = $result['message'] ?? '';
                    $limiteTipo = str_contains($message, 'por vida')
                        ? SatDescargaMasiva::LIMITE_POR_VIDA
                        : SatDescargaMasiva::LIMITE_PENDIENTES;

                    $retryCount = ($descarga->retry_count ?? 0) + 1;
                    $maxRetries = $descarga->max_retries ?? 3;

                    // Verificar si puede reintentar
                    if ($retryCount >= $maxRetries) {
                        // PAUSAR - No más reintentos automáticos
                        $descarga->update([
                            'status' => 'pausado',
                            'retry_count' => $retryCount,
                            'limite_tipo' => $limiteTipo,
                            'mensaje_usuario' => $limiteTipo === SatDescargaMasiva::LIMITE_POR_VIDA
                                ? '🚫 Límite del SAT alcanzado. Espera 24-48 horas y usa "Reintentar Manual".'
                                : '⏳ Después de 3 intentos, el SAT sigue bloqueado. Intenta mañana.',
                            'last_error' => $message,
                        ]);
                        Log::warning('SAT Descarga: Límite alcanzado, pausando después de máx reintentos', [
                            'descarga_id' => $this->descargaId,
                            'retry_count' => $retryCount,
                            'limite_tipo' => $limiteTipo,
                        ]);
                        return; // NO reintentar más
                    }

                    // Calcular próximo reintento CONSERVADOR (4h, 8h, 24h)
                    $horasEspera = match ($retryCount) {
                        1 => 4,   // Primer reintento: 4 horas
                        2 => 8,   // Segundo reintento: 8 horas
                        default => 24, // Tercer reintento: 24 horas
                    };
                    $nextRetry = now()->addHours($horasEspera);

                    $descarga->update([
                        'status' => 'esperando',
                        'retry_count' => $retryCount,
                        'next_retry_at' => $nextRetry,
                        'limite_tipo' => $limiteTipo,
                        'mensaje_usuario' => "⏳ Reintento #{$retryCount} programado para " . $nextRetry->format('d/m H:i') . " (en {$horasEspera}h)",
                        'last_error' => $message,
                    ]);

                    Log::info('SAT Descarga: Límite detectado, reintento programado', [
                        'descarga_id' => $this->descargaId,
                        'retry_count' => $retryCount,
                        'next_retry_at' => $nextRetry,
                        'horas_espera' => $horasEspera,
                    ]);

                    // Programar reintento con delay largo
                    $this->release($horasEspera * 3600);
                    return;
                }

                $descarga->update([
                    'status' => 'error',
                    'last_error' => $result['message'],
                    'finished_at' => now(),
                ]);
                return;
            }

            // Éxito - reiniciar contadores
            $descarga->update([
                'status' => 'pendiente',
                'request_id' => $result['request_id'],
                'last_checked_at' => now(),
                'retry_count' => 0,
                'limite_tipo' => null,
                'mensaje_usuario' => null,
            ]);

            // Auto-programar verificación después de 30 segundos
            self::dispatch($this->descargaId, 'verify')->delay(now()->addSeconds(30));

            return;
        }

        if (empty($descarga->request_id)) {
            $descarga->update([
                'status' => 'error',
                'last_error' => 'La solicitud no tiene request_id.',
                'finished_at' => now(),
            ]);
            return;
        }

        $descarga->update([
            'status' => 'verificando',
            'last_checked_at' => now(),
        ]);

        $result = $service->verificarYDescargar($descarga);
        if (!$result['success']) {
            $descarga->update([
                'status' => 'error',
                'last_error' => $result['message'],
                'finished_at' => now(),
            ]);
            return;
        }

        if (!empty($result['pending'])) {
            $descarga->update([
                'status' => 'pendiente',
                'last_checked_at' => now(),
            ]);
            $this->release($this->nextBackoff());
            return;
        }

        $stats = $result['stats'] ?? [];
        $errorsPayload = [
            'errors' => $stats['errors_list'] ?? [],
            'duplicates' => $stats['duplicates_list'] ?? [],
        ];

        $descarga->update([
            'status' => 'completado',
            'paquetes' => $result['packages'] ?? null,
            'total_cfdis' => $stats['total'] ?? 0,
            'inserted_cfdis' => $stats['inserted'] ?? ($stats['staged'] ?? 0),
            'duplicate_cfdis' => $stats['duplicates'] ?? 0,
            'error_cfdis' => $stats['errors'] ?? 0,
            'errors' => ($errorsPayload['errors'] || $errorsPayload['duplicates']) ? $errorsPayload : null,
            'finished_at' => now(),
        ]);
    }

    private function nextBackoff(): int
    {
        $attempt = max(1, $this->attempts());
        $index = min($attempt - 1, count(self::VERIFY_BACKOFF) - 1);

        return self::VERIFY_BACKOFF[$index];
    }
}
