<?php

namespace App\Services\Cfdi;

use App\Models\Cfdi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Servicio para gestión de archivos físicos de CFDI (XML y PDF)
 * Centraliza la lógica de almacenamiento, rutas y recuperación.
 * Resuelve Error #26: Controladores con Lógica de Infraestructura
 */
class CfdiFileService
{
    protected $pdfService;

    public function __construct(CfdiPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Nombre de archivo legible y seguro para descargas (serie-folio + prefijo UUID).
     */
    public function suggestDownloadBasename(Cfdi $cfdi): string
    {
        $serie = $cfdi->serie !== null && $cfdi->serie !== ''
            ? preg_replace('/[^A-Za-z0-9._-]/', '', (string) $cfdi->serie)
            : 'NA';
        $folio = $cfdi->folio !== null && $cfdi->folio !== ''
            ? preg_replace('/[^A-Za-z0-9._-]/', '', (string) $cfdi->folio)
            : '0';
        $uuidClean = strtolower(preg_replace('/[^a-f0-9]/', '', (string) $cfdi->uuid));
        $short = substr($uuidClean, 0, 8);

        $base = trim("{$serie}-{$folio}_{$short}", '-_');
        if ($base === '' || strlen($base) < 4) {
            return $cfdi->uuid ?: 'cfdi';
        }

        return $base;
    }

    /**
     * Busca la ruta física de un archivo XML
     */
    public function getXmlPath(string $uuid, ?string $fechaEmision = null, ?string $direccion = null): ?string
    {
        $paths = [
            'cfdis/xml/' . $uuid . '.xml',
            'cfdis/' . $uuid . '.xml',
        ];

        if ($fechaEmision) {
            $date = Carbon::parse($fechaEmision);
            $year = $date->format('Y');
            $month = $date->format('m');
            $tipo = $direccion === 'recibido' ? 'recibidos' : 'emitidos';
            $paths[] = "cfdis/{$tipo}/{$year}/{$month}/{$uuid}.xml";
            $paths[] = "cfdis/{$tipo}/{$uuid}.xml";
        }

        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                return Storage::path($path);
            }
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->path($path);
            }
        }

        return null; // No encontrado en sistema de archivos
    }

    /**
     * Resuelve una ruta almacenada en BD contra los discos usados históricamente.
     */
    private function resolveStoredPath(?string $storedPath): ?string
    {
        if (empty($storedPath)) {
            return null;
        }

        $normalizedPath = ltrim($storedPath, '/');
        $publicRelativePath = preg_replace('#^storage/#', '', $normalizedPath);

        $candidates = array_values(array_unique(array_filter([
            $normalizedPath,
            $publicRelativePath,
        ])));

        foreach ($candidates as $candidate) {
            if (Storage::exists($candidate)) {
                return Storage::path($candidate);
            }

            if (Storage::disk('public')->exists($candidate)) {
                return Storage::disk('public')->path($candidate);
            }

            $absolutePaths = [
                storage_path('app/' . $candidate),
                storage_path('app/public/' . $candidate),
            ];

            foreach ($absolutePaths as $absolutePath) {
                if (is_file($absolutePath)) {
                    return $absolutePath;
                }
            }
        }

        return null;
    }

    /**
     * Busca la ruta física de un archivo PDF
     */
    public function getPdfPath(string $uuid, ?string $pdfUrl = null): ?string
    {
        $paths = [
            'cfdis/pdf/' . $uuid . '.pdf',
            'cfdis/' . $uuid . '.pdf',
        ];

        if ($pdfUrl) {
            $paths[] = ltrim($pdfUrl, '/');
        }

        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                return Storage::path($path);
            }
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->path($path);
            }
        }

        return null;
    }

    /**
     * Obtiene el contenido del XML, ya sea de archivo o BD
     */
    public function getXmlContent(Cfdi $cfdi): ?string
    {
        // Reutilizamos la lógica del PdfService que ya es robusta, 
        // o mejor aún, centralizamos aquí si PdfService usa este servicio.
        // Por ahora, delegamos al PdfService para evitar duplicar lógica de lectura
        // de PdfService::getXmlContent, o movemos esa lógica aquí.
        // Dado el refactor, lo ideal es que este servicio sea la fuente de verdad.

        $path = $this->getXmlPath($cfdi->uuid, $cfdi->fecha_emision, $cfdi->direccion);
        if ($path) {
            return file_get_contents($path);
        }

        if ($cfdi->xml_url) {
            $storedPath = $this->resolveStoredPath($cfdi->xml_url);
            if ($storedPath) {
                return file_get_contents($storedPath);
            }
        }

        return $cfdi->xml_content;
    }

    /**
     * Genera respuesta de descarga/visualización para XML
     */
    public function responseXml(Cfdi $cfdi, bool $download = true)
    {
        $uuid = $cfdi->uuid;
        $fileLabel = $this->suggestDownloadBasename($cfdi) . '.xml';
        $path = $this->getXmlPath($uuid, $cfdi->fecha_emision, $cfdi->direccion)
            ?: $this->resolveStoredPath($cfdi->xml_url);
        $disposition = $download ? 'attachment' : 'inline';

        if ($path) {
            return response()->file($path, [
                'Content-Type' => 'application/xml; charset=utf-8',
                'Content-Disposition' => $disposition . '; filename="' . $fileLabel . '"',
            ]);
        }

        // Si no hay archivo, intentar devolver contenido de BD
        $content = $this->getXmlContent($cfdi);
        if ($content) {
            return response($content)
                ->header('Content-Type', 'application/xml; charset=utf-8')
                ->header('Content-Disposition', $disposition . '; filename="' . $fileLabel . '"');
        }

        abort(404, 'XML no encontrado');
    }

    /**
     * Genera respuesta de descarga/visualización para PDF
     * Genera el PDF al vuelo si no existe
     */
    public function responsePdf(Cfdi $cfdi, bool $download = false)
    {
        $uuid = $cfdi->uuid;
        $path = $this->getPdfPath($uuid, $cfdi->pdf_url);

        $pdfName = $this->suggestDownloadBasename($cfdi) . '.pdf';

        if ($path) {
            return $download ?
                response()->download($path, $pdfName) :
                response()->file($path, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $pdfName . '"',
                ]);
        }

        // Generar al vuelo
        return $this->generarPdfAlVuelo($cfdi, $download);
    }

    /**
     * Eliminar archivos físicos asociados a un UUID
     */
    public function deleteFiles(string $uuid): void
    {
        $paths = [
            "cfdis/xml/{$uuid}.xml",
            "cfdis/pdf/{$uuid}.pdf",
            "cfdis/{$uuid}.xml",
            "cfdis/{$uuid}.pdf",
        ];

        foreach ($paths as $path) {
            if (Storage::exists($path))
                Storage::delete($path);
            if (Storage::disk('public')->exists($path))
                Storage::disk('public')->delete($path);
        }
    }

    /**
     * Helper privado para generar PDF al vuelo usando PdfService
     */
    private function generarPdfAlVuelo(Cfdi $cfdi, bool $download)
    {
        $xmlContent = $this->getXmlContent($cfdi);

        if (!$xmlContent) {
            abort(404, 'XML no encontrado para generar PDF');
        }

        $pdfContent = $this->pdfService->generatePdfContent($cfdi, $xmlContent);

        if (!$pdfContent) {
            abort(500, 'Error al generar el PDF');
        }

        $pdfName = $this->suggestDownloadBasename($cfdi) . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename="' . $pdfName . '"');
    }
}
