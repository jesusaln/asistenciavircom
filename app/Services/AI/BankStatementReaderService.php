<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class BankStatementReaderService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Extrae todos los movimientos de un estado de cuenta bancario en PDF usando Gemini Multimodal
     *
     * @param string $pdfPath Ruta física al archivo PDF
     * @param string|null $password Contraseña de apertura si el PDF está cifrado
     * @return array Array estructurado con los datos del banco y sus movimientos
     */
    public function extractStatement(string $pdfPath, ?string $password = null): array
    {
        if (!File::exists($pdfPath)) {
            throw new \Exception("El archivo PDF del estado de cuenta no existe en la ruta: {$pdfPath}");
        }

        $workingPdfPath = $pdfPath;
        $tempUnlockedPath = null;

        // Desbloqueo de contraseña usando Ghostscript si se proporcionó una clave
        if (!empty($password)) {
            $tempUnlockedPath = storage_path('app/temp_bank_' . uniqid() . '.pdf');
            $gsCommand = sprintf(
                'gs -sDEVICE=pdfwrite -sOutputFile=%s -sPDFPassword=%s -dNOPAUSE -dBATCH -dQUIET %s 2>&1',
                escapeshellarg($tempUnlockedPath),
                escapeshellarg($password),
                escapeshellarg($pdfPath)
            );

            exec($gsCommand, $output, $returnCode);

            if ($returnCode === 0 && File::exists($tempUnlockedPath) && File::size($tempUnlockedPath) > 100) {
                $workingPdfPath = $tempUnlockedPath;
            } else {
                if (File::exists($tempUnlockedPath)) File::delete($tempUnlockedPath);
                throw new \Exception("No se pudo desbloquear el PDF. Verifica que la contraseña sea correcta. Detalles: " . implode(" ", $output));
            }
        }

        // Leer el contenido binario del PDF y codificarlo en Base64
        $pdfData = base64_encode(File::get($workingPdfPath));
        
        // Limpiar archivo desbloqueado temporal si se creó
        if ($tempUnlockedPath && File::exists($tempUnlockedPath)) {
            File::delete($tempUnlockedPath);
        }

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'banco_nombre' => ['type' => 'STRING', 'description' => 'Nombre completo o comercial del banco (ej. BBVA BANCOMER, BANORTE, SANTANDER, BANAMEX, HSBC, SCOTIABANK)'],
                'cuenta_numero' => ['type' => 'STRING', 'description' => 'Número de cuenta, CLABE o tarjeta (al menos los últimos 4 dígitos detectados)'],
                'titular_nombre' => ['type' => 'STRING', 'description' => 'Nombre de la empresa o persona titular de la cuenta bancaria'],
                'titular_rfc' => ['type' => 'STRING', 'description' => 'RFC del titular de la cuenta si aparece'],
                'periodo_anio_mes' => ['type' => 'STRING', 'description' => 'Periodo contable del estado de cuenta en formato YYYY-MM (ej. 2026-05)'],
                'moneda' => ['type' => 'STRING', 'description' => 'Moneda de la cuenta bancaria (ej. MXN o USD)'],
                'saldo_inicial' => ['type' => 'NUMBER', 'description' => 'Saldo inicial de la cuenta al comienzo del periodo'],
                'saldo_final' => ['type' => 'NUMBER', 'description' => 'Saldo final de la cuenta al corte del periodo'],
                'total_cargos' => ['type' => 'NUMBER', 'description' => 'Monto total sumado de los cargos o retiros del periodo'],
                'total_abonos' => ['type' => 'NUMBER', 'description' => 'Monto total sumado de los abonos o depósitos del periodo'],
                'movimientos' => [
                    'type' => 'ARRAY',
                    'description' => 'Lista cronológica exhaustiva de TODOS los movimientos bancarios que aparecen en las tablas del PDF sin omitir ninguno.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'fecha' => ['type' => 'STRING', 'description' => 'Fecha de operación del movimiento en formato estándar YYYY-MM-DD'],
                            'concepto' => ['type' => 'STRING', 'description' => 'Descripción o concepto completo tal como aparece en el banco (ej. SPEI ENVIADO LG ELECTRONICS MEXICO, COMISION POR MENSUALIDAD, PAGO DE INTERESES)'],
                            'referencia' => ['type' => 'STRING', 'description' => 'Número de referencia, autorización, clave de rastreo o folio del movimiento'],
                            'tipo' => ['type' => 'STRING', 'description' => "Valor exacto: 'cargo' si es un retiro o salida de dinero de la cuenta, 'abono' si es un depósito o entrada de dinero a la cuenta"],
                            'monto' => ['type' => 'NUMBER', 'description' => 'Monto exacto del movimiento bancario (número positivo decimal sin formato de moneda)'],
                            'saldo_posterior' => ['type' => 'NUMBER', 'description' => 'Saldo contable en la cuenta bancaria inmediatamente después de este movimiento si el PDF lo desglosa renglón por renglón']
                        ],
                        'required' => ['fecha', 'concepto', 'tipo', 'monto']
                    ]
                ]
            ],
            'required' => ['banco_nombre', 'periodo_anio_mes', 'saldo_inicial', 'saldo_final', 'movimientos']
        ];

        $jsonTemplate = json_encode([
            "banco_nombre" => "BANAMEX / BANORTE / BBVA",
            "cuenta_numero" => "1234",
            "titular_nombre" => "Empresa S.A.",
            "titular_rfc" => "EMP000101XYZ",
            "periodo_anio_mes" => "2026-04",
            "moneda" => "MXN",
            "saldo_inicial" => 214978.12,
            "saldo_final" => 249488.16,
            "total_cargos" => 15000.50,
            "total_abonos" => 49510.54,
            "movimientos" => [
                [
                    "fecha" => "2026-04-01",
                    "concepto" => "SPEI ENVIADO PROVEEDOR XYZ",
                    "referencia" => "REF0001",
                    "tipo" => "cargo",
                    "monto" => 5000.00,
                    "saldo_posterior" => 209978.12
                ],
                [
                    "fecha" => "2026-04-02",
                    "concepto" => "DEP EN EFECTIVO SUCURSAL",
                    "referencia" => "DEP099",
                    "tipo" => "abono",
                    "monto" => 12500.00,
                    "saldo_posterior" => 222478.12
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = "Eres un Auditor Financiero Experto y Lector Multimodal de Inteligencia Artificial de alto nivel.
Se te ha entregado un documento PDF que representa un Estado de Cuenta Bancario comercial o empresarial de un banco en México.

Tu misión es analizar visual y textualmente todas las páginas del documento y extraer con precisión milimétrica su información general y todos sus movimientos bancarios renglón por renglón.

REGLAS DE EXTRACCIÓN CRÍTICAS (OPTIMIZACIÓN DE TOKENS):
1. Extrae absolutamente TODOS los cargos (retiros) y abonos (depósitos) del periodo.
2. Identifica correctamente si la columna es un 'cargo' (salida de dinero) o un 'abono' (entrada de dinero).
3. SINTETIZA EL CONCEPTO: Limpia el texto del concepto eliminando números de cuenta repetidos, domiciliaciones largas y códigos internos bancarios innecesarios (ej. transforma 'PAGO RECIBIDO DE AGROPECUARIA Y SERVICIOS LAS M SU REF.000019 COMPACJESUS' en 'AGROPECUARIA Y SERVICIOS LAS M').
4. OMITE la propiedad 'saldo_posterior' en cada ítem para economizar un 30% de tokens en el JSON de salida.
5. Fechas en formato estándar YYYY-MM-DD.

IMPORTANTE: Devuelve tu respuesta ÚNICA Y EXCLUSIVAMENTE como un bloque de código JSON válido y completo, siguiendo exactamente esta estructura:
```json
{$jsonTemplate}
```
No incluyas ningún texto introductorio, saludos ni explicaciones. Solo el bloque JSON puro. Asegúrate de procesar el estado de cuenta completo hasta el final.";

        $messages = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inlineData' => [
                            'mimeType' => 'application/pdf',
                            'data' => $pdfData
                        ]
                    ]
                ]
            ]
        ];

        // Ejecutar en modo de generación de texto libre (aprovechando los 8,192 tokens completos)
        $res = $this->gemini->chat($messages, []);
        \Illuminate\Support\Facades\Log::info('BankReader Pure JSON Response:', ['res' => $res]);

        if (!$res['success']) {
            throw new \Exception($res['error'] ?? "Error procesando el PDF con los servidores de IA de Gemini.");
        }

        $content = $res['message']['content'] ?? '';
        $data = null;

        // Extraer y decodificar el JSON del contenido de texto
        if (str_contains($content, '{')) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($end !== false && $end > $start) {
                $jsonStr = substr($content, $start, $end - $start + 1);
                $data = json_decode($jsonStr, true);
            }
            
            // Reparación de emergencia: Si el estado de cuenta era colosal y se cortó al final de la lista
            if (empty($data) && str_contains($content, '"movimientos"')) {
                $cleanStr = substr($content, $start);
                // Buscar el último movimiento que se haya cerrado completamente con '}'
                $lastClosedObj = strrpos($cleanStr, '}');
                if ($lastClosedObj !== false) {
                    $repairedJson = substr($cleanStr, 0, $lastClosedObj + 1) . "\n    ]\n}";
                    $data = json_decode($repairedJson, true);
                }
            }
        }

        if (empty($data) || empty($data['movimientos'])) {
            $errSnippet = substr($content, 0, 300);
            throw new \Exception("La IA no pudo estructurar todos los movimientos bancarios correctamente. Respuesta parcial: {$errSnippet}");
        }

        return $data;
    }
}
