<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreditSignatureMail extends Mailable
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
            subject: '🔔 Solicitud de Crédito Firmada: ' . $this->cliente->nombre_razon_social,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.credito_firmado',
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
