<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrdenCompraEnviada extends Mailable
{
    use Queueable, SerializesModels;

    public OrdenCompra $ordenCompra;

    /**
     * Create a new message instance.
     */
    public function __construct(OrdenCompra $ordenCompra)
    {
        $this->ordenCompra = $ordenCompra;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva Orden de Compra - ' . $this->ordenCompra->numero_orden,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orden-compra-enviada',
            with: [
                'ordenCompra' => $this->ordenCompra,
                'proveedor' => $this->ordenCompra->proveedor,
                'productos' => $this->ordenCompra->productos,
                'configuracion' => \App\Models\EmpresaConfiguracion::getConfig(),
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
