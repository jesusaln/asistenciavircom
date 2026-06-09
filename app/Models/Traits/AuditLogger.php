<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

/**
 * AuditLogger Trait
 *
 * Proporciona funcionalidad de auditoría para scripts de mantenimiento
 * y comandos que realizan cambios directos en la base de datos.
 *
 * @usage:
 *   // En un script fix_*.php
 *   use App\Models\Traits\AuditLogger;
 *
 *   class FixSomething extends Command
 *   {
 *       use AuditLogger;
 *
 *       protected $auditDescription = 'Corrección de datos inconsistentes';
 *
 *       public function handle()
 *       {
 *           $this->logAuditStart();
 *
 *           // Realizar cambios...
 *           $this->logModelChange($model, 'updated', ['campo' => 'valor']);
 *
 *           $this->logAuditComplete();
 *       }
 *   }
 */
trait AuditLogger
{
    /**
     * Descripción de la operación de auditoría
     */
    protected ?string $auditDescription = null;

    /**
     * Tipo de auditoría
     */
    protected string $auditType = 'system_fix';

    /**
     * Causa del cambio
     */
    protected string $auditCause = 'maintenance_script';

    /**
     * Cambios realizados
     */
    protected array $auditChanges = [];

    /**
     *ID del batch de auditoría
     */
    protected ?string $auditBatchId = null;

    /**
     * Iniciar sesión de auditoría
     */
    public function logAuditStart(?string $description = null): string
    {
        $this->auditBatchId = uniqid('audit_', true);
        $this->auditChanges = [];

        if ($description) {
            $this->auditDescription = $description;
        }

        $message = $this->getAuditStartMessage();

        $this->writeAuditLog('audit_started', $message, [
            'batch_id' => $this->auditBatchId,
            'type' => $this->auditType,
            'cause' => $this->auditCause,
            'description' => $this->auditDescription,
            'user_id' => $this->getActorId(),
            'ip_address' => $this->getIpAddress(),
        ]);

        $this->info($message);

        return $this->auditBatchId;
    }

    /**
     * Finalizar sesión de auditoría
     */
    public function logAuditComplete(): void
    {
        if (!$this->auditBatchId) {
            return;
        }

        $summary = [
            'total_changes' => count($this->auditChanges),
            'changes' => $this->auditChanges,
        ];

        $message = $this->getAuditCompleteMessage();

        $this->writeAuditLog('audit_completed', $message, [
            'batch_id' => $this->auditBatchId,
            'type' => $this->auditType,
            'cause' => $this->auditCause,
            'description' => $this->auditDescription,
            'changes_count' => count($this->auditChanges),
            'changes_summary' => $summary,
            'user_id' => $this->getActorId(),
            'ip_address' => $this->getIpAddress(),
        ]);

        $this->info($message);

        $this->auditBatchId = null;
        $this->auditChanges = [];
    }

    /**
     * Registrar cambio en un modelo
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $action 'created', 'updated', 'deleted'
     * @param array $changes Cambios realizados
     */
    public function logModelChange($model, string $action, array $changes = []): void
    {
        $modelClass = get_class($model);
        $modelKey = $model->getKey();
        $modelName = class_basename($modelClass);
        $table = $model->getTable();

        $changeRecord = [
            'batch_id' => $this->auditBatchId,
            'model_type' => $modelClass,
            'model_id' => $modelKey,
            'model_name' => $modelName,
            'table' => $table,
            'action' => $action,
            'changes' => $changes,
            'actor_id' => $this->getActorId(),
            'actor_type' => 'system',
            'timestamp' => now()->toISOString(),
        ];

        $this->auditChanges[] = $changeRecord;

        // Intentar registrar en Spatie Activitylog si está disponible
        $this->logToActivitylog($model, $action, $changes);

        // Escribir a logs
        $message = "[{$this->auditType}] {$modelName} [{$modelKey}] - {$action}";
        $this->writeAuditLog('model_change', $message, $changeRecord);

        if ($this->output && $this->output->isVerbose()) {
            $this->info("  {$message}: " . json_encode($changes));
        }
    }

    /**
     * Registrar cambio masivo
     *
     * @param string $table Nombre de la tabla
     * @param int $count Número de registros afectados
     * @param array $changes Cambios realizados
     */
    public function logBulkChange(string $table, int $count, array $changes = []): void
    {
        $changeRecord = [
            'batch_id' => $this->auditBatchId,
            'table' => $table,
            'action' => 'bulk_update',
            'affected_count' => $count,
            'changes' => $changes,
            'actor_id' => $this->getActorId(),
            'timestamp' => now()->toISOString(),
        ];

        $this->auditChanges[] = $changeRecord;

        $message = "[{$this->auditType}] Bulk update on {$table}: {$count} records";
        $this->writeAuditLog('bulk_change', $message, $changeRecord);

        $this->info("  {$message}");
    }

    /**
     * Registrar error en auditoría
     *
     * @param \Throwable $e
     * @param array $context Contexto adicional
     */
    public function logAuditError(\Throwable $e, array $context = []): void
    {
        $this->writeAuditLog('audit_error', 'Audit error occurred', [
            'batch_id' => $this->auditBatchId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
        ]);

        $this->error("Audit error: {$e->getMessage()}");
    }

    /**
     * Cancelar sesión de auditoría (rollback simulado)
     */
    public function logAuditCancelled(?string $reason = null): void
    {
        if (!$this->auditBatchId) {
            return;
        }

        $this->writeAuditLog('audit_cancelled', 'Audit session cancelled', [
            'batch_id' => $this->auditBatchId,
            'reason' => $reason,
            'changes_before_cancel' => count($this->auditChanges),
        ]);

        $this->info("Audit cancelled: {$reason}");

        $this->auditBatchId = null;
        $this->auditChanges = [];
    }

    /**
     * Obtener ID del batch actual
     */
    public function getAuditBatchId(): ?string
    {
        return $this->auditBatchId;
    }

    /**
     * Obtener número de cambios registrados
     */
    public function getChangesCount(): int
    {
        return count($this->auditChanges);
    }

    // ==================== Métodos Protegidos ====================

    /**
     * Escribir a logs del sistema
     */
    protected function writeAuditLog(string $event, string $message, array $context = []): void
    {
        Log::channel('audit')->info($message, $context);

        // También escribir a daily para referencia
        Log::channel('daily')->info("[AUDIT {$event}] {$message}", $context);
    }

    /**
     * Intentar registrar en Spatie Activitylog
     */
    protected function logToActivitylog($model, string $action, array $changes): void
    {
        if (!class_exists(Activity::class)) {
            return;
        }

        try {
            $causer = $this->getActor();

            activity()
                ->performedOn($model)
                ->withProperties([
                    'batch_id' => $this->auditBatchId,
                    'type' => $this->auditType,
                    'cause' => $this->auditCause,
                    'changes' => $changes,
                ])
                ->log($this->formatAction($action));
        } catch (\Exception $e) {
            // Silently fail - no romper el script por fallo de logging
        }
    }

    /**
     * Formatear acción para Activitylog
     */
    protected function formatAction(string $action): string
    {
        $actions = [
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'restored' => 'restored',
        ];

        return $actions[$action] ?? $action;
    }

    /**
     * Obtener ID del actor (usuario o sistema)
     */
    protected function getActorId(): ?int
    {
        $actor = $this->getActor();
        return $actor?->getKey();
    }

    /**
     * Obtener el actor (usuario autenticado o sistema)
     */
    protected function getActor()
    {
        try {
            if (class_exists('Illuminate\Support\Facades\Auth') && auth()->check()) {
                return auth()->user();
            }
        } catch (\Exception $e) {
            // Auth no disponible
        }

        // Retornar usuario del sistema
        return new class {
            public function getKey()
            {
                return 0;
            }

            public function getAttribute($key)
            {
                return $key === 'name' ? 'System (Maintenance Script)' : null;
            }
        };
    }

    /**
     * Obtener dirección IP
     */
    protected function getIpAddress(): string
    {
        try {
            return request()?->ip() ?? '127.0.0.1';
        } catch (\Exception $e) {
            return '127.0.0.1';
        }
    }

    /**
     * Obtener mensaje de inicio
     */
    protected function getAuditStartMessage(): string
    {
        return "Starting audit session [{$this->auditBatchId}]: {$this->auditDescription}";
    }

    /**
     * Obtener mensaje de completion
     */
    protected function getAuditCompleteMessage(): string
    {
        return "Audit session completed [{$this->auditBatchId}]: " .
            count($this->auditChanges) . " changes recorded";
    }

    /**
     * Configurar descripción
     */
    public function setAuditDescription(string $description): self
    {
        $this->auditDescription = $description;
        return $this;
    }

    /**
     * Configurar tipo de auditoría
     */
    public function setAuditType(string $type): self
    {
        $this->auditType = $type;
        return $this;
    }

    /**
     * Configurar causa
     */
    public function setAuditCause(string $cause): self
    {
        $this->auditCause = $cause;
        return $this;
    }
}
