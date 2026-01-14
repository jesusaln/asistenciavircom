<?php

namespace App\Notifications;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewClientRegisteredNotification extends Notification
{
    use Queueable;

    public $cliente;

    /**
     * Create a new notification instance.
     */
    public function __construct(Cliente $cliente)
    {
        $this->cliente = $cliente;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Enviamos a base de datos para la campanita
        // Podríamos agregar 'mail' si quisiéramos enviar correo al staff también
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo Cliente Registrado')
            ->line('Un nuevo cliente se ha registrado en el portal.')
            ->line('Cliente: ' . $this->cliente->nombre_razon_social)
            ->line('Empresa: ' . $this->cliente->nombre_razon_social)
            ->action('Revisar Cliente', route('clientes.index', ['search' => $this->cliente->email]))
            ->line('Por favor revisa y aprueba su acceso.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'user-plus',
            'color' => 'blue', // Opcional, para frontend
            'title' => 'Nuevo cliente registrado',
            'message' => "{$this->cliente->nombre_razon_social} se ha unido.",
            'action_url' => route('clientes.index'),
            'cliente_id' => $this->cliente->id
        ];
    }
}
