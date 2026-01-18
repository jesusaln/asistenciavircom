<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Ticket;

class TicketRecibido extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The ticket instance.
     *
     * @var \App\Models\Ticket
     */
    public $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
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
        $url = route('soporte.show', $this->ticket->id);

        return (new MailMessage)
                    ->subject("Hemos recibido tu ticket de soporte [#{$this->ticket->folio}]")
                    ->greeting("Hola {$notifiable->nombre_razon_social},")
                    ->line("Confirmamos que hemos recibido tu solicitud de soporte y la hemos registrado con el siguiente folio:")
                    ->line("**Ticket:** #{$this->ticket->folio}")
                    ->line("**Asunto:** {$this->ticket->titulo}")
                    ->line('Nuestro equipo comenzará a trabajar en tu solicitud lo antes posible. Puedes ver el estado actualizado de tu ticket en cualquier momento haciendo clic en el siguiente botón:')
                    ->action('Ver Mi Ticket', $url)
                    ->line('Gracias por contactarnos.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_folio' => $this->ticket->folio,
            'titulo' => $this->ticket->titulo,
        ];
    }
}
