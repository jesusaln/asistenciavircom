<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Servicio para parsear estados de cuenta bancarios en formato CSV y Excel
 */
class BankStatementParserService
{
    /**
     * Bancos soportados y sus configuraciones
     */
    protected array $bancosConfig = [
        'BBVA' => [
            'delimitador' => ',',
            'encoding' => 'UTF-8',
            'skip_rows' => 0, // Filas a saltar antes del header
            'columnas' => [
                'fecha' => ['Fecha', 'FECHA', 'Fecha Operación', 'Fecha operación'],
                'concepto' => ['Concepto', 'CONCEPTO', 'Descripción', 'DESCRIPCION'],
                'cargo' => ['Cargo', 'CARGO', 'Cargos', 'Retiro'],
                'abono' => ['Abono', 'ABONO', 'Abonos', 'Depósito'],
                'saldo' => ['Saldo', 'SALDO', 'Saldo Final'],
                'referencia' => ['Referencia', 'REFERENCIA', 'Ref', 'No. Referencia'],
            ],
            'formato_fecha' => ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'],
        ],
        'BANORTE' => [
            'delimitador' => ',',
            'encoding' => 'UTF-8',
            'skip_rows' => 0,
            'columnas' => [
                'fecha' => ['FECHA', 'Fecha'],
                'concepto' => ['DESCRIPCION', 'Descripción', 'CONCEPTO'],
                'cargo' => ['RETIRO', 'Retiro', 'Cargo'],
                'abono' => ['DEPOSITO', 'Depósito', 'Abono'],
                'saldo' => ['SALDO', 'Saldo'],
                'referencia' => ['REFERENCIA', 'Referencia'],
            ],
            'formato_fecha' => ['d-M-y', 'd/m/Y', 'd-m-Y'],
        ],
        'SANTANDER' => [
            'delimitador' => ';',
            'encoding' => 'UTF-8',
            'skip_rows' => 0,
            'columnas' => [
                'fecha' => ['Fecha Movimiento', 'Fecha'],
                'concepto' => ['Concepto', 'Descripcion'],
                'cargo' => ['Cargo', 'Retiro'],
                'abono' => ['Abono', 'Deposito'],
                'saldo' => ['Saldo'],
                'referencia' => ['Referencia'],
            ],
            'formato_fecha' => ['d/m/Y', 'd-m-Y'],
        ],
    ];

    /**
     * Detectar el banco basado en el contenido del CSV
     */
    public function detectarBanco(string $contenido): ?string
    {
        $contenidoLower = strtolower($contenido);
        $primeraLinea = strtok($contenido, "\n");

        // Detectar por patrones específicos
        if (str_contains($contenidoLower, 'bbva') || str_contains($primeraLinea, 'Fecha,Concepto')) {
            return 'BBVA';
        }

        if (str_contains($contenidoLower, 'banorte') || str_contains($primeraLinea, 'DESCRIPCION')) {
            return 'BANORTE';
        }

        if (str_contains($contenidoLower, 'santander') || str_contains($primeraLinea, ';')) {
            return 'SANTANDER';
        }

        // Intentar detectar por delimitador
        if (str_contains($primeraLinea, ';')) {
            return 'SANTANDER';
        }

        // Default a BBVA si tiene formato CSV estándar
        if (str_contains($primeraLinea, ',')) {
            return 'BBVA';
        }

        return null;
    }

    /**
     * Parsear el contenido del CSV
     */
    public function parsear(string $contenido, ?string $banco = null): array
    {
        // Detectar banco si no se especificó
        $banco = $banco ?? $this->detectarBanco($contenido);
        
        if (!$banco || !isset($this->bancosConfig[$banco])) {
            throw new \Exception("Formato de banco no reconocido. Bancos soportados: " . implode(', ', array_keys($this->bancosConfig)));
        }

        $config = $this->bancosConfig[$banco];
        
        // Limpiar contenido
        $contenido = $this->limpiarContenido($contenido, $config['encoding']);
        
        // Parsear CSV
        $lineas = str_getcsv($contenido, "\n");
        
        // Saltar filas iniciales si es necesario
        for ($i = 0; $i < $config['skip_rows']; $i++) {
            array_shift($lineas);
        }

        if (empty($lineas)) {
            throw new \Exception("El archivo está vacío o no tiene formato válido");
        }

        // Obtener encabezados
        $headerLine = array_shift($lineas);
        $headers = str_getcsv($headerLine, $config['delimitador']);
        $headers = array_map('trim', $headers);

        // Mapear columnas
        $mapaColumnas = $this->mapearColumnas($headers, $config['columnas']);

        if (!isset($mapaColumnas['fecha'])) {
            throw new \Exception("No se encontró la columna de fecha en el archivo");
        }

        // Parsear movimientos
        $movimientos = [];
        foreach ($lineas as $index => $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            $valores = str_getcsv($linea, $config['delimitador']);
            
            try {
                $movimiento = $this->parsearFila($valores, $mapaColumnas, $config['formato_fecha'], $banco);
                if ($movimiento) {
                    $movimientos[] = $movimiento;
                }
            } catch (\Exception $e) {
                Log::warning("Error parseando fila {$index}: " . $e->getMessage());
            }
        }

        return [
            'banco' => $banco,
            'total_movimientos' => count($movimientos),
            'movimientos' => $movimientos,
        ];
    }

    /**
     * Limpiar contenido del archivo
     */
    protected function limpiarContenido(string $contenido, string $encodingEsperado): string
    {
        // Detectar y convertir encoding
        $encodingDetectado = mb_detect_encoding($contenido, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        
        if ($encodingDetectado && $encodingDetectado !== $encodingEsperado) {
            $contenido = mb_convert_encoding($contenido, $encodingEsperado, $encodingDetectado);
        }

        // Remover BOM si existe
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        
        // Normalizar saltos de línea
        $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);

        return $contenido;
    }

    /**
     * Mapear columnas del archivo a campos internos
     */
    protected function mapearColumnas(array $headers, array $columnasConfig): array
    {
        $mapa = [];

        foreach ($columnasConfig as $campo => $posiblesNombres) {
            foreach ($headers as $index => $header) {
                $headerLimpio = trim(strtolower($header));
                foreach ($posiblesNombres as $posibleNombre) {
                    if ($headerLimpio === strtolower($posibleNombre)) {
                        $mapa[$campo] = $index;
                        break 2;
                    }
                }
            }
        }

        return $mapa;
    }

    /**
     * Parsear una fila de datos
     */
    protected function parsearFila(array $valores, array $mapa, array $formatosFecha, string $banco): ?array
    {
        // Obtener fecha
        $fechaStr = $valores[$mapa['fecha']] ?? null;
        if (!$fechaStr) return null;

        $fecha = $this->parsearFecha($fechaStr, $formatosFecha);
        if (!$fecha) {
            Log::warning("No se pudo parsear fecha: {$fechaStr}");
            return null;
        }

        // Obtener concepto
        $concepto = isset($mapa['concepto']) ? trim($valores[$mapa['concepto']] ?? '') : '';

        // Obtener referencia
        $referencia = isset($mapa['referencia']) ? trim($valores[$mapa['referencia']] ?? '') : '';

        // Obtener montos
        $cargo = isset($mapa['cargo']) ? $this->parsearMonto($valores[$mapa['cargo']] ?? '') : 0;
        $abono = isset($mapa['abono']) ? $this->parsearMonto($valores[$mapa['abono']] ?? '') : 0;
        $saldo = isset($mapa['saldo']) ? $this->parsearMonto($valores[$mapa['saldo']] ?? '') : null;

        // Determinar tipo y monto
        if ($abono > 0) {
            $tipo = 'deposito';
            $monto = $abono;
        } elseif ($cargo > 0) {
            $tipo = 'retiro';
            $monto = -$cargo; // Negativo para retiros
        } else {
            return null; // Sin movimiento
        }

        return [
            'fecha' => $fecha->format('Y-m-d'),
            'concepto' => $concepto,
            'referencia' => $referencia,
            'monto' => $monto,
            'monto_absoluto' => abs($monto),
            'saldo' => $saldo,
            'tipo' => $tipo,
            'banco' => $banco,
        ];
    }

    /**
     * Parsear fecha en múltiples formatos
     */
    protected function parsearFecha(string $fechaStr, array $formatos): ?\DateTime
    {
        $fechaStr = trim($fechaStr);
        
        foreach ($formatos as $formato) {
            $fecha = \DateTime::createFromFormat($formato, $fechaStr);
            if ($fecha && $fecha->format($formato) === $fechaStr) {
                return $fecha;
            }
        }

        // Intentar con strtotime como último recurso
        $timestamp = strtotime($fechaStr);
        if ($timestamp) {
            return new \DateTime("@{$timestamp}");
        }

        return null;
    }

    /**
     * Parsear monto limpiando caracteres no numéricos
     */
    protected function parsearMonto(string $montoStr): float
    {
        // Remover todo excepto números, punto y coma
        $montoStr = trim($montoStr);
        
        if (empty($montoStr)) return 0;

        // Remover símbolos de moneda y espacios
        $montoStr = preg_replace('/[$\s]/', '', $montoStr);
        
        // Manejar formato mexicano (1,234.56) y europeo (1.234,56)
        if (preg_match('/,\d{2}$/', $montoStr)) {
            // Formato europeo: coma como decimal
            $montoStr = str_replace('.', '', $montoStr);
            $montoStr = str_replace(',', '.', $montoStr);
        } else {
            // Formato americano/mexicano: punto como decimal
            $montoStr = str_replace(',', '', $montoStr);
        }

        return (float) $montoStr;
    }

    /**
     * Obtener bancos soportados
     */
    public function getBancosSoportados(): array
    {
        return array_keys($this->bancosConfig);
    }

    /**
     * Obtener formatos de archivo aceptados
     */
    public function getFormatosAceptados(): array
    {
        return ['csv', 'txt', 'xls', 'xlsx'];
    }

    /**
     * Parsear archivo Excel (.xls, .xlsx)
     */
    public function parsearExcel(string $rutaArchivo, ?string $banco = null): array
    {
        try {
            $spreadsheet = IOFactory::load($rutaArchivo);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                throw new \Exception("El archivo Excel está vacío");
            }

            // Detectar banco si no se especificó
            $banco = $banco ?? $this->detectarBancoDesdeExcel($rows);

            if (!$banco || !isset($this->bancosConfig[$banco])) {
                // Usar BBVA como default
                $banco = 'BBVA';
            }

            $config = $this->bancosConfig[$banco];

            // Buscar fila de encabezados (puede no ser la primera)
            $headerRowIndex = $this->encontrarFilaEncabezados($rows, $config['columnas']);
            
            if ($headerRowIndex === null) {
                throw new \Exception("No se encontraron los encabezados esperados en el archivo");
            }

            $headers = array_map(function($cell) {
                return trim((string) $cell);
            }, $rows[$headerRowIndex]);

            // Mapear columnas
            $mapaColumnas = $this->mapearColumnas($headers, $config['columnas']);

            if (!isset($mapaColumnas['fecha'])) {
                throw new \Exception("No se encontró la columna de fecha en el archivo");
            }

            // Parsear movimientos
            $movimientos = [];
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $valores = array_map(function($cell) {
                    return trim((string) $cell);
                }, $rows[$i]);

                // Verificar que la fila no esté vacía
                $filaVacia = empty(array_filter($valores, fn($v) => $v !== '' && $v !== null));
                if ($filaVacia) continue;

                try {
                    $movimiento = $this->parsearFilaExcel($valores, $mapaColumnas, $config['formato_fecha'], $banco);
                    if ($movimiento) {
                        $movimientos[] = $movimiento;
                    }
                } catch (\Exception $e) {
                    Log::warning("Error parseando fila {$i} de Excel: " . $e->getMessage());
                }
            }

            return [
                'banco' => $banco,
                'total_movimientos' => count($movimientos),
                'movimientos' => $movimientos,
            ];

        } catch (\Exception $e) {
            Log::error("Error al parsear Excel: " . $e->getMessage());
            throw new \Exception("Error al leer archivo Excel: " . $e->getMessage());
        }
    }

    /**
     * Detectar banco desde contenido de Excel
     */
    protected function detectarBancoDesdeExcel(array $rows): ?string
    {
        // Buscar menciones de bancos en las primeras filas
        $primerasFilas = array_slice($rows, 0, 10);
        $contenido = strtolower(json_encode($primerasFilas));

        if (str_contains($contenido, 'bbva')) {
            return 'BBVA';
        }
        if (str_contains($contenido, 'banorte')) {
            return 'BANORTE';
        }
        if (str_contains($contenido, 'santander')) {
            return 'SANTANDER';
        }

        return null;
    }

    /**
     * Encontrar la fila que contiene los encabezados
     */
    protected function encontrarFilaEncabezados(array $rows, array $columnasConfig): ?int
    {
        $nombresBuscados = [];
        foreach ($columnasConfig as $nombres) {
            foreach ($nombres as $nombre) {
                $nombresBuscados[] = strtolower($nombre);
            }
        }

        foreach ($rows as $index => $row) {
            $rowLower = array_map(function($cell) {
                return strtolower(trim((string) $cell));
            }, $row);

            // Verificar si esta fila contiene al menos 2 encabezados esperados
            $coincidencias = 0;
            foreach ($rowLower as $celda) {
                if (in_array($celda, $nombresBuscados)) {
                    $coincidencias++;
                }
            }

            if ($coincidencias >= 2) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Parsear una fila de Excel
     */
    protected function parsearFilaExcel(array $valores, array $mapa, array $formatosFecha, string $banco): ?array
    {
        // Obtener fecha
        $fechaRaw = $valores[$mapa['fecha']] ?? null;
        if (!$fechaRaw || $fechaRaw === '') return null;

        $fecha = $this->parsearFechaExcel($fechaRaw, $formatosFecha);
        if (!$fecha) {
            Log::warning("No se pudo parsear fecha Excel: {$fechaRaw}");
            return null;
        }

        // Obtener concepto
        $concepto = isset($mapa['concepto']) ? trim((string)($valores[$mapa['concepto']] ?? '')) : '';

        // Obtener referencia
        $referencia = isset($mapa['referencia']) ? trim((string)($valores[$mapa['referencia']] ?? '')) : '';

        // Obtener montos
        $cargo = isset($mapa['cargo']) ? $this->parsearMontoExcel($valores[$mapa['cargo']] ?? '') : 0;
        $abono = isset($mapa['abono']) ? $this->parsearMontoExcel($valores[$mapa['abono']] ?? '') : 0;
        $saldo = isset($mapa['saldo']) ? $this->parsearMontoExcel($valores[$mapa['saldo']] ?? '') : null;

        // Determinar tipo y monto
        if ($abono > 0) {
            $tipo = 'deposito';
            $monto = $abono;
        } elseif ($cargo > 0) {
            $tipo = 'retiro';
            $monto = -$cargo;
        } else {
            return null;
        }

        return [
            'fecha' => $fecha->format('Y-m-d'),
            'concepto' => $concepto,
            'referencia' => $referencia,
            'monto' => $monto,
            'monto_absoluto' => abs($monto),
            'saldo' => $saldo,
            'tipo' => $tipo,
            'banco' => $banco,
        ];
    }

    /**
     * Parsear fecha desde Excel (puede venir como número serial o string)
     */
    protected function parsearFechaExcel($fechaRaw, array $formatosFecha): ?\DateTime
    {
        // Si es un número (fecha serial de Excel)
        if (is_numeric($fechaRaw) && $fechaRaw > 0) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $fechaRaw);
            } catch (\Exception $e) {
                // Continuar con otros métodos
            }
        }

        // Si es string, intentar parsear con los formatos conocidos
        $fechaStr = trim((string) $fechaRaw);
        if (empty($fechaStr)) return null;

        return $this->parsearFecha($fechaStr, $formatosFecha);
    }

    /**
     * Parsear monto desde Excel (puede venir como número o string)
     */
    protected function parsearMontoExcel($montoRaw): float
    {
        if (is_numeric($montoRaw)) {
            return abs((float) $montoRaw);
        }

        return $this->parsearMonto((string) $montoRaw);
    }
}

