<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientAccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $empresa;

    /**
     * Create a new message instance.
     */
    public function __construct(Cliente $cliente)
    {
        $this->cliente = $cliente;
        $this->empresa = EmpresaConfiguracion::getConfig();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido! Tu acceso al portal de ' . ($this->empresa->nombre_empresa ?? 'nuestra plataforma') . ' ha sido aprobado',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cliente_aprobado',
            with: [
                'cliente' => $this->cliente,
                'empresa' => $this->empresa,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
