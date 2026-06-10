<?php

namespace App\Services\RRHH;

use App\Models\User;
use App\Services\CfdiXmlParserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class EmployeeImportService
{
    protected CfdiXmlParserService $parser;

    public function __construct(CfdiXmlParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Importar empleados desde uno o varios archivos XML de nómina
     *
     * @param array|UploadedFile $files
     * @return array Resumen de la importación
     */
    public function importFromXmls($files): array
    {
        if (!is_array($files)) {
            $files = [$files];
        }

        $results = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
            'details' => []
        ];

        foreach ($files as $file) {
            try {
                $xmlContent = file_get_contents($file->getRealPath());
                $importResult = $this->importFromXmlContent($xmlContent, $file->getClientOriginalName());
                
                if ($importResult['status'] === 'created') {
                    $results['created']++;
                } else {
                    $results['updated']++;
                }
                
                $results['total']++;
                $results['details'][] = [
                    'file' => $file->getClientOriginalName(),
                    'employee' => $importResult['employee_name'],
                    'status' => $importResult['status']
                ];

            } catch (Exception $e) {
                Log::error("Error importando empleado desde XML: " . $e->getMessage());
                $results['errors'][] = "Error en archivo {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Importar desde contenido XML directo
     */
    public function importFromXmlContent(string $xmlContent, string $identifier = 'XML'): array
    {
        $data = $this->parser->parseCfdiXml($xmlContent);

        if ($data['tipo_comprobante'] !== 'N') {
            throw new Exception("El comprobante {$identifier} no es de nómina.");
        }

        $nominaData = $data['complementos']['nomina'] ?? null;
        if (!$nominaData) {
            $nominaData = $this->findNominaComplement($data);
        }

        if (!$nominaData) {
            throw new Exception("No se encontró el complemento de nómina en {$identifier}.");
        }

        $receptorData = $data['receptor'] ?? [];
        $nominaReceptor = $nominaData['receptor'] ?? [];

        $employeeInfo = [
            'name' => $receptorData['nombre'] ?? 'Empleado importado',
            'rfc' => $receptorData['rfc'] ?? null,
            'curp' => $nominaReceptor['curp'] ?? null,
            'nss' => $nominaReceptor['num_seguridad_social'] ?? null,
            'fecha_contratacion' => $nominaReceptor['fecha_inicio_rel_laboral'] ?? null,
            'puesto' => $nominaReceptor['puesto'] ?? null,
            'departamento' => $nominaReceptor['departamento'] ?? null,
            'numero_empleado' => $nominaReceptor['num_empleado'] ?? null,
            'tipo_jornada' => $this->mapTipoJornada($nominaReceptor['tipo_jornada'] ?? ''),
            'frecuencia_pago' => $this->mapPeriodicidad($nominaReceptor['periodicidad_pago'] ?? ''),
            'tipo_contrato' => $this->mapTipoContrato($nominaReceptor['tipo_contrato'] ?? ''),
            // Campos técnicos adicionales
            'tipo_regimen' => $nominaReceptor['tipo_regimen'] ?? null,
            'riesgo_puesto' => $nominaReceptor['riesgo_puesto'] ?? null,
            'clave_ent_fed' => $nominaReceptor['clave_ent_fed'] ?? null,
            'sindicalizado' => ($nominaReceptor['sindicalizado'] ?? 'No') === 'Sí',
            'registro_patronal' => $nominaData['emisor']['registro_patronal'] ?? null,
            'salario_base' => $this->calculateMonthlySalary(
                $nominaData['total_percepciones'] ?? 0,
                $nominaReceptor['periodicidad_pago'] ?? '04'
            ),
            'salario_diario_integrado' => $nominaReceptor['salario_diario_integrado'] ?? 0,
            'salario_base_cotizacion' => $nominaReceptor['salario_base_cot_apor'] ?? 0,
        ];

        $processResult = $this->processEmployee($employeeInfo);
        
        return [
            'status' => $processResult['status'],
            'employee_name' => $employeeInfo['name'],
            'user' => $processResult['user']
        ];
    }

    /**
     * Procesar la creación o actualización del empleado
     */
    private function processEmployee(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = null;

            // 1. Buscar por RFC
            if (!empty($data['rfc'])) {
                $user = User::where('rfc', $data['rfc'])->first();
            }

            // 2. Buscar por CURP si no se encontró por RFC
            if (!$user && !empty($data['curp'])) {
                $user = User::where('curp', $data['curp'])->first();
            }

            // 3. Buscar por Número de Empleado
            if (!$user && !empty($data['numero_empleado'])) {
                $user = User::where('numero_empleado', $data['numero_empleado'])->first();
            }

            $status = 'updated';

            if (!$user) {
                $status = 'created';
                $user = new User();
                // Generar un email ficticio o usar el nombre si no hay email en el XML (CFDI nómina no suele tener email)
                $emailPrefix = str_replace(' ', '.', strtolower($data['name']));
                $user->email = $emailPrefix . '@climasdeldesierto.com';
                
                // Asegurar email único
                $baseEmail = $user->email;
                $counter = 1;
                while (User::where('email', $user->email)->exists()) {
                    $user->email = str_replace('@', "{$counter}@", $baseEmail);
                    $counter++;
                }
                
                $user->password = bcrypt('nomina123'); // Password por defecto
            }

            $user->fill([
                'name' => $data['name'],
                'rfc' => $data['rfc'] ?? $user->rfc,
                'curp' => $data['curp'] ?? $user->curp,
                'nss' => $data['nss'] ?? $user->nss,
                'fecha_contratacion' => $data['fecha_contratacion'] ?? $user->fecha_contratacion,
                'puesto' => $data['puesto'] ?? $user->puesto,
                'departamento' => $data['departamento'] ?? $user->departamento,
                'numero_empleado' => $data['numero_empleado'] ?? $user->numero_empleado,
                'tipo_jornada' => $data['tipo_jornada'] ?? $user->tipo_jornada,
                'frecuencia_pago' => $data['frecuencia_pago'] ?? $user->frecuencia_pago,
                'tipo_contrato' => $data['tipo_contrato'] ?? $user->tipo_contrato,
                // Campos técnicos
                'tipo_regimen' => $data['tipo_regimen'] ?? $user->tipo_regimen,
                'riesgo_puesto' => $data['riesgo_puesto'] ?? $user->riesgo_puesto,
                'salario_diario_integrado' => $data['salario_diario_integrado'] ?? $user->salario_diario_integrado,
                'salario_base_cotizacion' => $data['salario_base_cotizacion'] ?? $user->salario_base_cotizacion,
                'clave_ent_fed' => $data['clave_ent_fed'] ?? $user->clave_ent_fed,
                'registro_patronal' => $data['registro_patronal'] ?? $user->registro_patronal,
                'sindicalizado' => $data['sindicalizado'] ?? $user->sindicalizado,
                'salario_base' => $data['salario_base'] ?? $user->salario_base,
                'es_empleado' => true,
                'activo' => true,
            ]);

            $user->save();

            return ['status' => $status, 'user' => $user];
        });
    }

    /**
     * Mapear periodicidad del SAT a valores del sistema
     */
    private function mapPeriodicidad(string $clave): string
    {
        return match ($clave) {
            '02' => 'semanal',
            '03' => 'catorcenal',
            '04' => 'quincenal',
            '05' => 'mensual',
            default => 'quincenal',
        };
    }

    /**
     * Mapear tipo de jornada del SAT
     */
    private function mapTipoJornada(string $clave): string
    {
        return match ($clave) {
            '01' => 'diurna',
            '02' => 'nocturna',
            '03' => 'mixta',
            default => 'diurna',
        };
    }

    /**
     * Mapear tipo de contrato del SAT
     */
    private function mapTipoContrato(string $clave): string
    {
        // 01: Contrato de trabajo por tiempo indeterminado
        // 02: Contrato de trabajo para obra determinada
        // 03: Contrato de trabajo por tiempo determinado
        if ($clave === '01') return 'indefinido';
        if ($clave === '03') return 'temporal';
        
        return 'tiempo_completo';
    }

    /**
     * Calcular el salario mensual estimado basado en el pago del periodo
     */
    private function calculateMonthlySalary(float $periodAmount, string $periodicidad): float
    {
        return match ($periodicidad) {
            '01' => $periodAmount * 30,      // Diario
            '02' => $periodAmount * (30 / 7), // Semanal
            '03' => $periodAmount * (30 / 14), // Catorcenal
            '04' => $periodAmount * 2,       // Quincenal
            '05' => $periodAmount,           // Mensual
            default => $periodAmount * 2,    // Default quincenal
        };
    }

    /**
     * Buscar el complemento de nómina en los datos parseados
     */
    private function findNominaComplement(array $data): ?array
    {
        // El parser de Climas del Desierto pone los complementos en $data['complementos']
        if (isset($data['complementos']['nomina'])) {
            return $data['complementos']['nomina'];
        }

        // Si por alguna razón no está ahí, podríamos buscar en otros lugares si el parser fuera diferente
        return null;
    }
}
