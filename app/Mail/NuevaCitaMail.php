<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevaCitaMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 Nueva Cita Agendada - ' . ($this->datos['servicio'] ?? 'Servicio'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nueva_cita',
        );
    }
}
