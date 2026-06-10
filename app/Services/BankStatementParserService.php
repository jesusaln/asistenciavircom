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
    protected \App\Services\AI\GeminiService $gemini;

    public function __construct(\App\Services\AI\GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Normalizar texto para comparaciones (quitar acentos, convertir a minúsculas)
     */
    protected function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'Ñ' => 'n', 'Ü' => 'u'
        ]);
        return $texto;
    }

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
        'BANAMEX' => [
            'delimitador' => ',',
            'encoding' => 'UTF-8',
            'skip_rows' => 0,
            'columnas' => [
                'fecha' => ['Fecha de solicitud', 'FECHA', 'Fecha'],
                'concepto' => ['Descripción', 'CONCEPTO', 'Concepto'],
                'monto' => ['Importe', 'MONTO', 'Monto'],
                'referencia' => ['#Autorización / Instrucción', 'REFERENCIA', 'Autorización', 'Referencia'],
                'estado' => ['Estatus', 'Estado'],
            ],
            'formato_fecha' => ['d M Y - H:i', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'd/M/Y - H:i'],
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

        if (str_contains($contenidoLower, 'banamex') || 
            str_contains($primeraLinea, 'Fecha de solicitud') || 
            str_contains($primeraLinea, 'Importe') ||
            str_contains($primeraLinea, 'Estatus')) {
            return 'BANAMEX';
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
        
        // Detectar delimitador si es CSV/TXT
        $delimitador = $config['delimitador'] ?? ',';
        $primeraLinea = strtok($contenido, "\n");
        if (str_contains($primeraLinea, '|')) {
            $delimitador = '|';
        } elseif (str_contains($primeraLinea, "\t")) {
            $delimitador = "\t";
        } elseif (str_contains($primeraLinea, ';')) {
            $delimitador = ';';
        } elseif (str_contains($primeraLinea, ',')) {
            $delimitador = ',';
        }
        
        // Parsear contenido
        $lineas = explode("\n", $contenido);
        
        // Saltar filas iniciales si es necesario
        for ($i = 0; $i < $config['skip_rows']; $i++) {
            array_shift($lineas);
        }

        // Buscar fila de encabezados (revisar las primeras 10 líneas)
        $headerRowIndex = -1;
        $mapaColumnas = [];
        $headersDetectados = [];

        for ($i = 0; $i < min(10, count($lineas)); $i++) {
            $currentHeaders = str_getcsv($lineas[$i], $delimitador);
            $currentHeaders = array_map(fn($h) => trim($h, " \t\n\r\0\x0B\""), $currentHeaders);
            $mapa = $this->mapearColumnas($currentHeaders, $config['columnas']);
            
            if (isset($mapa['fecha']) && (isset($mapa['monto']) || isset($mapa['abono']) || isset($mapa['cargo']))) {
                $headerRowIndex = $i;
                $mapaColumnas = $mapa;
                $headersDetectados = $currentHeaders;
                break;
            }
        }

        if ($headerRowIndex === -1) {
            throw new \Exception("No se encontraron los encabezados esperados en el archivo CSV.");
        }

        // Eliminar las líneas anteriores al header y el header mismo
        for ($i = 0; $i <= $headerRowIndex; $i++) {
            array_shift($lineas);
        }

        // Parsear movimientos
        $movimientos = [];
        foreach ($lineas as $index => $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            $valores = str_getcsv($linea, $delimitador);
            
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
                // Limpiar encabezado: quitar acentos, espacios y convertir a lowercase
                $headerLimpio = $this->normalizarTexto(trim($header));
                foreach ($posiblesNombres as $posibleNombre) {
                    $nombreLimpio = $this->normalizarTexto(trim($posibleNombre));
                    if ($headerLimpio === $nombreLimpio || str_contains($headerLimpio, $nombreLimpio)) {
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
        $montoUnico = isset($mapa['monto']) ? $this->parsearMonto($valores[$mapa['monto']] ?? '') : null;
        $saldo = isset($mapa['saldo']) ? $this->parsearMonto($valores[$mapa['saldo']] ?? '') : null;

        // Determinar tipo y monto
        if ($montoUnico !== null) {
            // Si hay monto único, determinar tipo por signo o por columna de abono/cargo si existen
            $monto = $montoUnico;
            $tipo = $monto >= 0 ? 'deposito' : 'retiro';
            
            // Caso especial: Banamex a veces entrega todo positivo. 
            // Si el concepto sugiere retiro (PAGO, RETIRO, COMPRA) y no hay signo, podríamos inferir.
            // Pero por ahora confiamos en el signo o en que el usuario sepa.
        } elseif ($abono > 0) {
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
        
        // Traducir meses en español a inglés para DateTime
        $meses = [
            'ene' => 'jan', 'feb' => 'feb', 'mar' => 'mar', 'abr' => 'apr', 
            'may' => 'may', 'jun' => 'jun', 'jul' => 'jul', 'ago' => 'aug', 
            'sep' => 'sep', 'oct' => 'oct', 'nov' => 'nov', 'dic' => 'dec'
        ];
        
        $fechaStrLower = strtolower($fechaStr);
        foreach ($meses as $es => $en) {
            if (str_contains($fechaStrLower, $es)) {
                $fechaStr = str_ireplace($es, $en, $fechaStr);
                break;
            }
        }
        
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
        return ['csv', 'txt', 'xls', 'xlsx', 'pdf'];
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
        $montoUnico = isset($mapa['monto']) ? $this->parsearMontoExcel($valores[$mapa['monto']] ?? '') : null;
        $saldo = isset($mapa['saldo']) ? $this->parsearMontoExcel($valores[$mapa['saldo']] ?? '') : null;

        // Determinar tipo y monto
        if ($montoUnico !== null) {
            $monto = $montoUnico;
            $tipo = $monto >= 0 ? 'deposito' : 'retiro';
        } elseif ($abono > 0) {
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
    /**
     * Parsear archivo PDF usando pdftotext
     */
    public function parsearPdf(string $rutaArchivo, ?string $banco = null): array
    {
        try {
            // Ejecutar pdftotext para extraer el contenido
            // -layout intenta mantener la estructura visual del documento
            $output = [];
            $resultCode = 0;
            exec("pdftotext -layout " . escapeshellarg($rutaArchivo) . " -", $output, $resultCode);

            if ($resultCode !== 0) {
                throw new \Exception("Error al ejecutar pdftotext (Código: $resultCode)");
            }

            $contenido = implode("\n", $output);
            
            // Detectar banco si no se especificó
            $banco = $banco ?? $this->detectarBanco($contenido);
            
            if (!$banco) {
                // Búsqueda más agresiva de palabras clave (Banamex usa "MiCuenta" mucho)
                if (stripos($contenido, 'Banamex') !== false || 
                    stripos($contenido, 'Citibanamex') !== false || 
                    stripos($contenido, 'BANCO NACIONAL DE MEXICO') !== false ||
                    stripos($contenido, 'MiCuenta') !== false) {
                    $banco = 'BANAMEX';
                }
            }

            // Lógica específica por banco para extraer filas usando regex
            $movimientos = [];
            
            if ($banco === 'BANAMEX') {
                $movimientos = $this->extraerMovimientosBanamexPdf($contenido);
            }
            
            // Si después de Banamex no hay nada, o si es otro banco, usar genérico
            if (empty($movimientos)) {
                $movimientos = $this->extraerMovimientosGenericoPdf($contenido, $banco);
            }

            if (empty($movimientos)) {
                $snippet = substr(preg_replace('/\s+/', ' ', $contenido), 0, 150);
                Log::warning("No se encontraron movimientos en PDF. Banco detectado: $banco. Snippet: $snippet");
            }

            return [
                'banco' => $banco ?? 'Desconocido',
                'total_movimientos' => count($movimientos),
                'movimientos' => $movimientos,
                'metodo' => 'PDF',
            ];

        } catch (\Exception $e) {
            Log::error("Error al parsear PDF: " . $e->getMessage());
            throw new \Exception("Error al leer archivo PDF: " . $e->getMessage());
        }
    }

    /**
     * Extraer movimientos de un PDF de Banamex usando Regex
     */
    protected function extraerMovimientosBanamexPdf(string $contenido): array
    {
        $movimientos = [];
        $lineas = explode("\n", $contenido);
        
        // Intentar extraer el año del encabezado (ej: "Período del 19 de marzo al 18 de abril del 2026")
        $añoActual = date('Y');
        if (preg_match('/del\s+(\d{4})/i', $contenido, $yearMatches)) {
            $añoActual = $yearMatches[1];
        }
        
        $fechaActual = null;
        $conceptoBuffer = [];

        // Regex para detectar el inicio de un movimiento (Fecha + parte del concepto)
        $inicioPattern = '/^\s*(\d{1,2})\s+(ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|OCT|NOV|DIC)/i';
        
        // Regex para detectar el final de un movimiento (Cualquier línea con dos montos decimales seguidos)
        $finPattern = '/([\d,]+\.\d{2})\s+([\d,]+\.\d{2})/';

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Detectar inicio de movimiento
            if (preg_match($inicioPattern, $linea, $matches)) {
                // Si ya había uno pendiente sin cerrar, guardarlo o resetear
                if ($fechaActual && !empty($conceptoBuffer)) {
                    Log::debug("Banamex PDF: Detectado nuevo inicio sin cerrar anterior. Concepto: " . implode(' ', $conceptoBuffer));
                }

                $dia = $matches[1];
                $mes = $matches[2];
                
                $fechaActual = $this->parsearFecha("$dia $mes $añoActual", ['d M Y']);
                $resto = trim(preg_replace($inicioPattern, '', $linea));
                $conceptoBuffer = [$resto];
                continue;
            }

            // Si estamos en medio de un movimiento, acumular concepto o buscar el final
            if ($fechaActual) {
                if (preg_match($finPattern, $linea, $matches)) {
                    $montoRaw = $matches[1];
                    $saldoRaw = $matches[2];
                    
                    $monto = $this->parsearMonto($montoRaw);
                    $saldo = $this->parsearMonto($saldoRaw);
                    
                    $conceptoCompleto = implode(' ', $conceptoBuffer);
                    
                    // Banamex Monthly Statement: if it's the second column, it's a deposit.
                    // But with pdftotext -layout, we can try to see if there's a lot of spaces before the first amount.
                    // However, keywords are more reliable.
                    $esRetiro = stripos($conceptoCompleto, 'PAGO A') !== false || 
                                stripos($conceptoCompleto, 'RETIRO') !== false || 
                                stripos($conceptoCompleto, 'DOMI') !== false ||
                                stripos($conceptoCompleto, 'CARGO') !== false ||
                                stripos($conceptoCompleto, 'INTERBANCARIO A') !== false ||
                                stripos($conceptoCompleto, 'PAGO DE SERVICIO') !== false ||
                                stripos($conceptoCompleto, 'MPIO DE HERM') !== false; // Ejemplo del usuario
                                
                    $esDeposito = stripos($conceptoCompleto, 'PAGO RECIBIDO') !== false || 
                                  stripos($conceptoCompleto, 'DEPOSITO') !== false || 
                                  stripos($conceptoCompleto, 'ABONO') !== false ||
                                  stripos($conceptoCompleto, 'EXENCION') !== false;

                    $tipo = ($esRetiro && !$esDeposito) ? 'retiro' : 'deposito';

                    $movimientos[] = [
                        'fecha' => $fechaActual->format('Y-m-d'),
                        'concepto' => $conceptoCompleto,
                        'referencia' => '',
                        'monto' => $tipo === 'retiro' ? -$monto : $monto,
                        'monto_absoluto' => $monto,
                        'tipo' => $tipo,
                        'banco' => 'BANAMEX',
                        'saldo' => $saldo
                    ];
                    
                    $fechaActual = null;
                    $conceptoBuffer = [];
                } else {
                    // Si encontramos una línea que parece un nuevo inicio sin haber cerrado el anterior,
                    // probablemente el anterior era un "SALDO ANTERIOR" o similar que no tiene línea de HORA.
                    if (preg_match($inicioPattern, $linea)) {
                        $fechaActual = null; // Reiniciar
                        // Re-procesar esta línea como inicio
                        $dia = $matches[1] ?? '01';
                        $mes = $matches[2] ?? 'ENE';
                        $fechaActual = $this->parsearFecha("$dia $mes $añoActual", ['d M Y']);
                        $conceptoBuffer = [trim(preg_replace($inicioPattern, '', $linea))];
                    } else if (stripos($linea, 'Página') === false && stripos($linea, 'Detalle de Operaciones') === false) {
                        $conceptoBuffer[] = $linea;
                    }
                }
            }
        }

        // Si falló el line-by-line, intentar una búsqueda global más agresiva
        if (empty($movimientos)) {
            $globalPattern = '/(\d{1,2})\s+(ENE|FEB|MAR|ABR|MAY|JUN|JUL|AGO|SEP|OCT|NOV|DIC)\b([\s\S]*?)([\d,]+\.\d{2})\s+([\d,]+\.\d{2})/i';
            if (preg_match_all($globalPattern, $contenido, $globalMatches, PREG_SET_ORDER)) {
                foreach ($globalMatches as $m) {
                    $dia = $m[1];
                    $mes = $m[2];
                    $conceptoRaw = trim($m[3]);
                    $montoRaw = $m[4];
                    $saldoRaw = $m[5];

                    // Limpiar concepto de saltos de línea excesivos y ruidos
                    $concepto = trim(preg_replace('/\s+/', ' ', $conceptoRaw));
                    if (stripos($concepto, 'Página') !== false) continue;
                    if (stripos($concepto, 'SALDO ANTERIOR') !== false) continue;

                    $fecha = $this->parsearFecha("$dia $mes $añoActual", ['d M Y']);
                    if (!$fecha) continue;

                    $monto = $this->parsearMonto($montoRaw);
                    $saldo = $this->parsearMonto($saldoRaw);

                    $esRetiro = stripos($concepto, 'PAGO A') !== false || stripos($concepto, 'RETIRO') !== false || stripos($concepto, 'DOMI') !== false;
                    $tipo = $esRetiro ? 'retiro' : 'deposito';

                    $movimientos[] = [
                        'fecha' => $fecha->format('Y-m-d'),
                        'concepto' => $concepto,
                        'referencia' => '',
                        'monto' => $tipo === 'retiro' ? -$monto : $monto,
                        'monto_absoluto' => $monto,
                        'tipo' => $tipo,
                        'banco' => 'BANAMEX',
                        'saldo' => $saldo
                    ];
                }
            }
        }

        return $movimientos;
    }

    /**
     * Extractor genérico para PDFs
     */
    protected function extraerMovimientosGenericoPdf(string $contenido, ?string $banco): array
    {
        // Esta es una implementación básica que busca fechas seguidas de montos al final de la línea
        $movimientos = [];
        $lineas = explode("\n", $contenido);
        
        // Regex para capturar: FECHA (Cualquier formato) ... MONTO (al final)
        $pattern = '/^(\d{1,2}[-\/\s](?:[a-zA-Z]{3}|\d{1,2})[-\/\s]\d{2,4}).*?\s+([\d,.]+)$/';

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            if (preg_match($pattern, $linea, $matches)) {
                $monto = $this->parsearMonto($matches[2]);
                if ($monto == 0 || $monto > 10000000) continue; // Evitar capturar números de cuenta o saldos totales

                $movimientos[] = [
                    'fecha' => $this->parsearFecha($matches[1], ['d/m/Y', 'd-m-Y', 'd M Y', 'Y-m-d'])?->format('Y-m-d'),
                    'concepto' => substr($linea, 0, 100), // Usamos parte de la línea como concepto
                    'referencia' => '',
                    'monto' => $monto,
                    'monto_absoluto' => abs($monto),
                    'tipo' => $monto >= 0 ? 'deposito' : 'retiro',
                    'banco' => $banco ?? 'PDF',
                ];
            }
        }

        return $movimientos;
    }

    /**
     * Parsear usando Inteligencia Artificial (Gemini) como fallback cuando los métodos tradicionales fallan
     */
    public function parsearConIa(string $texto, ?string $banco = null): array
    {
        try {
            if (!$this->gemini->isAvailable()) {
                throw new \Exception("El servicio de IA de Gemini no está configurado.");
            }

            // Comprimir espacios en blanco consecutivos y saltos de línea para ahorrar 80%+ de tokens y evitar timeouts
            $texto = preg_replace('/[ \t]+/', ' ', $texto);
            $texto = preg_replace('/\n+/', "\n", $texto);
            $texto = trim($texto);

            // Limitar texto denso para evitar tokens excesivos y acelerar la velocidad de respuesta de la IA
            $textoLimitado = mb_substr($texto, 0, 8000);

            $systemPrompt = "Eres un Asistente IA de Contabilidad experto en extraer estados de cuenta bancarios en México.
Tu tarea es leer los datos en texto sin formato (provenientes de un CSV, Excel o PDF) y extraer una lista estructurada de las transacciones financieras principales (limítate a un máximo de las primeras 40 transacciones para evitar timeouts).

INSTRUCCIONES CRÍTICAS:
1. Extrae un máximo de las primeras 40 transacciones que encuentres en el texto.
2. Cada transacción debe incluir:
   - fecha: En formato Y-m-d (ej: 2026-05-18). Si viene en otro formato o con nombres de mes en español (Ene, Feb, etc.), tradúcelo a Y-m-d.
   - concepto: Descripción detallada o concepto del movimiento bancario.
   - referencia: Folio, número de autorización, de referencia o de movimiento, si existe. Si no, déjalo vacío.
   - monto: Número float (positivo para depósitos o abonos, negativo para retiros, cargos o pagos). Asegúrate de respetar el signo.
   - saldo: Saldo restante después del movimiento (float o null si no se puede leer).
   - tipo: Debe ser 'deposito' si el monto es positivo (entrada) o 'retiro' si es negativo (salida).
   - banco: El banco de origen (ej: 'BBVA', 'BANAMEX', 'BANORTE', 'SANTANDER' u 'Otro').
3. Llama a la función 'extract_bank_statement_transactions' con los resultados.";

            $userPrompt = "Contenido del estado de cuenta bancario:\n\n" . $textoLimitado;

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ];

            $tools = [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'extract_bank_statement_transactions',
                        'description' => 'Extrae movimientos estructurados a partir del texto de un estado de cuenta bancario.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'banco' => [
                                    'type' => 'STRING',
                                    'description' => 'Nombre del banco detectado (ej: BBVA, BANAMEX, BANORTE, SANTANDER).'
                                ],
                                'transactions' => [
                                    'type' => 'ARRAY',
                                    'items' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'fecha' => [
                                                'type' => 'STRING',
                                                'description' => 'Fecha del movimiento en formato Y-m-d.'
                                            ],
                                            'concepto' => [
                                                'type' => 'STRING',
                                                'description' => 'Concepto o descripción del movimiento.'
                                            ],
                                            'referencia' => [
                                                'type' => 'STRING',
                                                'description' => 'Número de autorización, referencia o vacío.'
                                            ],
                                            'monto' => [
                                                'type' => 'NUMBER',
                                                'description' => 'Monto float. Positivo para abono/depósito, negativo para cargo/retiro.'
                                            ],
                                            'saldo' => [
                                                'type' => 'NUMBER',
                                                'description' => 'Saldo resultante o null.'
                                            ],
                                            'tipo' => [
                                                'type' => 'STRING',
                                                'description' => 'deposito o retiro.'
                                            ]
                                        ],
                                        'required' => ['fecha', 'concepto', 'monto', 'tipo']
                                    ]
                                ]
                            ],
                            'required' => ['banco', 'transactions']
                        ]
                    ]
                ]
            ];

            $response = $this->gemini->chat($messages, $tools);

            if ($response['success']) {
                $toolCall = $response['message']['tool_calls'][0] ?? null;
                if ($toolCall) {
                    $args = is_array($toolCall['function']['arguments'])
                        ? $toolCall['function']['arguments']
                        : json_decode($toolCall['function']['arguments'], true);

                    $bancoDetectado = $args['banco'] ?? ($banco ?? 'Otro');
                    $transacciones = $args['transactions'] ?? [];

                    $movimientos = [];
                    foreach ($transacciones as $t) {
                        $movimientos[] = [
                            'fecha' => $t['fecha'],
                            'concepto' => $t['concepto'],
                            'referencia' => $t['referencia'] ?? '',
                            'monto' => (float) $t['monto'],
                            'monto_absoluto' => abs((float) $t['monto']),
                            'saldo' => isset($t['saldo']) ? (float) $t['saldo'] : null,
                            'tipo' => $t['tipo'] === 'deposito' ? 'deposito' : 'retiro',
                            'banco' => $bancoDetectado,
                        ];
                    }

                    return [
                        'banco' => $bancoDetectado,
                        'total_movimientos' => count($movimientos),
                        'movimientos' => $movimientos,
                        'metodo' => 'AI (Gemini)',
                    ];
                }
            }

            throw new \Exception("La IA no devolvió un formato estructurado de transacciones.");
        } catch (\Exception $e) {
            Log::error("Error parseando estado de cuenta con IA: " . $e->getMessage());
            throw $e;
        }
    }

}
