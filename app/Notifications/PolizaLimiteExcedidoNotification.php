<?php

namespace App\Notifications;

use App\Models\PolizaServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolizaLimiteExcedidoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PolizaServicio $poliza;
    protected string $tipoLimite;
    protected int $excedente;

    /**
     * Create a new notification instance.
     */
    public function __construct(PolizaServicio $poliza, string $tipoLimite, int $excedente = 1)
    {
        $this->poliza = $poliza;
        $this->tipoLimite = $tipoLimite;
        $this->excedente = $excedente;
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
        $costoExtra = $this->tipoLimite === 'tickets'
            ? 150 // Costo por ticket extra
            : ($this->poliza->costo_visita_sitio_extra ?? 650);

        $cliente = $this->poliza->cliente;
        $montoACobrar = $this->excedente * $costoExtra;

        return (new MailMessage)
            ->subject("🚨 LÍMITE EXCEDIDO - Póliza {$this->poliza->folio} ({$cliente->nombre_razon_social})")
            ->greeting("Alerta de Excedente")
            ->line("El cliente **{$cliente->nombre_razon_social}** ha excedido el límite de {$tipoTexto} en su póliza.")
            ->line("**Póliza:** {$this->poliza->nombre} (Folio: {$this->poliza->folio})")
            ->line("**Excedente:** {$this->excedente} {$tipoTexto}")
            ->line("**Monto a Cobrar:** $" . number_format($montoACobrar, 2) . " MXN")
            ->action('Ver Póliza en Admin', url("/polizas-servicio/{$this->poliza->id}"))
            ->line('Considera generar una cuenta por cobrar para este excedente.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'poliza_id' => $this->poliza->id,
            'poliza_folio' => $this->poliza->folio,
            'cliente_id' => $this->poliza->cliente_id,
            'cliente_nombre' => $this->poliza->cliente?->nombre_razon_social,
            'tipo_limite' => $this->tipoLimite,
            'excedente' => $this->excedente,
            'alerta' => 'limite_excedido',
            'mensaje' => "Cliente {$this->poliza->cliente?->nombre_razon_social} excedió límite de {$this->tipoLimite}",
        ];
    }
}
