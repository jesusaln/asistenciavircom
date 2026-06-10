<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use App\Support\SensitiveDataLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Segundos entre reintentos

    public int $empresaId;
    private string $to;
    private string $templateName;
    private string $language;
    private array $templateParams;
    private array $meta;

    public function middleware()
    {
        return [new \App\Jobs\Middleware\EnforceTenantContext];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $empresaId,
        string $to,
        string $templateName,
        string $language = 'es_MX',
        array $templateParams = [],
        array $meta = []
    ) {
        $this->empresaId = $empresaId;
        $this->to = $to;
        $this->templateName = $templateName;
        $this->language = $language;
        $this->templateParams = $templateParams;
        $this->meta = $this->sanitizeMeta($meta);

        $delaySeconds = (int) ($this->meta['delay_seconds'] ?? 0);
        $delaySeconds = max(0, min($delaySeconds, 86400));

        if ($delaySeconds > 0) {
            $this->delay(now()->addSeconds($delaySeconds));
        }
    }

    /**
     * Limita metadatos para evitar retrasos o cargas anómalas desde la cola.
     */
    private function sanitizeMeta(array $meta): array
    {
        if (isset($meta['delay_seconds'])) {
            $meta['delay_seconds'] = max(0, min((int) $meta['delay_seconds'], 86400));
        }

        if (isset($meta['header_params']) && is_array($meta['header_params'])) {
            $meta['header_params'] = array_slice($meta['header_params'], 0, 5);
        }

        return $meta;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Procesando envío de plantilla WhatsApp', SensitiveDataLog::redact([
            'empresa_id' => $this->empresaId,
            'to' => $this->to,
            'template' => $this->templateName,
        ]));

        try {
            // Obtener empresa
            $empresa = Empresa::findOrFail($this->empresaId);

            // Verificar que WhatsApp esté habilitado
            if (!$empresa->whatsapp_enabled) {
                throw new \Exception('WhatsApp no está habilitado para esta empresa');
            }

            $isMarketing = ! empty($this->meta['campana_id']) || ($this->meta['tipo'] ?? '') === 'marketing_campaign';
            if ($isMarketing) {
                $cliente = null;
                if (! empty($this->meta['destinatario_id'])) {
                    $dest = \App\Models\MarketingDestinatario::with('cliente')->find($this->meta['destinatario_id']);
                    $cliente = $dest?->cliente;
                }
                if (! $cliente) {
                    $cliente = \App\Models\Cliente::where('empresa_id', $this->empresaId)
                        ->where('telefono', $this->to)
                        ->first();
                }
                if (! $cliente) {
                    throw new \Exception('No se pudo identificar al cliente para validar consentimiento de marketing (WhatsApp).');
                }
                if (! $cliente->hasWhatsAppConsent() || ! $cliente->hasMarketingConsent()) {
                    throw new \Exception('Se requiere consentimiento de WhatsApp y de marketing para campañas.');
                }
            }

            // Crear registro en la tabla de logs
            $log = WhatsAppMessage::create([
                'empresa_id' => $this->empresaId,
                'marketing_campana_id' => $this->meta['campana_id'] ?? null,
                'marketing_destinatario_id' => $this->meta['destinatario_id'] ?? null,
                'to' => $this->to,
                'template_name' => $this->templateName,
                'template_params' => $this->templateParams,
                'status' => WhatsAppMessage::STATUS_QUEUED,
            ]);

            // Crear servicio WhatsApp
            $whatsappService = WhatsAppService::fromEmpresa($empresa);

            // Enviar plantilla
            $response = $whatsappService->sendTemplate(
                $this->to,
                $this->templateName,
                $this->language,
                $this->templateParams,
                $this->meta['header_params'] ?? []
            );

            // Actualizar registro como enviado
            $log->markAsSent(
                $response['messages'][0]['id'] ?? null,
                $response
            );

            if (!empty($this->meta['destinatario_id'])) {
                \App\Models\MarketingDestinatario::whereKey($this->meta['destinatario_id'])->update([
                    'estado' => 'enviado',
                    'external_message_id' => $response['messages'][0]['id'] ?? null,
                    'error_mensaje' => null,
                    'sent_at' => now(),
                ]);
            }

            Log::info('Plantilla WhatsApp enviada exitosamente', SensitiveDataLog::redact([
                'log_id' => $log->id,
                'message_id' => $response['messages'][0]['id'] ?? null,
                'response' => $response,
            ]));

        } catch (Throwable $e) {
            Log::error('Error al enviar plantilla WhatsApp', SensitiveDataLog::redact([
                'empresa_id' => $this->empresaId,
                'to' => $this->to,
                'template' => $this->templateName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            // Si existe un registro de log, marcarlo como fallido
            if (isset($log)) {
                $log->markAsFailed(
                    $e->getCode() ?: 'UNKNOWN_ERROR',
                    ['error' => $e->getMessage()]
                );
            }

            if (!empty($this->meta['destinatario_id'])) {
                \App\Models\MarketingDestinatario::whereKey($this->meta['destinatario_id'])->update([
                    'estado' => 'fallido',
                    'error_mensaje' => $e->getMessage(),
                ]);
            }

            // Re-lanzar excepción para que Laravel maneje el reintento
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Job de WhatsApp falló definitivamente después de todos los reintentos', SensitiveDataLog::redact([
            'empresa_id' => $this->empresaId,
            'to' => $this->to,
            'template' => $this->templateName,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]));

        // Aquí podrías implementar lógica adicional como:
        // - Notificar al administrador por email
        // - Crear un registro de errores
        // - Intentar canales alternativos de comunicación
    }
}
