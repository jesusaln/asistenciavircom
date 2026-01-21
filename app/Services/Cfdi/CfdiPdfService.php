<?php

namespace App\Services\Cfdi;

use App\Models\Cfdi;
use App\Models\EmpresaConfiguracion;
use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CfdiPdfService
{
    /**
     * Genera el contenido binario de un PDF para un CFDI.
     * 
     * @param Cfdi $cfdiRecord
     * @param string|null $xmlContent Contenido XML opcional (si no se provee, se busca en storage)
     * @return string|null Contenido binario del PDF o null si falla
     */
    public function generatePdfContent(Cfdi $cfdiRecord, ?string $xmlContent = null): ?string
    {
        try {
            if (!$xmlContent) {
                $xmlContent = $this->getXmlContent($cfdiRecord);
            }

            if (!$xmlContent) {
                return null;
            }

            // Parsear XML
            $xml = simplexml_load_string($xmlContent);
            if (!$xml) {
                return null;
            }

            $ns = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $ns['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('tfd', $ns['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital');

            // Registrar namespaces de pagos
            if (isset($ns['pago10']))
                $xml->registerXPathNamespace('pago10', $ns['pago10']);
            if (isset($ns['pago20']))
                $xml->registerXPathNamespace('pago20', $ns['pago20']);
            if (isset($ns['pago']))
                $xml->registerXPathNamespace('pago', $ns['pago']);

            $comprobante = json_decode(json_encode($xml), true);

            // Extraer Timbre Fiscal
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
            $timbreAttributes = [];
            if (count($tfd) > 0) {
                foreach ($tfd[0]->attributes() as $name => $value) {
                    $timbreAttributes[$name] = (string) $value;
                }
            }

            $uuid = $timbreAttributes['UUID'] ?? $cfdiRecord->uuid;

            $data = [
                'uuid' => $uuid,
                'fechaTimbrado' => $timbreAttributes['FechaTimbrado'] ?? $cfdiRecord->fecha_timbrado?->format('Y-m-d\TH:i:s') ?? '',
                'selloCFDI' => $timbreAttributes['SelloCFD'] ?? $cfdiRecord->sello_cfdi ?? '',
                'selloSAT' => $timbreAttributes['SelloSAT'] ?? $cfdiRecord->sello_sat ?? '',
                'noCertificadoSAT' => $timbreAttributes['NoCertificadoSAT'] ?? $cfdiRecord->no_certificado_sat ?? '',
                'cadenaOriginal' => $cfdiRecord->cadena_original ?: "||$uuid||"
            ];

            // Extraer emisor y receptor
            $emisorNode = $xml->xpath('//cfdi:Emisor');
            $receptorNode = $xml->xpath('//cfdi:Receptor');
            $conceptosNode = $xml->xpath('//cfdi:Conceptos/cfdi:Concepto');

            $emisor = count($emisorNode) > 0 ? json_decode(json_encode($emisorNode[0]), true)['@attributes'] ?? [] : [];
            $receptor = count($receptorNode) > 0 ? json_decode(json_encode($receptorNode[0]), true)['@attributes'] ?? [] : [];

            // Procesar Conceptos
            $conceptos = [];
            foreach ($conceptosNode as $node) {
                $asArray = json_decode(json_encode($node), true);
                $conceptos[] = $asArray['@attributes'] ?? $asArray;
            }

            $comprobanteAttrs = $comprobante['@attributes'] ?? $comprobante;
            $comprobanteAttrs['Conceptos'] = $conceptos;

            // Extraer Impuestos
            $impuestosNode = $xml->xpath('//cfdi:Comprobante/cfdi:Impuestos');
            if (count($impuestosNode) > 0) {
                $impGen = json_decode(json_encode($impuestosNode[0]), true);
                $comprobanteAttrs['Impuestos'] = $impGen;
            }

            // Configuración de empresa
            $empresaConfig = EmpresaConfiguracion::getConfig();
            $empresaData = $empresaConfig ? $empresaConfig->toArray() : [];

            // QR Code
            $totalQR = $comprobanteAttrs['Total'] ?? $cfdiRecord->total ?? 0;
            $totalQR = str_pad(number_format((float) $totalQR, 6, '.', ''), 17, '0', STR_PAD_LEFT);
            $qr_url_string = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=" . $uuid .
                "&re=" . ($emisor['Rfc'] ?? $cfdiRecord->rfc_emisor ?? '') .
                "&rr=" . ($receptor['Rfc'] ?? $cfdiRecord->rfc_receptor ?? '') .
                "&tt=" . $totalQR .
                "&fe=" . substr($data['selloCFDI'] ?? '', -8);

            $qrBase64 = null;
            try {
                $renderer = new ImageRenderer(new RendererStyle(300, 1), new SvgImageBackEnd());
                $writer = new Writer($renderer);
                $qrSvg = $writer->writeString($qr_url_string);
                $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
            } catch (\Exception $e) {
                Log::warning("Fallo al generar QR para PDF: " . $e->getMessage());
            }

            // Seleccionar vista
            $viewName = ($cfdiRecord->tipo_comprobante === 'P') ? 'ventas.cfdi_pdf_pago' : 'ventas.cfdi_pdf';
            if (!View::exists($viewName)) {
                $viewName = 'ventas.cfdi_pdf';
            }

            $pdf = Pdf::loadView($viewName, [
                'venta' => null,
                'cfdi' => $cfdiRecord,
                'comprobante' => $comprobanteAttrs,
                'emisor' => $emisor,
                'receptor' => $receptor,
                'data' => $data,
                'qr_url' => $qr_url_string,
                'qr_base64' => $qrBase64,
                'empresa' => $empresaData,
                'color_principal' => $empresaConfig->color_principal ?? '#10b981'
            ]);

            $pdf->setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
            $pdf->setPaper('letter', 'portrait');

            return $pdf->output();

        } catch (\Exception $e) {
            Log::error("Error generating CFDI PDF: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca y retorna el contenido XML de un CFDI.
     */
    public function getXmlContent(Cfdi $cfdiRecord): ?string
    {
        $uuid = $cfdiRecord->uuid;
        $possiblePaths = [
            'cfdis/xml/' . $uuid . '.xml',
            'cfdis/' . $uuid . '.xml',
        ];

        if ($cfdiRecord->fecha_emision) {
            $date = Carbon::parse($cfdiRecord->fecha_emision);
            $year = $date->format('Y');
            $month = $date->format('m');
            $tipo = $cfdiRecord->direccion === 'recibido' ? 'recibidos' : 'emitidos';
            $possiblePaths[] = "cfdis/{$tipo}/{$year}/{$month}/{$uuid}.xml";
            $possiblePaths[] = "cfdis/{$tipo}/{$uuid}.xml";
        }

        if ($cfdiRecord->xml_url) {
            array_unshift($possiblePaths, $cfdiRecord->xml_url);
        }

        foreach ($possiblePaths as $path) {
            if (Storage::exists($path)) {
                return Storage::get($path);
            }
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->get($path);
            }
        }

        return $cfdiRecord->xml_content;
    }
}
