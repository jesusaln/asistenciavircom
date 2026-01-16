<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ClienteCreditoPDFController extends Controller
{
    public function contrato(Cliente $cliente)
    {
        $config = EmpresaConfiguracion::getConfig();

        $data = [
            'cliente' => $cliente,
            'empresa' => $config,
            'fecha' => now()->format('d/m/Y'),
        ];

        // Usar info de empresa para el PDF (incluye logo en base64 para DomPDF)
        $data['infoEmpresa'] = EmpresaConfiguracion::getInfoEmpresa();

        $pdf = Pdf::loadView('pdf.contrato-credito', $data);

        return $pdf->stream("Contrato_Credito_{$cliente->nombre_razon_social}.pdf");
    }
}
