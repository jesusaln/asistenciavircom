<?php

namespace App\Notifications;

use App\Models\Cita;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NuevaCitaPublicaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $cita;
    public $nombre;

    /**
     * Create a new notification instance.
     */
    public function __construct(Cita $cita, $nombre)
    {
        $this->cita = $cita;
        $this->nombre = $nombre;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'calendar-plus',
            'color' => 'orange',
            'title' => 'Nueva Solicitud de Cita',
            'message' => "{$this->nombre} ha solicitado una cita vía web.",
            'action_url' => route('citas.index'),
            'cita_id' => $this->cita->id,
            'type' => 'appointment_request'
        ];
    }
}
