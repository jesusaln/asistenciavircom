<?php

namespace App\Notifications;

use App\Models\PolizaServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolizaLimiteProximoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PolizaServicio $poliza;
    protected string $tipoLimite;
    protected int $porcentaje;

    /**
     * Create a new notification instance.
     */
    public function __construct(PolizaServicio $poliza, string $tipoLimite, int $porcentaje)
    {
        $this->poliza = $poliza;
        $this->tipoLimite = $tipoLimite;
        $this->porcentaje = $porcentaje;
    }

    /**
     * Get the notification's delivery channels.
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
        $tipoTexto = $this->tipoLimite === 'tickets' ? 'tickets de soporte' : 'visitas en sitio';
        $consumido = $this->tipoLimite === 'tickets'
            ? ($this->poliza->tickets_soporte_consumidos_mes ?? 0)
            : ($this->poliza->visitas_sitio_consumidas_mes ?? 0);
        $limite = $this->tipoLimite === 'tickets'
            ? ($this->poliza->limite_mensual_tickets ?? 0)
            : ($this->poliza->visitas_sitio_mensuales ?? 0);

        return (new MailMessage)
            ->subject("⚠️ Tu póliza está al {$this->porcentaje}% de su límite - {$this->poliza->nombre}")
            ->greeting("Hola {$notifiable->nombre_razon_social}")
            ->line("Te informamos que tu póliza **{$this->poliza->nombre}** (Folio: {$this->poliza->folio}) ha alcanzado el **{$this->porcentaje}%** de su límite mensual de {$tipoTexto}.")
            ->line("**Consumo actual:** {$consumido} de {$limite} {$tipoTexto}")
            ->line("Si necesitas servicios adicionales después de alcanzar el límite, estos serán facturados por separado según las tarifas acordadas.")
            ->action('Ver Mi Póliza', url('/portal'))
            ->line('Gracias por confiar en nuestros servicios.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'poliza_id' => $this->poliza->id,
            'poliza_folio' => $this->poliza->folio,
            'poliza_nombre' => $this->poliza->nombre,
            'tipo_limite' => $this->tipoLimite,
            'porcentaje' => $this->porcentaje,
            'mensaje' => "Póliza {$this->poliza->folio} al {$this->porcentaje}% de {$this->tipoLimite}",
        ];
    }
}
