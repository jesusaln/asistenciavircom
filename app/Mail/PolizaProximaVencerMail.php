<?php

namespace App\Mail;

use App\Models\PolizaServicio;
use App\Models\EmpresaConfiguracion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PolizaProximaVencerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $poliza;
    public $empresa;
    public $diasRestantes;
    public $fechaVencimiento;

    /**
     * Create a new message instance.
     */
    public function __construct(PolizaServicio $poliza)
    {
        $this->poliza = $poliza;
        $this->empresa = EmpresaConfiguracion::getConfig();
        $this->diasRestantes = $poliza->dias_para_vencer;
        $this->fechaVencimiento = \Carbon\Carbon::parse($poliza->fecha_fin)->format('d/m/Y');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $nombreEmpresa = $this->empresa->nombre_empresa ?? 'Soporte Técnico';

        return new Envelope(
            subject: "⚠️ Tu póliza {$this->poliza->nombre} vence en {$this->diasRestantes} días - {$nombreEmpresa}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.poliza_proxima_vencer',
            with: [
                'poliza' => $this->poliza,
                'empresa' => $this->empresa,
                'diasRestantes' => $this->diasRestantes,
                'fechaVencimiento' => $this->fechaVencimiento,
                'cliente' => $this->poliza->cliente,
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
