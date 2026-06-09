<?php

namespace App\Notifications;

use App\Models\PedidoOnline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class PedidoCreadoNotification extends Notification implements ShouldQueue
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
        $urlSeguimiento = route('tienda.pedido', ['numero' => $this->pedido->numero_pedido]);

        // Construir tabla de items HTML simple
        $itemsHtml = '<ul style="list-style: none; padding: 0;">';
        foreach ($this->pedido->items as $item) {
            $itemsHtml .= '<li style="border-bottom: 1px solid #eee; padding: 10px 0;">';
            $itemsHtml .= '<strong>' . $item['cantidad'] . 'x</strong> ' . $item['nombre'];
            $itemsHtml .= '<span style="float: right;">$' . number_format($item['precio_con_iva'] * $item['cantidad'], 2) . '</span>';
            $itemsHtml .= '</li>';
        }
        $itemsHtml .= '</ul>';

        $mail = (new MailMessage)
            ->subject("Confirmación de Pedido: {$this->pedido->numero_pedido}")
            ->greeting("¡Hola, {$this->pedido->nombre}!")
            ->line('Gracias por tu compra. Hemos recibido tu pedido y lo estamos procesando.')
            ->line(new HtmlString('<h3 style="color: #2563eb;">Tu Pedido: ' . $this->pedido->numero_pedido . '</h3>'))
            ->line(new HtmlString($itemsHtml))
            ->line(new HtmlString('<div style="text-align: right; font-size: 1.1em;"><strong>Total: $' . number_format($this->pedido->total, 2) . ' MXN</strong></div>'))
            ->action('Ver Estatus del Pedido', $urlSeguimiento);

        // Sección de Facturación
        $mail->line(new HtmlString('<br><div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; border-left: 4px solid #2563eb;">
            <strong>¿Requiere Factura?</strong><br>
            Si necesita factura fiscal, por favor responda a este correo adjuntando su <strong>Constancia de Situación Fiscal</strong> y el <strong>Uso de CFDI</strong>. Tiene hasta fin de mes para solicitarla.
        </div><br>'));

        // Instrucciones si es transferencia
        if ($this->pedido->metodo_pago === 'transferencia' && $this->pedido->estado === 'pendiente') {
            $mail->line('**IMPORTANTE:** Tu pedido está reservado. Para procesarlo, por favor realiza la transferencia a los siguientes datos:')
                ->line(new HtmlString('
                    <div style="background-color: #fff7ed; padding: 15px; border-radius: 8px; border: 1px solid #fed7aa;">
                        <strong>Banco:</strong> BBVA<br>
                        <strong>Cuenta:</strong> 0123456789 (Ejemplo)<br>
                        <strong>CLABE:</strong> 012345678901234567<br>
                        <strong>Concepto:</strong> ' . $this->pedido->numero_pedido . '
                    </div>
                 '));
        }

        return $mail->line('Te notificaremos en cuanto tu paquete sea enviado.');
    }
}
