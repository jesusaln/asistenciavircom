<?php

namespace App\Notifications;

use App\Models\PedidoOnline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PedidoEnviadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $pedido;

    /**
     * Create a new notification instance.
     */
    public function __construct(PedidoOnline $pedido)
    {
        $this->pedido = $pedido;
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
        $url = route('tienda.pedido', $this->pedido->numero_pedido);

        $message = (new MailMessage)
            ->subject('¡Tu pedido #' . $this->pedido->numero_pedido . ' ha sido enviado!')
            ->greeting('¡Hola, ' . $this->pedido->nombre . '!')
            ->line('Tenemos excelentes noticias: tu pedido ya ha sido enviado y está en camino a su destino.')
            ->line('Paquetería: ' . ($this->pedido->paqueteria ?? 'Mensajería'))
            ->line('Número de Guía: ' . $this->pedido->guia_envio);

        if ($this->pedido->tracking_url) {
            $message->action('Rastrear mi paquete', $this->pedido->tracking_url);
        } else {
            $message->action('Ver detalle del pedido', $url);
        }

        return $message
            ->line('Gracias por comprar con nosotros. ¡Esperamos que disfrutes tu compra!')
            ->line('Atentamente,')
            ->line('El equipo de Asistencia Vircom');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pedido_id' => $this->pedido->id,
            'numero_pedido' => $this->pedido->numero_pedido,
            'guia_envio' => $this->pedido->guia_envio,
        ];
    }
}
