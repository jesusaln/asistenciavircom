<?php

namespace App\Mail;

use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CotizacionPolizaTiMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $cotizacionData;
    public ?Empresa $empresa;

    /**
     * Create a new message instance.
     */
    public function __construct(array $cotizacionData, ?Empresa $empresa = null)
    {
        $this->cotizacionData = $cotizacionData;
        $this->empresa = $empresa;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $clienteNombre = $this->cotizacionData['cliente_nombre'] ?? 'Estimado Cliente';
        $nombreEmpresa = $this->empresa?->nombre_razon_social ?? \App\Support\Brand::name($this->empresa);

        return $this->subject("🛡️ Propuesta de Póliza de Soporte y Mantenimiento TI — {$clienteNombre}")
            ->view('emails.cotizacion_poliza_ti')
            ->with([
                'data' => $this->cotizacionData,
                'empresa' => $this->empresa,
                'nombreEmpresa' => $nombreEmpresa,
            ]);
    }
}