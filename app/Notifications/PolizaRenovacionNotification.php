<?php

namespace App\Notifications;

use App\Models\PolizaServicio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class PolizaRenovacionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PolizaServicio $poliza;

    public function __construct(PolizaServicio $poliza)
    {
        $this->poliza = $poliza;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $empresa = \App\Models\Empresa::find($this->poliza->empresa_id);
        $diasRestantes = $this->poliza->dias_para_vencer;
        $fechaVencimiento = Carbon::parse($this->poliza->fecha_fin)->format('d/m/Y');

        return (new MailMessage)
            ->subject("⚠️ Su póliza {$this->poliza->folio} vence pronto - Renuévela ahora")
            ->greeting("Estimado/a {$notifiable->nombre_razon_social},")
            ->line("Le informamos que su póliza de servicio **{$this->poliza->nombre}** está próxima a vencer.")
            ->line("**Fecha de vencimiento:** {$fechaVencimiento}")
            ->line("**Días restantes:** {$diasRestantes} días")
            ->line("Para garantizar la continuidad de su soporte técnico prioritario, le invitamos a renovar su póliza.")
            ->line("**Beneficios de su póliza actual:**")
            ->line("• Tiempo de respuesta prioritario: {$this->poliza->sla_horas_respuesta} horas")
            ->line("• Horas incluidas mensuales: " . ($this->poliza->horas_incluidas_mensual ?? 'Ilimitadas'))
            ->line("• Tickets mensuales: " . ($this->poliza->limite_mensual_tickets ?? 'Ilimitados'))
            ->action('Renovar Mi Póliza', url('/portal/poliza/' . $this->poliza->id . '/renovar'))
            ->line("Si tiene alguna pregunta, no dude en contactarnos.")
            ->salutation("Atentamente,\n{$empresa->nombre_comercial}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'poliza_id' => $this->poliza->id,
            'folio' => $this->poliza->folio,
            'tipo' => 'renovacion',
        ];
    }
}
