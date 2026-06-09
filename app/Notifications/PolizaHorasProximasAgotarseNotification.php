<?php

namespace App\Notifications;

use App\Models\PolizaServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada al cliente cuando le queda el 20% de sus horas mensuales.
 */
class PolizaHorasProximasAgotarseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PolizaServicio $poliza;
    public float $horasRestantes;
    public float $porcentaje;

    public function __construct(PolizaServicio $poliza, float $horasRestantes, float $porcentaje)
    {
        $this->poliza = $poliza;
        $this->horasRestantes = $horasRestantes;
        $this->porcentaje = $porcentaje;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $empresa = $this->poliza->empresa ?? null;
        $nombreEmpresa = $empresa->nombre_empresa ?? 'Nuestra Empresa';

        return (new MailMessage)
            ->subject("⚠️ Tu póliza tiene solo {$this->porcentaje}% de horas restantes")
            ->greeting("Hola {$notifiable->nombre_razon_social},")
            ->line("Te informamos que tu póliza **{$this->poliza->folio}** está próxima a agotar sus horas mensuales.")
            ->line("**Horas restantes:** {$this->horasRestantes} hrs ({$this->porcentaje}% de tu banco)")
            ->line("Si necesitas más horas, puedes:")
            ->line("1. Esperar al reinicio del próximo mes")
            ->line("2. Contratar horas adicionales")
            ->line("3. Actualizar tu plan a uno con más horas")
            ->action('Ver mi Póliza', url('/portal/polizas/' . $this->poliza->id))
            ->line("Gracias por confiar en {$nombreEmpresa}.")
            ->salutation('Saludos cordiales');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'poliza_id' => $this->poliza->id,
            'poliza_folio' => $this->poliza->folio,
            'horas_restantes' => $this->horasRestantes,
            'porcentaje' => $this->porcentaje,
            'tipo' => 'alerta_horas_20',
            'mensaje' => "Tu póliza {$this->poliza->folio} tiene solo {$this->horasRestantes} hrs restantes.",
        ];
    }
}
