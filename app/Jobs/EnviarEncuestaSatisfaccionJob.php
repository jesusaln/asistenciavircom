<?php

namespace App\Jobs;

use App\Models\EncuestaSatisfaccion;
use App\Services\EncuestaSatisfaccionService;
use App\Support\EmpresaResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarEncuestaSatisfaccionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;
    public int $maxExceptions = 5;

    public function __construct(public int $encuestaId)
    {
        $this->onQueue('whatsapp-chatbot');
    }

    public function handle(): void
    {
        $encuesta = EncuestaSatisfaccion::find($this->encuestaId);
        if (! $encuesta) {
            Log::info('EnviarEncuestaSatisfaccionJob: encuesta no encontrada', ['id' => $this->encuestaId]);
            return;
        }

        EmpresaResolver::setContext($encuesta->empresa_id);

        try {
            if (! in_array($encuesta->estado, [EncuestaSatisfaccion::ESTADO_PENDIENTE], true)) {
                Log::info('EnviarEncuestaSatisfaccionJob: encuesta ya no está pendiente', [
                    'id' => $encuesta->id,
                    'estado' => $encuesta->estado,
                ]);
                return;
            }

            $service = new EncuestaSatisfaccionService;
            $service->enviarMensajeInicial($encuesta);
        } finally {
            EmpresaResolver::clearContext();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EnviarEncuestaSatisfaccionJob: failed', [
            'encuesta_id' => $this->encuestaId,
            'error' => $exception->getMessage(),
        ]);

        try {
            $encuesta = EncuestaSatisfaccion::find($this->encuestaId);
            if ($encuesta) {
                $encuesta->update([
                    'estado' => EncuestaSatisfaccion::ESTADO_FALLIDA_ENVIO,
                    'ultimo_error_envio' => mb_substr($exception->getMessage(), 0, 1000),
                ]);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
}