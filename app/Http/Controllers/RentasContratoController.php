<?php

namespace App\Http\Controllers;

use App\Models\Renta;
use Barryvdh\DomPDF\Facade\Pdf;

class RentasContratoController extends Controller
{
    use Concerns\ConfiguracionEmpresa;

    /**
     * Generar contrato de renta en PDF (PDV) en UTF-8 sin BOM.
     */
    public function contratoPDF(Renta $renta)
    {
        $renta->load(['cliente', 'equipos']);

        $configuracion = $this->getConfiguracionEmpresa();

        $pdf = Pdf::loadView('rentas.contrato', [
            'renta' => $renta,
            'configuracion' => $configuracion,
        ]);

        $pdf->setPaper('letter', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $filename = 'contrato-' . ($renta->numero_contrato ?: $renta->id) . '.pdf';
        return $pdf->download($filename);
    }
}
