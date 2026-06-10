<?php

namespace App\Notifications;

use App\Models\Proveedor;
use App\Models\RepseContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class RepseExpirationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected RepseContract|Proveedor $entity;
    protected int $days;
    protected string $type;
    protected ?string $bimestreLabel;

    public function __construct(
        RepseContract|Proveedor $entity,
        int $days,
        string $type = 'own_contract',
        ?string $bimestreLabel = null
    ) {
        $this->entity = $entity;
        $this->days = $days;
        $this->type = $type;
        $this->bimestreLabel = $bimestreLabel;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->type === 'contractor') {
            return $this->contractorMail($notifiable);
        }

        if ($this->type === 'icsoe_reporting') {
            return $this->icsoeMail($notifiable);
        }

        return $this->ownContractMail($notifiable);
    }

    protected function ownContractMail(object $notifiable): MailMessage
    {
        $contract = $this->entity;
        $fechaVencimiento = Carbon::parse($contract->end_date)->format('d/m/Y');
        $cliente = $contract->cliente->nombre_razon_social;

        return (new MailMessage)
            ->subject("ALERTA REPSE: Contrato {$contract->contract_number} vence en {$this->days} días")
            ->greeting("Hola {$notifiable->name},")
            ->line("Se ha detectado que el contrato REPSE **{$contract->contract_number}** está próximo a expirar.")
            ->line("**Cliente:** {$cliente}")
            ->line("**Fecha de vencimiento:** {$fechaVencimiento}")
            ->line("**Días restantes:** {$this->days} días")
            ->line("Es crucial renovar este contrato o las adendas correspondientes para mantener el blindaje legal ante la STPS.")
            ->action('Ver Contrato', url('/comisiones/repse/' . $contract->id))
            ->line("Recuerda que la falta de un contrato vigente puede invalidar la deducibilidad de los servicios especializados.")
            ->salutation("Sistema de Blindaje Legal - Climas del Desierto");
    }

    protected function contractorMail(object $notifiable): MailMessage
    {
        $proveedor = $this->entity;
        $fechaVencimiento = $proveedor->repse_expiry->format('d/m/Y');

        return (new MailMessage)
            ->subject("ALERTA REPSE: Registro REPSE del contratista {$proveedor->nombre_razon_social} vence en {$this->days} días")
            ->greeting("Hola {$notifiable->name},")
            ->line("El registro REPSE del contratista **{$proveedor->nombre_razon_social}** está próximo a expirar.")
            ->line("**RFC:** {$proveedor->rfc}")
            ->line("**Folio REPSE:** " . ($proveedor->repse_number ?? 'No registrado'))
            ->line("**Fecha de vencimiento:** {$fechaVencimiento}")
            ->line("**Días restantes:** {$this->days} días")
            ->line("Como contratante, es tu responsabilidad verificar que tus proveedores mantengan vigente su registro REPSE.")
            ->action('Ver Contratista', url('/comisiones/repse/' . $proveedor->id))
            ->line("Un contratista sin REPSE vigente pone en riesgo la deducibilidad de los servicios y puede generar responsabilidad solidaria.")
            ->salutation("Sistema de Blindaje Legal - Climas del Desierto");
    }

    protected function icsoeMail(object $notifiable): MailMessage
    {
        $contract = $this->entity;
        $bimestre = $this->bimestreLabel ?? 'actual';
        $trabajadoresCount = $contract->empleados->count();

        return (new MailMessage)
            ->subject("RECORDATORIO ICSOE: Reportar contrato {$contract->contract_number} para bimestre {$bimestre}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Es momento de presentar el reporte bimestral **ICSOE** ante el IMSS.")
            ->line("**Bimestre a reportar:** {$bimestre}")
            ->line("**Contrato:** {$contract->contract_number}")
            ->line("**Cliente:** {$contract->cliente->nombre_razon_social}")
            ->line("**Trabajadores asignados:** {$trabajadoresCount}")
            ->line("Tienes hasta los primeros 5 días de este mes para cumplir con esta obligación.")
            ->action('Exportar ICSOE', url('/comisiones/repse/mis-contratos/' . $contract->id . '/export'))
            ->line("Recuerda que también debes presentar el SISUB ante INFONAVIT en los meses de reporteo cuatrimestral.")
            ->salutation("Sistema de Blindaje Legal - Climas del Desierto");
    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->type === 'contractor') {
            $proveedor = $this->entity;
            return [
                'type' => 'repse_contractor_expiration',
                'proveedor_id' => $proveedor->id,
                'proveedor_nombre' => $proveedor->nombre_razon_social,
                'repse_number' => $proveedor->repse_number,
                'days_remaining' => $this->days,
                'expires_at' => $proveedor->repse_expiry,
                'message' => "El registro REPSE del contratista {$proveedor->nombre_razon_social} vence en {$this->days} días."
            ];
        }

        if ($this->type === 'icsoe_reporting') {
            $contract = $this->entity;
            return [
                'type' => 'icsoe_reporting_reminder',
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'cliente_nombre' => $contract->cliente->nombre_razon_social,
                'bimestre' => $this->bimestreLabel,
                'message' => "Recordatorio ICSOE: Reportar contrato {$contract->contract_number} para bimestre {$this->bimestreLabel}."
            ];
        }

        $contract = $this->entity;
        return [
            'type' => 'repse_expiration',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'cliente_nombre' => $contract->cliente->nombre_razon_social,
            'days_remaining' => $this->days,
            'expires_at' => $contract->end_date,
            'message' => "El contrato REPSE {$contract->contract_number} vence en {$this->days} días."
        ];
    }
}
