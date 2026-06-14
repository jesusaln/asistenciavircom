<?php

namespace App\Mail;

use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Mail\Concerns\ConfigureTenantMail;

class SendPortalCredentialsMail extends Mailable
{
    use Queueable, SerializesModels, ConfigureTenantMail;

    public $cliente;
    public $password;
    public $empresa;

    /**
     * Create a new message instance.
     */
    public function __construct(Cliente $cliente, string $password)
    {
        $this->cliente = $cliente;
        $this->password = $password;
        $this->empresa = EmpresaConfiguracion::getConfig($cliente->empresa_id);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Datos de acceso al Portal de Clientes - ' . ($this->empresa->nombre_empresa ?? 'Asistencia Vircom'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cliente_credenciales',
            with: [
                'cliente' => $this->cliente,
                'password' => $this->password,
                'empresa' => $this->empresa,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
