<?php

namespace App\Mail;

use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $venta;

    /**
     * Create a new message instance.
     */
    public function __construct(Venta $venta)
    {
        $this->venta = $venta;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comprobante de Venta - ' . $this->venta->numero_venta,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.venta',
            with: [
                'venta' => $this->venta,
                'cliente' => $this->venta->cliente,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        // Generar PDF
        $pdf = Pdf::loadView('ventas.pdf', ['venta' => $this->venta]);
        $pdf->setPaper('letter', 'portrait');

        return [
            Attachment::fromData(fn () => $pdf->output(), 'venta-' . $this->venta->numero_venta . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
