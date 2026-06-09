<?php

namespace App\Notifications;

use App\Models\PolizaServicio;
use App\Models\Ticket;
use App\Models\Cita;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolizaMantenimientoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $poliza;
    public $ticket;
    public $cita;

    public function __construct(PolizaServicio $poliza, Ticket $ticket, Cita $cita)
    {
        $this->poliza = $poliza;
        $this->ticket = $ticket;
        $this->cita = $cita;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("📅 Mantenimiento Preventivo Programado - {$this->poliza->folio}")
            ->view('emails.poliza_mantenimiento_notificacion', [
                'poliza' => $this->poliza,
                'ticket' => $this->ticket,
                'cita' => $this->cita,
                'cliente' => $this->poliza->cliente,
                'empresa' => $this->poliza->empresa,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'poliza_id' => $this->poliza->id,
            'ticket_id' => $this->ticket->id,
            'cita_id' => $this->cita->id,
            'mensaje' => "Mantenimiento preventivo generado para la póliza {$this->poliza->folio}",
        ];
    }
}
