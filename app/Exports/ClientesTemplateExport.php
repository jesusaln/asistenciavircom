<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientesTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        // Retornamos un array vacío ya que solo queremos las cabeceras para la plantilla
        return [];
    }

    public function headings(): array
    {
        return [
            'nombre_razon_social',
            'tipo_persona', // fisica o moral
            'rfc',
            'curp',
            'regimen_fiscal', // Código SAT (ej. 601)
            'uso_cfdi', // Código SAT (ej. G03)
            'email',
            'telefono',
            'celular',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'codigo_postal',
            'municipio',
            'estado', // Código SAT (ej. SON)
            'pais', // Código (ej. MX)
            'limite_credito',
            'dias_credito'
        ];
    }
}
