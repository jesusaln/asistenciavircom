<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrdenCompraMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public OrdenCompra $orden;
    public string $empresaNombre;

    /**
     * Create a new message instance.
     */
    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
        $this->empresaNombre = config('app.name', 'Sistema ERP');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Orden de Compra #{$this->orden->numero_orden} - {$this->empresaNombre}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orden-compra',
            with: [
                'orden' => $this->orden,
                'proveedor' => $this->orden->proveedor,
                'productos' => $this->orden->productos,
                'empresaNombre' => $this->empresaNombre,
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
