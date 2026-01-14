<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppTemplate implements ShouldQueue
{
    use Queueable;

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
        $this->meta = $meta;

        // Configurar delay si se especifica en meta
        if (isset($meta['delay_seconds']) && $meta['delay_seconds'] > 0) {
            $this->delay(now()->addSeconds($meta['delay_seconds']));
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Procesando envío de plantilla WhatsApp', [
            'empresa_id' => $this->empresaId,
            'to' => $this->to,
            'template' => $this->templateName,
        ]);

        try {
            // Obtener empresa
            $empresa = Empresa::findOrFail($this->empresaId);

            // Verificar que WhatsApp esté habilitado
            if (!$empresa->whatsapp_enabled) {
                throw new \Exception('WhatsApp no está habilitado para esta empresa');
            }

            // Verificar consentimiento del cliente (nuevos términos de Meta - Sección 3.a.v)
            $cliente = \App\Models\Cliente::where('telefono', $this->to)->first();
            if ($cliente && !$cliente->hasWhatsAppConsent()) {
                throw new \Exception('El cliente no ha dado consentimiento explícito para recibir mensajes de WhatsApp');
            }

            // Crear registro en la tabla de logs
            $log = WhatsAppMessage::create([
                'empresa_id' => $this->empresaId,
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
                $this->templateParams
            );

            // Actualizar registro como enviado
            $log->markAsSent(
                $response['messages'][0]['id'] ?? null,
                $response
            );

            Log::info('Plantilla WhatsApp enviada exitosamente', [
                'log_id' => $log->id,
                'message_id' => $response['messages'][0]['id'] ?? null,
                'response' => $response,
            ]);

        } catch (Throwable $e) {
            Log::error('Error al enviar plantilla WhatsApp', [
                'empresa_id' => $this->empresaId,
                'to' => $this->to,
                'template' => $this->templateName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Si existe un registro de log, marcarlo como fallido
            if (isset($log)) {
                $log->markAsFailed(
                    $e->getCode() ?: 'UNKNOWN_ERROR',
                    ['error' => $e->getMessage()]
                );
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
        Log::error('Job de WhatsApp falló definitivamente después de todos los reintentos', [
            'empresa_id' => $this->empresaId,
            'to' => $this->to,
            'template' => $this->templateName,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Aquí podrías implementar lógica adicional como:
        // - Notificar al administrador por email
        // - Crear un registro de errores
        // - Intentar canales alternativos de comunicación
    }

    /**
     * Método estático para crear y despachar el job fácilmente
     */
    public static function dispatch(
        int $empresaId,
        string $to,
        string $templateName,
        string $language = 'es_MX',
        array $templateParams = [],
        array $meta = []
    ): self {
        $job = new self($empresaId, $to, $templateName, $language, $templateParams, $meta);

        // Aplicar delay si se especifica
        if (isset($meta['delay_seconds']) && $meta['delay_seconds'] > 0) {
            dispatch($job->delay(now()->addSeconds($meta['delay_seconds'])));
        } else {
            dispatch($job);
        }

        return $job;
    }
}
