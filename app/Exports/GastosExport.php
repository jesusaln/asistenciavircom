<?php

namespace App\Exports;

use App\Models\Compra;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GastosExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Referencia',
            'Fecha',
            'Categoría',
            'Proveedor',
            'Proyecto',
            'Descripción',
            'Método de Pago',
            'Subtotal',
            'IVA',
            'Total',
            'Responsable',
            'Estado'
        ];
    }

    public function map($gasto): array
    {
        return [
            $gasto->numero_compra,
            $gasto->fecha_compra ? $gasto->fecha_compra->format('d/m/Y') : '-',
            $gasto->categoriaGasto?->nombre ?? '-',
            $gasto->proveedor?->nombre_razon_social ?? '-',
            $gasto->proyecto?->nombre ?? 'Sin Proyecto',
            $gasto->notas,
            $gasto->metodo_pago,
            (float) $gasto->subtotal,
            (float) $gasto->iva,
            (float) $gasto->total,
            $gasto->user?->name ?? $gasto->createdBy?->name ?? '-',
            $gasto->estado
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
