<?php

namespace App\Notifications;

use App\Models\PolizaConsumo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolizaConsumoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $consumo;

    /**
     * Create a new notification instance.
     */
    public function __construct(PolizaConsumo $consumo)
    {
        $this->consumo = $consumo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Por ahora solo mail, se puede expandir a database o whatsapp
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $poliza = $this->consumo->poliza;
        $tipoLabel = $this->consumo->tipo_label;

        return (new MailMessage)
            ->subject("Nuevo consumo registrado - Póliza {$poliza->folio}")
            ->greeting("Hola, {$notifiable->nombre_razon_social}")
            ->line("Se ha registrado un nuevo consumo de servicio en su póliza **{$poliza->nombre}**.")
            ->line("**Detalle del servicio:**")
            ->line("- **Tipo:** {$tipoLabel}")
            ->line("- **Descripción:** {$this->consumo->descripcion}")
            ->line("- **Fecha:** " . $this->consumo->fecha_consumo->format('d/m/Y H:i'))
            ->line("- **Ahorro Estimado:** $" . number_format($this->consumo->ahorro, 2))
            ->action('Ver Historial de Póliza', route('portal.polizas.historial', $poliza->id))
            ->line('Gracias por confiar en nuestros servicios.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'consumo_id' => $this->consumo->id,
            'poliza_id' => $this->consumo->poliza_id,
            'tipo' => $this->consumo->tipo,
            'ahorro' => $this->consumo->ahorro,
        ];
    }
}
