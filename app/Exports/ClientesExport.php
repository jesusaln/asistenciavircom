<?php

namespace App\Exports;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        $f = $this->filters;

        // Ajusta los nombres de relaciones si en tu modelo son diferentes:
        // 'estadoSat', 'regimen', 'uso' son ejemplos frecuentes.
        return Cliente::query()
            ->with(['estadoSat', 'regimen', 'uso'])
            ->when(!empty($f['search']), function ($q) use ($f) {
                $s = trim($f['search']);
                $q->where(function ($qq) use ($s) {
                    $qq->where('nombre_razon_social', 'like', "%{$s}%")
                        ->orWhere('rfc', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->when(isset($f['tipo_persona']) && $f['tipo_persona'] !== '', fn($q) => $q->where('tipo_persona', $f['tipo_persona']))
            ->when(isset($f['regimen_fiscal']) && $f['regimen_fiscal'] !== '', fn($q) => $q->where('regimen_fiscal', $f['regimen_fiscal']))
            ->when(isset($f['uso_cfdi']) && $f['uso_cfdi'] !== '', fn($q) => $q->where('uso_cfdi', $f['uso_cfdi']))
            ->when(isset($f['estado']) && $f['estado'] !== '', fn($q) => $q->where('estado', $f['estado']))
            ->when(isset($f['activo']) && $f['activo'] !== '' && $f['activo'] !== null, function ($q) use ($f) {
                // 'activo' llega como '1'|'0' (string) desde la UI
                $q->where('activo', (int) $f['activo'] === 1);
            })
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre/Razón Social',
            'Tipo de persona',
            'RFC',
            'CURP',
            'Régimen fiscal',
            'Uso CFDI',
            'Email',
            'Teléfono',
            'Calle',
            'Num. exterior',
            'Num. interior',
            'Colonia',
            'Código postal',
            'Municipio',
            'Estado',
            'País',
            'Activo',
            'Creado el',
        ];
    }

    public function map($c): array
    {
        return [
            $c->id,
            $c->nombre_razon_social,
            $c->tipo_persona ? ucfirst($c->tipo_persona) : '',
            $c->rfc,
            $c->curp ?? '',
            // muestra "626 — Régimen Simplificado..." si existen relaciones
            $c->regimen_fiscal . ($c->regimen?->descripcion ? " — {$c->regimen->descripcion}" : ''),
            $c->uso_cfdi . ($c->uso?->descripcion ? " — {$c->uso->descripcion}" : ''),
            $c->email,
            $c->telefono,
            $c->calle,
            $c->numero_exterior,
            $c->numero_interior,
            $c->colonia,
            $c->codigo_postal,
            $c->municipio,
            $c->estado . ($c->estadoSat?->nombre ? " — {$c->estadoSat->nombre}" : ''),
            $c->pais,
            $c->activo ? 'Activo' : 'Inactivo',
            optional($c->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
