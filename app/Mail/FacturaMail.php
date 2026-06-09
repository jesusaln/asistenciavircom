<?php

namespace App\Mail;

use App\Models\Cfdi;
use App\Models\Venta;
use App\Models\EmpresaConfiguracion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cfdi;
    public $venta;

    /**
     * Create a new message instance.
     */
    public function __construct(Cfdi $cfdi)
    {
        $this->cfdi = $cfdi;
        $this->venta = $cfdi->venta;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $folio = $this->cfdi->serie . $this->cfdi->folio;
        $empresaConfig = EmpresaConfiguracion::getConfig();
        $empresaName = $empresaConfig->nombre_empresa ?? config('app.name', 'Empresa');
        
        return new Envelope(
            subject: "Factura {$folio} - {$empresaName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.factura',
            with: [
                'cfdi' => $this->cfdi,
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
        $attachments = [];

        // 1. Adjuntar XML
        $xmlPath = 'cfdis/' . $this->cfdi->uuid . '.xml';
        if (Storage::disk('public')->exists($xmlPath)) {
            $fullXmlPath = Storage::disk('public')->path($xmlPath);
            $attachments[] = Attachment::fromPath($fullXmlPath)
                ->as('Factura-' . $this->cfdi->uuid . '.xml')
                ->withMime('application/xml');
        }

        // 2. Generar PDF dinámicamente con logo y QR
        try {
            $pdfContent = $this->generatePdfContent();
            if ($pdfContent) {
                $attachments[] = Attachment::fromData(fn () => $pdfContent, 'Factura-' . $this->cfdi->uuid . '.pdf')
                    ->withMime('application/pdf');
            }
        } catch (\Exception $e) {
            Log::error('Error generando PDF para email: ' . $e->getMessage());
            
            // Fallback: intentar usar pdf_url si existe
            if ($this->cfdi->pdf_url) {
                $pdfPath = storage_path('app/public/' . $this->cfdi->pdf_url);
                if (file_exists($pdfPath)) {
                    $attachments[] = Attachment::fromPath($pdfPath)
                        ->as('Factura-' . $this->cfdi->uuid . '.pdf')
                        ->withMime('application/pdf');
                }
            }
        }

        return $attachments;
    }

    /**
     * Generate PDF content with logo and QR code
     */
    private function generatePdfContent(): ?string
    {
        $uuid = $this->cfdi->uuid;
        
        // Cargar XML para extraer datos
        $xmlPath = 'cfdis/' . $uuid . '.xml';
        if (!Storage::disk('public')->exists($xmlPath)) {
            Log::warning("XML no existe para CFDI: {$uuid}");
            return null;
        }
        
        $xmlContent = Storage::disk('public')->get($xmlPath);
        $xml = simplexml_load_string($xmlContent);
        $ns = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('cfdi', $ns['cfdi']);
        $xml->registerXPathNamespace('tfd', $ns['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital');

        $comprobante = json_decode(json_encode($xml), true);
        
        // Extraer Timbre Fiscal
        $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
        $timbre = count($tfd) > 0 ? json_decode(json_encode($tfd[0]), true) : [];
        
        $data = [
            'uuid' => $timbre['@attributes']['UUID'] ?? '',
            'fechaTimbrado' => $timbre['@attributes']['FechaTimbrado'] ?? '',
            'selloCFDI' => $timbre['@attributes']['SelloCFD'] ?? '',
            'selloSAT' => $timbre['@attributes']['SelloSAT'] ?? '',
            'noCertificadoSAT' => $timbre['@attributes']['NoCertificadoSAT'] ?? '',
            'cadenaOriginal' => $this->cfdi->cadena_original ?? '||' . ($timbre['@attributes']['UUID'] ?? '') . '||'
        ];

        // Extraer emisor y receptor
        $emisorNode = $xml->xpath('//cfdi:Emisor');
        $receptorNode = $xml->xpath('//cfdi:Receptor');
        $conceptosNode = $xml->xpath('//cfdi:Conceptos/cfdi:Concepto');
        
        $emisor = count($emisorNode) > 0 ? json_decode(json_encode($emisorNode[0]), true)['@attributes'] : [];
        $receptor = count($receptorNode) > 0 ? json_decode(json_encode($receptorNode[0]), true)['@attributes'] : [];
        
        // Procesar Conceptos
        $conceptos = [];
        foreach ($conceptosNode as $node) {
            $asArray = json_decode(json_encode($node), true);
            $conceptos[] = $asArray['@attributes'] ?? $asArray;
        }

        $comprobanteAttrs = $comprobante['@attributes'] ?? $comprobante;
        $comprobanteAttrs['Conceptos'] = $conceptos;

        // Datos Extra
        try {
            $emisor['RegimenFiscalNombre'] = \App\Models\SatRegimenFiscal::where('clave', $emisor['RegimenFiscal'])->value('descripcion');
            $receptor['RegimenFiscalReceptorNombre'] = \App\Models\SatRegimenFiscal::where('clave', $receptor['RegimenFiscalReceptor'])->value('descripcion');
            $receptor['UsoCFDINombre'] = \App\Models\SatUsoCfdi::where('clave', $receptor['UsoCFDI'])->value('descripcion');
            $comprobanteAttrs['FormaPagoNombre'] = \App\Models\SatFormaPago::where('clave', $comprobanteAttrs['FormaPago'])->value('descripcion');
            $comprobanteAttrs['MetodoPagoNombre'] = \App\Models\SatMetodoPago::where('clave', $comprobanteAttrs['MetodoPago'])->value('descripcion');
        } catch (\Exception $e) { }

        // Empresa Config para logo
        $empresaConfig = EmpresaConfiguracion::getInfoEmpresa();
        $colores = EmpresaConfiguracion::getColores();
        $color_principal = $colores['principal'] ?? '#2563eb';

        // QR URL
        $totalFixed = number_format((float)$comprobanteAttrs['Total'], 6, '.', '');
        $totalFixed = str_pad($totalFixed, 17, '0', STR_PAD_LEFT);
        $sello8 = substr($data['selloCFDI'], -8);
        $qr_url_string = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?" . 
                  "id=" . $data['uuid'] . 
                  "&re=" . $emisor['Rfc'] . 
                  "&rr=" . $receptor['Rfc'] . 
                  "&tt=" . $totalFixed . 
                  "&fe=" . $sello8;

        // Generar QR en Base64
        $qrBase64 = null;
        try {
            $qrApiUrl = 'https://quickchart.io/qr?text=' . urlencode($qr_url_string) . '&size=200&ecLevel=M&margin=1';
            $response = Http::withoutVerifying()->timeout(5)->get($qrApiUrl);
            
            if ($response->successful()) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            Log::warning('Error generando QR para email: ' . $e->getMessage());
        }

        // Logo en base64
        if (empty($empresaConfig['logo_base64']) && !empty($empresaConfig['logo_path_absolute'])) {
            try {
                $type = pathinfo($empresaConfig['logo_path_absolute'], PATHINFO_EXTENSION);
                $logoData = file_get_contents($empresaConfig['logo_path_absolute']);
                $empresaConfig['logo_base64'] = 'data:image/' . $type . ';base64,' . base64_encode($logoData);
            } catch (\Exception $e) { 
                Log::warning("Error cargando logo para email: " . $e->getMessage()); 
            }
        }

        // Cargar venta con relaciones
        $venta = Venta::with(['cliente', 'items.ventable'])->find($this->cfdi->venta_id);

        // Generar PDF
        $pdf = Pdf::loadView('ventas.cfdi_pdf', [
            'venta' => $venta,
            'cfdi' => (object) array_merge($data, ['serie' => $comprobanteAttrs['Serie'] ?? '', 'folio' => $comprobanteAttrs['Folio'] ?? '']),
            'comprobante' => $comprobanteAttrs,
            'emisor' => $emisor,
            'receptor' => $receptor,
            'data' => $data,
            'qr_url' => $qr_url_string, 
            'qr_base64' => $qrBase64, 
            'empresa' => $empresaConfig,
            'color_principal' => $color_principal
        ]);
        
        $pdf->setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->output();
    }
}
