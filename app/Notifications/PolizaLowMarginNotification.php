<?php

namespace App\Notifications;

use App\Models\PolizaServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolizaLowMarginNotification extends Notification
{
    use Queueable;

    protected $poliza;
    protected $margen;

    /**
     * Create a new notification instance.
     */
    public function __construct(PolizaServicio $poliza, float $margen)
    {
        $this->poliza = $poliza;
        $this->margen = $margen;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->margen < 0 ? 'CRÍTICO: Rentabilidad Negativa' : 'ALERTA: Margen Operativo Bajo';
        $color = $this->margen < 0 ? '#ef4444' : '#f59e0b';

        return (new MailMessage)
            ->subject("{$status} - Póliza {$this->poliza->folio}")
            ->greeting("Hola, Administrador")
            ->line("Se ha detectado un riesgo financiero en la Póliza **{$this->poliza->folio}** del cliente **{$this->poliza->cliente->nombre_razon_social}**.")
            ->line("El margen de rentabilidad actual es de: **" . round($this->margen, 2) . "%**.")
            ->action('Ver Reporte de Rentabilidad', route('polizas-servicio.reporte-rentabilidad'))
            ->line('Le recomendamos revisar los consumos recientes y ajustar la estrategia de atención para este contrato.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'poliza_id' => $this->poliza->id,
            'folio' => $this->poliza->folio,
            'margen' => $this->margen,
            'mensaje' => "Margen bajo detectado: " . round($this->margen, 2) . "%",
            'type' => 'financial_alert'
        ];
    }
}
