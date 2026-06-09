<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AlertaMantenimientoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function build(): self
    {
        $carro = $this->datos['carro'] ?? null;
        $mto = $this->datos['mantenimiento'];
        $subject = 'Alerta de mantenimiento: ' . ($mto->tipo ?? 'Servicio') . (
            $carro ? (' - ' . $carro->marca . ' ' . $carro->modelo) : ''
        );

        return $this->subject($subject)
            ->view('emails.alerta_mantenimiento', [ 'datos' => $this->datos ]);
    }
}

