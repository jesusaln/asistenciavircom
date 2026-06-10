<?php

namespace App\Console\Commands;

use App\Models\Contab\CuentaContable;
use App\Models\Contab\RfcMapping;
use App\Models\Empresa;
use App\Services\Contab\AICategorizationService;
use Illuminate\Console\Command;

class TestAICategorization extends Command
{
    protected $signature = 'test:ai-categorization {--empresa=5} {--xml=}';

    protected $description = 'Prueba en vivo la categorización automática de CFDIs usando Gemini 2.5 Flash con XMLs reales';

    public function handle(AICategorizationService $aiService)
    {
        $this->info("🚀 Iniciando prueba en vivo de Categorización AI con XML Contable Real...");

        $empresaId = (int) $this->option('empresa');
        $empresa = Empresa::find($empresaId);

        if (!$empresa) {
            $this->warn("No se encontró la empresa con ID {$empresaId}. Creando empresa de prueba temporal...");
            $empresa = Empresa::create([
                'nombre_razon_social' => 'Climas del Desierto S.A. de C.V.',
                'rfc' => 'CDD123456ABC',
                'tipo_persona' => 'moral',
                'regimen_fiscal' => '601',
                'email' => 'admin@climasdeldesierto.com',
                'telefono' => '6861234567',
                'calle' => 'Av. Reforma',
                'numero_exterior' => '1000',
                'colonia' => 'Centro',
                'codigo_postal' => '21100',
                'municipio' => 'Mexicali',
                'estado' => 'Baja California',
            ]);
        }

        $this->info("Empresa: {$empresa->nombre_razon_social} (ID: {$empresa->id})");

        // Asegurar que existan cuentas contables clave en esta empresa
        CuentaContable::firstOrCreate(
            ['empresa_id' => $empresa->id, 'codigo' => '115.01'],
            ['nombre' => 'Almacén e Inventario de Equipos Minisplits', 'tipo' => 'activo', 'naturaleza' => 'deudora', 'es_detalle' => true]
        );

        CuentaContable::firstOrCreate(
            ['empresa_id' => $empresa->id, 'codigo' => '501.01'],
            ['nombre' => 'Costo de Venta de Equipos de Aire Acondicionado', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'es_detalle' => true]
        );

        CuentaContable::firstOrCreate(
            ['empresa_id' => $empresa->id, 'codigo' => '601.03'],
            ['nombre' => 'Combustibles y Lubricantes', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'es_detalle' => true]
        );

        CuentaContable::firstOrCreate(
            ['empresa_id' => $empresa->id, 'codigo' => '601.04'],
            ['nombre' => 'Papelería y Artículos de Oficina', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'es_detalle' => true]
        );

        CuentaContable::firstOrCreate(
            ['empresa_id' => $empresa->id, 'codigo' => '701.01'],
            ['nombre' => 'Gastos Financieros y Comisiones Bancarias', 'tipo' => 'egreso', 'naturaleza' => 'deudora', 'es_detalle' => true]
        );

        $xmlPath = $this->option('xml');
        if (!$xmlPath || !file_exists($xmlPath)) {
            // Usar uno de los XMLs recibidos reales de la empresa
            $xmlPath = storage_path('app/public/cfdis/recibidos/2026/05/61b3e39b-bf9a-4f7f-9010-ef3ceba1911f.xml');
            if (!file_exists($xmlPath)) {
                $this->error("No se encontró el XML real en: {$xmlPath}");
                return;
            }
        }

        $this->info("\nParseando archivo XML contable real: " . basename($xmlPath));
        $xmlContent = simplexml_load_file($xmlPath);
        $namespaces = $xmlContent->getNamespaces(true);
        $xmlContent->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4');

        $emisorNode = $xmlContent->xpath('//cfdi:Emisor')[0] ?? null;
        $conceptosNodes = $xmlContent->xpath('//cfdi:Concepto') ?? [];

        if (!$emisorNode) {
            $this->error("El XML no contiene nodo Emisor válido.");
            return;
        }

        $emisorRfc = (string) $emisorNode['Rfc'];
        $emisorNombre = (string) $emisorNode['Nombre'];

        // Limpiar posible caché en RfcMapping para forzar el análisis experto de Gemini
        RfcMapping::where('empresa_id', $empresa->id)->where('rfc', $emisorRfc)->delete();

        $conceptos = [];
        $totalImporte = 0;
        foreach ($conceptosNodes as $node) {
            $desc = (string) $node['Descripcion'];
            $importe = (float) $node['Importe'];
            $clave = (string) $node['ClaveProdServ'];
            $conceptos[] = [
                'descripcion' => $desc,
                'claveProdServ' => $clave,
                'importe' => $importe
            ];
            $totalImporte += $importe;
            $this->line(" 📦 Concepto: \"{$desc}\" (Clave: {$clave}) -> $ " . number_format($importe, 2));
        }

        $cfdiData = [
            'emisor' => [
                'rfc' => $emisorRfc,
                'nombre' => $emisorNombre
            ],
            'conceptos' => $conceptos,
            'total' => $totalImporte
        ];

        $this->info("\nEnviando datos del XML de [{$emisorNombre} - {$emisorRfc}] a Gemini 2.5 Flash...");
        
        $startTime = microtime(true);
        $cuenta = $aiService->categorizeExpense($cfdiData, $empresa->id);
        $duration = round((microtime(true) - $startTime) * 1000);

        if ($cuenta) {
            $this->info("\n🎉 ¡ÉXITO! Gemini clasificó el XML de Gasto/Compra correctamente en {$duration} ms.");
            $this->line("Cuenta Asignada: [{$cuenta->codigo}] {$cuenta->nombre} ({$cuenta->tipo})");

            // Verificar en BD el mapping y razonamiento
            $mapping = RfcMapping::where('empresa_id', $empresa->id)->where('rfc', $emisorRfc)->first();
            if ($mapping) {
                $this->comment("Razonamiento experto guardado en BD: \"{$mapping->ai_reasoning}\"");
            }
        } else {
            $this->warn("\n⚠️ La IA no devolvió un resultado para este XML.");
            $this->error("Motivo / Error: " . $aiService->lastError);
        }
    }
}
