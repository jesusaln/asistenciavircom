<?php

namespace App\Notifications;

use App\Models\SolicitudMaterial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SolicitudMaterialRecibida extends Notification implements ShouldQueue
{
    use Queueable;

    protected $solicitud;

    /**
     * Create a new notification instance.
     */
    public function __construct(SolicitudMaterial $solicitud)
    {
        $this->solicitud = $solicitud->load(['user', 'items.producto']);
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
        $itemsHtml = '<ul style="list-style: none; padding: 0;">';
        foreach ($this->solicitud->items as $item) {
            $desc = $item->producto ? $item->producto->nombre : $item->descripcion;
            $itemsHtml .= '<li style="border-bottom: 1px solid #eee; padding: 10px 0;">';
            $itemsHtml .= '<strong>' . $item->cantidad . ' ' . ($item->unidad_medida ?? 'Pza') . '</strong> - ' . $desc;
            $itemsHtml .= '</li>';
        }
        $itemsHtml .= '</ul>';

        return (new MailMessage)
            ->subject("Nueva Solicitud de Material: {$this->solicitud->folio}")
            ->greeting("Hola, " . $notifiable->name)
            ->line("El técnico **{$this->solicitud->user->name}** ha enviado una nueva solicitud de material.")
            ->line(new HtmlString('<h3 style="color: #2563eb;">Folio: ' . $this->solicitud->folio . '</h3>'))
            ->line("**Tipo:** " . $this->solicitud->tipo)
            ->line("**Prioridad:** " . $this->solicitud->prioridad)
            ->line("**Motivo:** " . $this->solicitud->motivo)
            ->line(new HtmlString('<h4>Artículos:</h4>'))
            ->line(new HtmlString($itemsHtml));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nueva Solicitud de Material',
            'message' => "El técnico {$this->solicitud->user->name} ha solicitado material (Folio: {$this->solicitud->folio})",
            'icon' => 'fas fa-clipboard-list',
            'solicitud_id' => $this->solicitud->id,
            'folio' => $this->solicitud->folio,
            'user_name' => $this->solicitud->user->name,
            'tipo' => $this->solicitud->tipo,
            'prioridad' => $this->solicitud->prioridad,
            'action_url' => "/admin/solicitudes-material", // URL para el panel administrativo
        ];
    }
}
