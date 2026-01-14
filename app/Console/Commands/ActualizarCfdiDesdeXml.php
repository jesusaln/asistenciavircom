<?php

namespace App\Console\Commands;

use App\Models\Cfdi;
use App\Services\CfdiXmlParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ActualizarCfdiDesdeXml extends Command
{
    protected $signature = 'cfdi:actualizar-desde-xml {--limit=100 : Límite de CFDIs a procesar}';
    protected $description = 'Actualiza los campos de receptor y otros datos del CFDI leyendo el XML almacenado';

    public function handle(CfdiXmlParserService $parser): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Buscando CFDIs que necesitan actualización...");
        
        // Obtener CFDIs que tienen xml_url o xml_content (para poder parsear)
        $cfdis = Cfdi::where(function ($q) {
                $q->whereNotNull('xml_url')
                  ->orWhereNotNull('xml_content');
            })
            ->limit($limit)
            ->get();
        
        $this->info("Encontrados {$cfdis->count()} CFDIs para procesar.");
        
        $bar = $this->output->createProgressBar($cfdis->count());
        $bar->start();
        
        $actualizados = 0;
        $errores = 0;
        
        foreach ($cfdis as $cfdi) {
            try {
                $xmlContent = $this->obtenerXmlContent($cfdi);
                
                if (!$xmlContent) {
                    $errores++;
                    $bar->advance();
                    continue;
                }
                
                $data = $parser->parseCfdiXml($xmlContent);
                
                // Actualizar datos_adicionales con info completa del receptor
                $datosAdicionales = $cfdi->datos_adicionales ?? [];
                $datosAdicionales['receptor'] = $data['receptor'] ?? null;
                $datosAdicionales['emisor'] = $data['emisor'] ?? null;
                $datosAdicionales['lugar_expedicion'] = $data['lugar_expedicion'] ?? null;
                $datosAdicionales['conceptos_count'] = count($data['conceptos'] ?? []);
                
                $cfdi->update(['datos_adicionales' => $datosAdicionales]);
                $actualizados++;
                
            } catch (\Exception $e) {
                Log::warning("Error actualizando CFDI {$cfdi->uuid}: " . $e->getMessage());
                $errores++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Proceso completado:");
        $this->info("   - Actualizados: {$actualizados}");
        $this->warn("   - Errores: {$errores}");
        
        return Command::SUCCESS;
    }
    
    private function obtenerXmlContent(Cfdi $cfdi): ?string
    {
        // Primero intentar desde xml_content
        if (!empty($cfdi->xml_content)) {
            return $cfdi->xml_content;
        }
        
        // Luego desde xml_url
        if (!empty($cfdi->xml_url)) {
            $path = $cfdi->xml_url;
            
            // Buscar en storage local
            if (Storage::exists($path)) {
                return Storage::get($path);
            }
            
            // Buscar en storage público
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->get($path);
            }
        }
        
        // Intentar rutas posibles por UUID
        $possiblePaths = [
            'cfdis/xml/' . $cfdi->uuid . '.xml',
            'cfdis/' . $cfdi->uuid . '.xml',
        ];
        
        // Agregar rutas organizadas por fecha
        if ($cfdi->fecha_emision) {
            $year = $cfdi->fecha_emision->format('Y');
            $month = $cfdi->fecha_emision->format('m');
            $tipo = $cfdi->direccion === 'recibido' ? 'recibidos' : 'emitidos';
            $possiblePaths[] = "cfdis/{$tipo}/{$year}/{$month}/{$cfdi->uuid}.xml";
        }
        
        foreach ($possiblePaths as $path) {
            if (Storage::exists($path)) {
                return Storage::get($path);
            }
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->get($path);
            }
        }
        
        return null;
    }
}
