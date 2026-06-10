<?php

namespace App\Services\Contab;

use App\Models\Contab\CuentaContable;
use App\Models\Contab\RfcMapping;
use App\Services\AI\GeminiService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AICategorizationService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public string $lastError = '';

    /**
     * Categoriza un CFDI de gasto de forma semántica usando Gemini y guarda el mapeo.
     *
     * @param array $data Datos del XML parseados (emisor, conceptos, total, etc.)
     * @param int $empresaId ID de la empresa
     * @return CuentaContable|null
     */
    public function categorizeExpense(array $data, int $empresaId): ?CuentaContable
    {
        $this->lastError = '';
        $emisorRfc = $data['emisor']['rfc'] ?? '';
        $emisorNombre = $data['emisor']['nombre'] ?? '';
        $conceptos = $data['conceptos'] ?? [];

        if (empty($emisorRfc)) {
            $this->lastError = 'RFC de emisor vacío.';
            return null;
        }

        // 1. Verificar si ya existe en caché local de BD y es una cuenta de gastos/costos/activos elegible (no proveedores/clientes)
        $mapping = RfcMapping::with('cuenta')->where('empresa_id', $empresaId)->where('rfc', $emisorRfc)->first();
        if ($mapping && $mapping->cuenta_id && $mapping->cuenta) {
            $codigo = $mapping->cuenta->codigo;
            $esElegible = $mapping->cuenta->tipo === 'egreso' || 
                          str_starts_with($codigo, '5') || 
                          str_starts_with($codigo, '6') || 
                          str_starts_with($codigo, '7') || 
                          str_starts_with($codigo, '115') || 
                          str_starts_with($codigo, '120');
            if ($esElegible) {
                return $mapping->cuenta;
            }
        }

        if (!$this->gemini->isAvailable()) {
            $this->lastError = 'Gemini no está configurado.';
            Log::warning('AICategorization: Gemini 2.5 Flash no está disponible para clasificar el RFC ' . $emisorRfc);
            return null;
        }

        // 2. Asegurar cuentas maestras esenciales para la categorización en esta empresa
        $cuentasMaestras = [
            ['codigo' => '115.01', 'nombre' => 'Almacén e Inventario de Equipos Minisplits', 'tipo' => 'activo', 'naturaleza' => 'deudora'],
            ['codigo' => '501.01', 'nombre' => 'Costo de Venta de Equipos y Componentes', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.01', 'nombre' => 'Gastos Generales de Operación', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.02', 'nombre' => 'Sueldos, Salarios y Nómina', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.03', 'nombre' => 'Combustibles, Gasolina y Lubricantes', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.04', 'nombre' => 'Papelería, Copias y Artículos de Oficina', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.05', 'nombre' => 'Mantenimiento y Conservación de Equipos', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.06', 'nombre' => 'Honorarios y Servicios Profesionales', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.07', 'nombre' => 'Impuestos y Derechos Contables', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.08', 'nombre' => 'Viáticos, Hospedaje y Gastos de Viaje', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '601.09', 'nombre' => 'Publicidad y Mercadotecnia', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '602.01', 'nombre' => 'Gastos de Vehículos y Seguros', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
            ['codigo' => '701.01', 'nombre' => 'Gastos Financieros y Comisiones Bancarias', 'tipo' => 'egreso', 'naturaleza' => 'deudora'],
        ];

        foreach ($cuentasMaestras as $cta) {
            CuentaContable::firstOrCreate(
                ['empresa_id' => $empresaId, 'codigo' => $cta['codigo']],
                ['nombre' => $cta['nombre'], 'tipo' => $cta['tipo'], 'naturaleza' => $cta['naturaleza'], 'es_detalle' => true, 'nivel' => 2]
            );
        }

        // Obtener catálogo de cuentas de egreso/costos/activos de la empresa sin restringir es_detalle
        $cuentas = CuentaContable::where('empresa_id', $empresaId)
            ->whereIn('tipo', ['egreso', 'activo']) // Activo por si hay compras de inventario/herramienta
            ->where(function($q) {
                $q->where('codigo', 'like', '5%')      // Costos
                  ->orWhere('codigo', 'like', '6%')    // Gastos Generales / Operativos
                  ->orWhere('codigo', 'like', '7%')    // Gastos Financieros / Otros
                  ->orWhere('codigo', 'like', '115%')  // Inventario / Almacén
                  ->orWhere('codigo', 'like', '120%'); // Activo Fijo / Herramientas
            })
            ->get(['id', 'codigo', 'nombre', 'tipo']);

        if ($cuentas->isEmpty()) {
            $this->lastError = 'Catálogo de cuentas vacío para la empresa ' . $empresaId;
            return null;
        }

        $catalogoTxt = $cuentas->map(function ($c) {
            return "- [{$c->codigo}] {$c->nombre} ({$c->tipo})";
        })->implode("\n");

        // 3. Preparar los datos del CFDI para el prompt
        $conceptosTxt = collect($conceptos)->map(function ($c) {
            $desc = $c['descripcion'] ?? '';
            $clave = $c['claveProdServ'] ?? '';
            $importe = $c['importe'] ?? 0;
            return "  * Concepto: \"{$desc}\" (Clave SAT: {$clave}) - Importe: \${$importe}";
        })->implode("\n");

        // 4. Obtener contexto de historial contable de la empresa (Pólizas y Mapeos pasados)
        $historialMapeos = RfcMapping::with('cuenta')
            ->where('empresa_id', $empresaId)
            ->whereNotNull('cuenta_id')
            ->orderBy('updated_at', 'desc')
            ->take(8)
            ->get();

        $historialTxt = $historialMapeos->map(function ($m) {
            $nombreCta = $m->cuenta ? "[{$m->cuenta->codigo}] {$m->cuenta->nombre}" : "Cuenta Desconocida";
            return " - Emisor: {$m->nombre_auxiliar} (RFC: {$m->rfc}) -> Clasificado en: {$nombreCta}";
        })->implode("\n");

        if (empty($historialTxt)) {
            $historialTxt = " (No hay mapeos previos registrados)";
        }

        $systemPrompt = "Eres un Contador Fiscal de alto nivel y Auditor Contable Autónomo en México.
Tu objetivo es analizar los datos de una factura de compra (CFDI) recibida por la empresa y determinar la cuenta contable de contrapartida (Costo/Gasto/Activo) del catálogo, o CREAR UNA NUEVA si es indispensable.

REGLAS DE CLASIFICACIÓN, SEGURIDAD Y AUTONOMÍA (¡CRÍTICAS!):
1. NUNCA, BAJO NINGUNA CIRCUNSTANCIA, sugieras o crees cuentas de Clientes (típicamente código 105) o Proveedores/Acreedores (típicamente código 201 o 202). Estas cuentas representan saldos por pagar/cobrar de balance y ya son manejadas de forma automática por el sistema en el lado opuesto del asiento contable. Tu objetivo es encontrar la cuenta de destino del gasto/compra (gasto, costo o inventario).
2. Las cuentas sugeridas o creadas deben pertenecer estrictamente a las siguientes categorías:
   - Costo de Ventas (grupo 501 / 500-599)
   - Gastos Generales, Operativos o de Administración (grupo 601, 602, 603 / 600-699)
   - Gastos Financieros (grupo 701)
   - Almacén e Inventarios (grupo 115)
   - Activo Fijo o Herramientas (grupo 120-125)
3. Analiza semánticamente el nombre del emisor y cada concepto del comprobante (junto con su ClaveProdServ del SAT).
4. Si es combustible (gasolina, lubricantes, diésel), asigna o crea una subcuenta dentro de Combustibles y Lubricantes (usualmente 601.03 o similar).
5. Si son refacciones, partes, herramientas de refrigeración o equipos de aire acondicionado para reventa, asigna Almacén/Inventario (115) o Costo de Venta (501).
6. Si son herramientas o equipo de trabajo duradero, usa Activo Fijo (120-125).
7. Si son servicios de software, licencias, nube, hosting o herramientas digitales (ej. GitHub, OpenAI, AWS, Google Cloud, Microsoft), busca la cuenta de Suscripciones, Licencias o Software, o crea una nueva si no existe (dentro de Gastos 601).
8. Si el catálogo actual no tiene una cuenta idónea para este rubro (ej. 'Seguros de Vehículos', 'Capacitación', 'Suscripciones y Software'), tienes la AUTORIDAD de usar la herramienta 'create_accounting_account' para abrir una nueva subcuenta dentro del grupo correcto (ej. 601.xx o 602.xx) siguiendo el formato del catálogo.
9. Si una cuenta existente refleja el gasto con precisión, usa obligatoriamente 'assign_accounting_account'.";

        $userPrompt = "EMISOR: RFC: {$emisorRfc} | Nombre: {$emisorNombre}
CONCEPTOS DEL CFDI:
{$conceptosTxt}

PATRONES HISTÓRICOS DE CLASIFICACIÓN DE ESTA EMPRESA:
{$historialTxt}

CATÁLOGO DE CUENTAS DISPONIBLE:
{$catalogoTxt}

Analiza el comprobante. Si existe una cuenta idónea, llama a 'assign_accounting_account'. Si es un rubro nuevo sin cuenta clara, llama a 'create_accounting_account' con tu propuesta de código y nombre formal.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'assign_accounting_account',
                    'description' => 'Asigna una cuenta contable existente idónea para la factura analizada.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'codigo_cuenta' => [
                                'type' => 'STRING',
                                'description' => 'El código exacto de la cuenta contable elegida del catálogo (ej. 601.03).'
                            ],
                            'nombre_cuenta' => [
                                'type' => 'STRING',
                                'description' => 'El nombre exacto de la cuenta elegida.'
                            ],
                            'razonamiento' => [
                                'type' => 'STRING',
                                'description' => 'Breve justificación fiscal contable de por qué se asigna esta cuenta.'
                            ],
                        ],
                        'required' => ['codigo_cuenta', 'nombre_cuenta', 'razonamiento']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_accounting_account',
                    'description' => 'Crea una nueva subcuenta contable en el catálogo si ninguna existente refleja con precisión el rubro del gasto.',
                    'parameters' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'codigo_padre' => [
                                'type' => 'STRING',
                                'description' => 'Código del grupo o cuenta de mayor (ej. 601 para Gastos Generales, 501 para Costo de Venta, 602 para Mantenimiento).'
                            ],
                            'nuevo_codigo_propuesto' => [
                                'type' => 'STRING',
                                'description' => 'El código exacto para la nueva subcuenta (ej. 601.25 o 602.15).'
                            ],
                            'nombre_nueva_cuenta' => [
                                'type' => 'STRING',
                                'description' => 'El nombre formal y profesional de la nueva cuenta (ej. Suscripciones de Software y Servicios Nube).'
                            ],
                            'tipo' => [
                                'type' => 'STRING',
                                'description' => 'Tipo contable: egreso, activo, o pasivo.'
                            ],
                            'naturaleza' => [
                                'type' => 'STRING',
                                'description' => 'Naturaleza contable: deudora o acreedora.'
                            ],
                            'razonamiento' => [
                                'type' => 'STRING',
                                'description' => 'Justificación fiscal y de orden administrativo de por qué se abre esta nueva partida en la empresa.'
                            ],
                        ],
                        'required' => ['codigo_padre', 'nuevo_codigo_propuesto', 'nombre_nueva_cuenta', 'tipo', 'naturaleza', 'razonamiento']
                    ]
                ]
            ]
        ];

        try {
            $response = $this->gemini->chat($messages, $tools);
            Log::info('AICategorization Response: ', ['response' => $response]);

            if ($response['success']) {
                $toolCall = $response['message']['tool_calls'][0] ?? null;

                if ($toolCall) {
                    $funcName = $toolCall['function']['name'] ?? '';
                    $args = is_array($toolCall['function']['arguments']) 
                        ? $toolCall['function']['arguments'] 
                        : json_decode($toolCall['function']['arguments'], true);

                    if ($funcName === 'create_accounting_account') {
                        $nuevoCodigo = $args['nuevo_codigo_propuesto'] ?? '';
                        $nuevoNombre = $args['nombre_nueva_cuenta'] ?? '';
                        $tipoCta = $args['tipo'] ?? 'egreso';
                        $naturalezaCta = $args['naturaleza'] ?? 'deudora';
                        $razonamiento = $args['razonamiento'] ?? '';

                        if ($nuevoCodigo && $nuevoNombre) {
                            $cuenta = CuentaContable::firstOrCreate(
                                ['empresa_id' => $empresaId, 'codigo' => $nuevoCodigo],
                                [
                                    'nombre' => $nuevoNombre,
                                    'tipo' => $tipoCta,
                                    'naturaleza' => $naturalezaCta,
                                    'nivel' => 2,
                                    'es_detalle' => true
                                ]
                            );

                            // Guardar en caché el mapeo
                            RfcMapping::updateOrCreate(
                                ['empresa_id' => $empresaId, 'rfc' => $emisorRfc],
                                [
                                    'cuenta_id' => $cuenta->id,
                                    'nombre_auxiliar' => $emisorNombre ? substr($emisorNombre, 0, 100) : null,
                                    'ai_reasoning' => "[CUENTA CREADA POR IA] " . $razonamiento
                                ]
                            );

                            Log::info("AICategorization: Cuenta Autónoma CREADA {$nuevoCodigo} - {$nuevoNombre} para RFC {$emisorRfc}. Razonamiento: {$razonamiento}");
                            return $cuenta;
                        }
                    } elseif ($funcName === 'assign_accounting_account') {
                        $codigoElegido = $args['codigo_cuenta'] ?? '';
                        $razonamiento = $args['razonamiento'] ?? '';

                        if ($codigoElegido) {
                            $cuenta = $cuentas->firstWhere('codigo', $codigoElegido);

                            if ($cuenta) {
                                RfcMapping::updateOrCreate(
                                    ['empresa_id' => $empresaId, 'rfc' => $emisorRfc],
                                    [
                                        'cuenta_id' => $cuenta->id,
                                        'nombre_auxiliar' => $emisorNombre ? substr($emisorNombre, 0, 100) : null,
                                        'ai_reasoning' => $razonamiento
                                    ]
                                );

                                Log::info("AICategorization: RFC {$emisorRfc} ({$emisorNombre}) clasificado como {$codigoElegido} - {$cuenta->nombre}. Razonamiento: {$razonamiento}");
                                return $cuenta;
                            } else {
                                $this->lastError = 'Cuenta elegida ' . $codigoElegido . ' no encontrada en BD.';
                                Log::warning("AICategorization: Cuenta {$codigoElegido} sugerida por Gemini no encontrada en catálogo.");
                            }
                        }
                    }
                } else {
                    $this->lastError = 'Gemini no retornó un tool call válido.';
                    Log::warning("AICategorization: Gemini no invocó tool ni retornó JSON.", ['raw' => $response]);
                }
            } else {
                $this->lastError = $response['error'] ?? 'Falla de conexión o cuota con Gemini.';
                Log::error("AICategorization: Falla en respuesta de Gemini.", ['error' => $this->lastError]);
            }
        } catch (Throwable $e) {
            $this->lastError = 'Excepción: ' . $e->getMessage();
            Log::error('AICategorization Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return null;
    }


    /**
     * Realiza una auditoría fiscal inteligente (Rayos X) sobre las discrepancias del mes.
     *
     * @param array $breakdown Lista de discrepancias (pólizas vs xml)
     * @param array $resumen Totales (trasladado, acreditable, isr, etc.)
     * @return array
     */
    public function auditRayosX(array $breakdown, array $resumen): array
    {
        if (!$this->gemini->isAvailable()) {
            return [
                'success' => false,
                'summary_md' => '⚠️ El servicio de Inteligencia Artificial (Gemini) no está disponible o no tiene configurada su API Key.'
            ];
        }

        $discrepancias = collect($breakdown)->filter(function ($item) {
            $ivaPol = $item['tipo'] === 'Gasto' ? ($item['iva_acreditable_poliza'] ?? 0) : ($item['iva_trasladado_poliza'] ?? 0);
            $ivaXml = $item['tipo'] === 'Gasto' ? ($item['iva_acreditable_xml'] ?? 0) : ($item['iva_trasladado_xml'] ?? 0);
            return Math_abs($ivaPol - $ivaXml) > 0.05 || ($item['numero_poliza'] ?? '') === 'Falta Póliza';
        })->values()->take(25)->toArray(); // Top 25 discrepancias para no saturar tokens

        $resumenJson = json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $discrepanciasJson = json_encode($discrepancias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = "Eres un Auditor Fiscal Experto del SAT y Asesor Financiero en México.
Tu objetivo es analizar el resultado de la conciliación de IVA mensual (Pólizas Contables vs XMLs del SAT) y emitir un Resumen Ejecutivo de Alertas y Riesgos en formato Markdown.

INSTRUCCIONES DE REDACCIÓN:
1. Sé conciso, profesional y directo.
2. Identifica si hay facturas emitidas o recibidas en el SAT que no tienen póliza contable ('Falta Póliza') y resalta el riesgo fiscal (ej. deducciones no aprovechadas o ingresos omitidos).
3. Si hay diferencias en el IVA trasladado (Ventas), advierte sobre posibles discrepancias en el pago de impuestos.
4. Si hay notas de crédito o devoluciones, indica si se están aplicando correctamente.
5. Utiliza formato Markdown limpio con viñetas, negritas y emojis profesionales (⚠️, 💡, 📊, 🚨). No uses saludos ni introducciones largas.";

        $userPrompt = "DATOS DEL MES (RESUMEN FINANCIERO):
{$resumenJson}

PRINCIPALES DISCREPANCIAS DETECTADAS (PÓLIZA VS XML):
{$discrepanciasJson}

Por favor, redacta el diagnóstico fiscal predictivo en Markdown.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        try {
            $response = $this->gemini->chat($messages);

            if ($response['success']) {
                $content = $response['message']['content'] ?? '';
                return [
                    'success' => true,
                    'summary_md' => $content
                ];
            }
        } catch (Throwable $e) {
            Log::error('AIAuditRayosX Exception: ' . $e->getMessage());
        }

        return [
            'success' => false,
            'summary_md' => '❌ Ocurrió un error al generar el diagnóstico de auditoría fiscal.'
        ];
    }
}

function Math_abs($num) {
    return abs($num);
}
