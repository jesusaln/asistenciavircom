<?php

namespace App\Exports;

use App\Models\InventarioMovimiento;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MermasExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $almacenId;

    public function __construct($almacenId = null)
    {
        $this->almacenId = $almacenId;
    }

    public function query()
    {
        $query = InventarioMovimiento::query()
            ->with(['producto', 'almacen', 'user'])
            ->where(function($q) {
                $q->where('motivo', 'like', '%MERMA%')
                  ->orWhere('motivo', 'like', '%EXCEDENTE%')
                  ->orWhere('detalles->auditoria', true);
            });

        if ($this->almacenId) {
            $query->where('almacen_id', $this->almacenId);
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'ID Movimiento',
            'Fecha y Hora',
            'Almacén / Unidad',
            'Producto',
            'SKU',
            'Tipo',
            'Cantidad',
            'Diferencia',
            'Motivo Detallado',
            'Auditado por',
            'Estado'
        ];
    }

    public function map($movimiento): array
    {
        $detalles = $movimiento->detalles;
        $tipoAuditoria = $detalles['tipo_auditoria'] ?? (str_contains($movimiento->motivo, 'MERMA') ? 'MERMA' : 'EXCEDENTE');
        $estado = $detalles['estado'] ?? 'Aprobado';
        
        return [
            $movimiento->id,
            $movimiento->created_at->format('d/m/Y H:i:s'),
            $movimiento->almacen->nombre ?? 'N/A',
            $movimiento->producto->nombre ?? 'N/A',
            $movimiento->producto->codigo ?? 'N/A',
            $tipoAuditoria,
            $movimiento->cantidad,
            ($tipoAuditoria === 'MERMA' ? '-' : '+') . $movimiento->cantidad,
            $movimiento->motivo,
            $movimiento->user->name ?? 'N/A',
            strtoupper($estado)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E293B']]],
        ];
    }
}
