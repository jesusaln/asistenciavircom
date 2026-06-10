<?php

namespace App\Notifications;

use App\Models\PolizaServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolizaReporteMensualNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $poliza;
    protected $pdfContent;
    protected $mes;
    protected $anio;
    protected $mesNombre;

    /**
     * Create a new notification instance.
     */
    public function __construct(PolizaServicio $poliza, $pdfContent, $mes, $anio)
    {
        $this->poliza = $poliza;
        $this->pdfContent = $pdfContent;
        $this->mes = $mes;
        $this->anio = $anio;
        $this->mesNombre = \Carbon\Carbon::createFromDate($anio, $mes, 1)->locale('es')->monthName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $fileName = "Reporte-{$this->poliza->folio}-" . ucfirst($this->mesNombre) . "-{$this->anio}.pdf";

        return (new MailMessage)
            ->subject("📊 Reporte Mensual de Servicio - {$this->poliza->folio} - " . ucfirst($this->mesNombre))
            ->greeting("Hola, {$notifiable->nombre_razon_social}")
            ->line("Adjunto encontrará el resumen detallado de los servicios de soporte técnico realizados bajo su póliza de mantenimiento durante el mes de " . ucfirst($this->mesNombre) . " de {$this->anio}.")
            ->line("En este reporte podrá visualizar:")
            ->line("• Resumen de horas de soporte consumidas.")
            ->line("• Listado de tickets atendidos y resueltos.")
            ->line("• Inventario de equipos protegidos.")
            ->attachData($this->pdfContent, $fileName, [
                'mime' => 'application/pdf',
            ])
            ->line("Agradecemos su confianza en nuestros servicios.")
            ->action('Ver mi Panel de Cliente', url('/portal/login'))
            ->salutation("Saludos cordiales,\nEl equipo de Soporte Técnico");
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
            'mes' => $this->mes,
            'anio' => $this->anio,
        ];
    }
}
