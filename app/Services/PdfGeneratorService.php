<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Log;

class PdfGeneratorService
{
    /**
     * Load a view and prepare the PDF with standard configuration.
     *
     * @param string $view The blade view name.
     * @param array $data The data to pass to the view.
     * @param string $paperSize 'letter', 'a4', or array [0, 0, w, h]
     * @param string $orientation 'portrait' or 'landscape'
     * @return \Barryvdh\DomPDF\PDF
     */
    public function loadView(string $view, array $data = [], string|array $paperSize = 'letter', string $orientation = 'portrait')
    {
        // 1. Standardize Company Data Loading
        $config = EmpresaConfiguracion::getConfig();
        $empresa = EmpresaConfiguracion::getInfoEmpresa();
        $colores = EmpresaConfiguracion::getColores();
        $financiera = EmpresaConfiguracion::getConfiguracionFinanciera();

        // 2. Prepare View Data
        $viewData = array_merge([
            'configuracion' => [
                'colores' => $colores,
                'empresa' => $empresa,
                'financiera' => $financiera,
                'pie_pagina_facturas' => EmpresaConfiguracion::getPiePagina('facturas'),
            ],
            'empresa' => $empresa,
        ], $data);

        // 3. Load PDF
        $pdf = Pdf::loadView($view, $viewData);

        // 4. Configure Paper & Options
        $pdf->setPaper($paperSize, $orientation);

        // Standard Options
        $options = [
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
        ];

        // Ticket specific options if needed (detected by paper size array usually)
        if (is_array($paperSize) && count($paperSize) === 4) {
            // For thermal tickets, we might want different margins or fonts
            // The controller used 'monospace' and specific margins for tickets.
            // We can check if view name contains 'ticket' or pass an option.
            if (str_contains($view, 'ticket')) {
                $options['defaultFont'] = 'monospace';
                $options['margin-top'] = 5;
                $options['margin-right'] = 5;
                $options['margin-bottom'] = 5;
                $options['margin-left'] = 5;
            }
        }

        $pdf->setOptions($options);

        return $pdf;
    }

    /**
     * Download the PDF.
     *
     * @param \Barryvdh\DomPDF\PDF $pdf
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function download($pdf, string $filename)
    {
        return $pdf->download($filename);
    }

    /**
     * Stream the PDF inline (browser view).
     * Handles output buffer cleaning to prevent corrupt PDFs.
     *
     * @param \Barryvdh\DomPDF\PDF $pdf
     * @param string $filename
     * @return void Ends execution and outputs content
     */
    public function stream($pdf, string $filename)
    {
        $pdfContent = $pdf->output();

        // Clean output buffer to remove any accidental whitespace/logs
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Accept-Ranges: bytes');

        echo $pdfContent;
        exit;
    }
}
