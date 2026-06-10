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
use Illuminate\Support\Str;

class SatDescargaMasivaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 25;
    public int $timeout = 180;

    private int $descargaId;
    private string $mode;
    private ?int $empresaId;

    private const VERIFY_BACKOFF = [120, 300, 600, 900, 900, 900, 1200, 1200, 1200, 1800];

    public function __construct(int $descargaId, string $mode = 'request', ?int $empresaId = null)
    {
        $this->descargaId = $descargaId;
        $this->mode = $mode;
        $this->empresaId = $empresaId;
    }

    public function handle(SatDescargaMasivaService $service): void
    {
        $descarga = SatDescargaMasiva::find($this->descargaId);
        if (!$descarga) {
            return;
        }

        // SET TENANT CONTEXT
        $resolvedEmpresaId = $this->empresaId;
        if (!$resolvedEmpresaId && $descarga->empresa_id) {
            $resolvedEmpresaId = $descarga->empresa_id;
        }
        if (!$resolvedEmpresaId && $descarga->created_by) {
            $resolvedEmpresaId = \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $descarga->created_by)
                ->value('empresa_id');
        }
        if ($resolvedEmpresaId) {
            \App\Support\EmpresaResolver::setContext($resolvedEmpresaId);
        }

        // 1. Evitar concurrencia: si hay otra descarga activa en el SAT en curso (menos de 15 minutos de actualización), posponer con jitter
        $activeExists = SatDescargaMasiva::where('id', '!=', $descarga->id)
            ->whereIn('status', ['solicitando', 'verificando', 'revalidando'])
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->exists();

        if ($activeExists) {
            Log::info("SAT Descarga: Concurrencia detectada, posponiendo descarga #{$this->descargaId}...", [
                'descarga_id' => $this->descargaId
            ]);
            $this->release(random_int(60, 180)); // Esperar 1-3 minutos con jitter
            return;
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

                if ($descarga->created_by) {
                    \App\Models\UserNotification::createForUser(
                        $descarga->created_by,
                        'sat_download_error',
                        'Error en Sincronización SAT',
                        "Hubo un problema al revalidar el estatus de las facturas: " . ($result['message'] ?? 'Error desconocido'),
                        ['descarga_id' => $descarga->id, 'error' => $result['message'] ?? ''],
                        '/cfdi',
                        'fas fa-exclamation-circle'
                    );
                }
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
                    
                    // Añadir un jitter aleatorio de 5 a 45 minutos para evitar que múltiples descargas despierten a la misma vez
                    $jitterMinutes = random_int(5, 45);
                    $nextRetry = now()->addHours($horasEspera)->addMinutes($jitterMinutes);
                    $delaySeconds = ($horasEspera * 3600) + ($jitterMinutes * 60);

                    $descarga->update([
                        'status' => 'esperando',
                        'retry_count' => $retryCount,
                        'next_retry_at' => $nextRetry,
                        'limite_tipo' => $limiteTipo,
                        'mensaje_usuario' => "⏳ Reintento #{$retryCount} programado para " . $nextRetry->format('d/m H:i') . " (en {$horasEspera}h y {$jitterMinutes}m)",
                        'last_error' => $message,
                    ]);

                    Log::info('SAT Descarga: Límite detectado, reintento programado con jitter', [
                        'descarga_id' => $this->descargaId,
                        'retry_count' => $retryCount,
                        'next_retry_at' => $nextRetry,
                        'delay_seconds' => $delaySeconds,
                    ]);

                    // Programar reintento con delay largo + jitter
                    $this->release($delaySeconds);
                    return;
                }

                $descarga->update([
                    'status' => 'error',
                    'last_error' => $result['message'],
                    'finished_at' => now(),
                ]);

                if ($descarga->created_by) {
                    \App\Models\UserNotification::createForUser(
                        $descarga->created_by,
                        'sat_download_error',
                        'Error en Solicitud SAT',
                        "El SAT rechazó la solicitud de descarga: " . ($result['message'] ?? 'Error desconocido'),
                        ['descarga_id' => $descarga->id, 'error' => $result['message'] ?? ''],
                        '/cfdi',
                        'fas fa-exclamation-triangle'
                    );
                }
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

            // Auto-programar verificación después de 2 minutos (el SAT tarda) con pequeño jitter
            self::dispatch($this->descargaId, 'verify', $resolvedEmpresaId)->delay(now()->addMinutes(2)->addSeconds(random_int(1, 30)));

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
            $message = $result['message'] ?? 'Error desconocido';

            // Si es un error de conexión o transitorio, reintentar con backoff
            if (Str::contains($message, ['conexion', 'timeout', '500', '503', 'intermitencia', 'connection'])) {
                Log::warning('SAT Descarga: Error transitorio en verificación, reintentando...', [
                    'descarga_id' => $this->descargaId,
                    'error' => $message,
                    'attempt' => $this->attempts()
                ]);

                // Liberar para reintento con el siguiente backoff
                $this->release($this->nextBackoff());
                return;
            }

            $descarga->update([
                'status' => 'error',
                'last_error' => $message,
                'finished_at' => now(),
            ]);

            if ($descarga->created_by) {
                \App\Models\UserNotification::createForUser(
                    $descarga->created_by,
                    'sat_download_error',
                    'Fallo en Descarga SAT',
                    "No se pudieron descargar los paquetes del SAT: " . $message,
                    ['descarga_id' => $descarga->id, 'error' => $message],
                    '/cfdi',
                    'fas fa-times-circle'
                );
            }
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
            'inserted_cfdis' => $stats['staged'] ?? ($stats['staged'] ?? 0),
            'duplicate_cfdis' => $stats['duplicates'] ?? 0,
            'error_cfdis' => $stats['errors'] ?? 0,
            'errors' => ($errorsPayload['errors'] || $errorsPayload['duplicates']) ? $errorsPayload : null,
            'finished_at' => now(),
        ]);

        // Notificar al usuario que la descarga terminó
        if ($descarga->created_by) {
            \App\Models\UserNotification::createForUser(
                $descarga->created_by,
                'sat_download_finished',
                'Sincronización SAT Finalizada',
                "La descarga de facturas " . ($descarga->direccion === 'emitido' ? 'Emitidas' : 'Recibidas') . " ha terminado. Se encontraron {$descarga->total_cfdis} documentos.",
                ['descarga_id' => $descarga->id],
                '/cfdi',
                'fas fa-cloud-download-alt'
            );
        }
    }

    public function backoff(): array
    {
        return [60, 120, 300, 600, 900, 1200];
    }

    private function nextBackoff(): int
    {
        $attempt = max(1, $this->attempts());
        $index = min($attempt - 1, count(self::VERIFY_BACKOFF) - 1);
        $base = self::VERIFY_BACKOFF[$index];

        // Añadir jitter aleatorio entre -15 y +45 segundos para romper sincronía
        return max(30, $base + random_int(-15, 45));
    }
}
