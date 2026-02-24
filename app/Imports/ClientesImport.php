<?php

namespace App\Imports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Throwable;

class ClientesImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation, SkipsOnFailure, SkipsOnError
{
    use Importable, RemembersRowNumber;

    private $failures = [];
    private $errors = [];

    public function model(array $row)
    {
        // Limpiar y normalizar RFC
        $rfc = strtoupper(trim($row['rfc']));

        // Evitar duplicados por RFC
        if (Cliente::where('rfc', $rfc)->exists()) {
            return null; // El validador 'unique' en rules() se encargará de esto si es necesario, pero aquí evitamos el insert accidental
        }

        return new Cliente([
            'nombre_razon_social' => $row['nombre_razon_social'],
            'tipo_persona' => strtolower($row['tipo_persona'] ?? 'fisica'),
            'rfc' => $rfc,
            'curp' => $row['curp'] ?? null,
            'regimen_fiscal' => (string) ($row['regimen_fiscal'] ?? '601'),
            'uso_cfdi' => (string) ($row['uso_cfdi'] ?? 'G03'),
            'email' => !empty($row['email']) ? mb_strtolower(trim($row['email']), 'UTF-8') : null,
            'telefono' => $row['telefono'] ?? null,
            'celular' => $row['celular'] ?? null,
            'calle' => $row['calle'] ?? null,
            'numero_exterior' => $row['numero_exterior'] ?? null,
            'numero_interior' => $row['numero_interior'] ?? null,
            'colonia' => $row['colonia'] ?? null,
            'codigo_postal' => $row['codigo_postal'] ?? null,
            'municipio' => $row['municipio'] ?? null,
            'estado' => !empty(trim($row['estado'] ?? '')) ? mb_strtoupper(trim($row['estado']), 'UTF-8') : 'SON',
            'pais' => (function ($p) {
                $p = strtoupper(trim($p ?? 'MX'));
                if (empty($p))
                    return 'MX';
                if ($p === 'MÉXICO' || $p === 'MEXICO')
                    return 'MX';
                return mb_substr($p, 0, 2);
            })($row['pais'] ?? 'MX'),
            'limite_credito' => $row['limite_credito'] ?? 0.00,
            'dias_credito' => $row['dias_credito'] ?? 0,
            'activo' => true,
            'requiere_factura' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre_razon_social' => 'required|string|max:255',
            'rfc' => 'required|string|max:13',
            'email' => 'nullable|email|max:255',
            'tipo_persona' => 'nullable|in:fisica,moral,FISICA,MORAL',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nombre_razon_social.required' => 'El nombre o razón social en la fila :row es obligatorio.',
            'rfc.required' => 'El RFC en la fila :row es obligatorio.',
            'rfc.max' => 'El RFC en la fila :row no debe exceder los 13 caracteres.',
            'email.email' => 'El formato de correo en la fila :row no es válido.',
            'tipo_persona.in' => 'El tipo de persona en la fila :row debe ser "fisica" o "moral".',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $msg = "Fila {$failure->row()}: " . implode(', ', $failure->errors());
            $this->failures[] = $msg;
            Log::warning("Falla en importación: " . $msg);
        }
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
        Log::error("Error técnico en importación: " . $e->getMessage());
    }

    public function getFailures()
    {
        return $this->failures;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
