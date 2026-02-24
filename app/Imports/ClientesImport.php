<?php

namespace App\Imports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;

class ClientesImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    use Importable;

    public function model(array $row)
    {
        // Validamos campos mínimos requeridos
        if (empty($row['nombre_razon_social']) || empty($row['rfc'])) {
            return null;
        }

        // Limpiar y normalizar RFC
        $rfc = strtoupper(trim($row['rfc']));

        // Evitar duplicados por RFC en el mismo proceso de importación
        $exists = Cliente::where('rfc', $rfc)->exists();
        if ($exists) {
            Log::info("Cliente con RFC {$rfc} ya existe, omitiendo.");
            return null;
        }

        return new Cliente([
            'nombre_razon_social' => $row['nombre_razon_social'],
            'tipo_persona' => strtolower($row['tipo_persona'] ?? 'fisica'),
            'rfc' => $rfc,
            'curp' => $row['curp'] ?? null,
            'regimen_fiscal' => (string) ($row['regimen_fiscal'] ?? '601'),
            'uso_cfdi' => (string) ($row['uso_cfdi'] ?? 'G03'),
            'email' => $row['email'] ?? null,
            'telefono' => $row['telefono'] ?? null,
            'celular' => $row['celular'] ?? null,
            'calle' => $row['calle'] ?? null,
            'numero_exterior' => $row['numero_exterior'] ?? null,
            'numero_interior' => $row['numero_interior'] ?? null,
            'colonia' => $row['colonia'] ?? null,
            'codigo_postal' => $row['codigo_postal'] ?? null,
            'municipio' => $row['municipio'] ?? null,
            'estado' => $row['estado'] ?? 'SON',
            'pais' => $row['pais'] ?? 'MX',
            'limite_credito' => $row['limite_credito'] ?? 0.00,
            'dias_credito' => $row['dias_credito'] ?? 0,
            'activo' => true,
            'requiere_factura' => true,
        ]);
    }
}
