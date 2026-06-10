<?php

namespace App\Console\Commands;

use App\Models\Cfdi;
use App\Models\Empresa;
use App\Services\Contab\ContabilidadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConciliarCsvCommand extends Command
{
    protected $signature = 'contabilidad:conciliar-csv {file} {empresa_id} {user_id=1}';
    protected $description = 'Procesa un CSV de movimientos bancarios e integra los pagos encontrados creando pólizas de ingreso';

    public function handle()
    {
        $filePath = $this->argument('file');
        $empresaId = (int) $this->argument('empresa_id');
        $userId = (int) $this->argument('user_id');

        if (!file_exists($filePath)) {
            $this->error("El archivo no existe: $filePath");
            return 1;
        }

        $this->info("Procesando archivo: $filePath para empresa: $empresaId");

        $content = file_get_contents($filePath);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        if (substr($content, 0, 3) === "\xEF\xBB\xBF") $content = substr($content, 3);
        
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_filter($lines, fn($l) => trim($l) !== '');
        
        if (empty($lines)) {
            $this->error("Archivo vacío.");
            return 1;
        }

        $header = str_getcsv(array_shift($lines), ',');
        $idxConcepto = null;
        $idxDeposito = null;
        
        foreach ($header as $i => $h) {
            $h = trim(strtolower($h));
            if (in_array($h, ['concepto', 'descripcion', 'detalle', 'concept', 'descripción', 'observaciones', 'motivo'])) $idxConcepto = $i;
            if (in_array($h, ['depositos', 'depósito', 'abono', 'deposit', 'deposits', 'monto', 'importe', 'crédito', 'credito'])) $idxDeposito = $i;
        }

        if ($idxConcepto === null || $idxDeposito === null) {
            $this->error("No se detectaron las columnas de Concepto o Depósitos. Cabeceras: " . implode(', ', $header));
            return 1;
        }

        $pagos = [];
        $noiseWords = ['PAGO', 'RECIBIDO', 'DE', 'DEL', 'AL', 'EL', 'LA', 'PARA', 'POR', 'CANALES', 'SERVICIO', 'TRANSFERENCIA', 'DEPOSITO', 'SPEI', 'TRASPASO', 'ABONO', 'SA', 'CV', 'S.A.', 'C.V.', 'SAPI', 'S.A.P.I.'];

        foreach ($lines as $line) {
            $r = str_getcsv($line, ',');
            if (count($r) <= max($idxConcepto, $idxDeposito)) continue;
            
            $concepto = trim($r[$idxConcepto] ?? '');
            $rawMonto = $r[$idxDeposito] ?? '0';
            $monto = abs((float) str_replace([',', '$', ' '], ['', '', ''], $rawMonto));
            
            if ($monto <= 0) continue;

            $rawWords = explode(' ', strtoupper(preg_replace('/[^A-Z0-9 ]/', ' ', $concepto)));
            $cleanWords = array_values(array_filter($rawWords, fn($w) => !in_array($w, $noiseWords) && strlen($w) > 2));
            
            if (!empty($cleanWords)) {
                $pagos[] = [
                    'clean_words' => $cleanWords,
                    'monto' => $monto,
                    'original_concepto' => $concepto
                ];
            }
        }

        $this->info("Detectados " . count($pagos) . " pagos potenciales en el banco.");

        $facturas = Cfdi::where('empresa_id', $empresaId)
            ->where('metodo_pago', 'PPD')
            ->where(function($q) { $q->whereNull('estado_sat')->orWhere('estado_sat', '!=', 'Cancelado'); })
            ->where('tipo_comprobante', 'I')
            ->get();

        $service = app(ContabilidadService::class);
        $matches = 0;

        foreach ($pagos as $p) {
            foreach ($facturas as $f) {
                $cliente = strtoupper($f->direccion === 'emitido' ? ($f->nombre_receptor ?? '') : $f->nombre_emisor);
                
                if (abs((float)$f->total - $p['monto']) > 0.10) continue;
                
                $wordMatch = false;
                foreach ($p['clean_words'] as $word) {
                    if (str_contains($cliente, $word)) {
                        $wordMatch = true;
                        break;
                    }
                }

                if ($wordMatch) {
                    $this->info("MATCH: [{$f->folio}] {$cliente} por \${$f->total} (Banco: {$p['original_concepto']})");
                    
                    $integrada = \App\Models\Contab\PolizaContable::where('empresa_id', $empresaId)
                        ->where('concepto', 'LIKE', "%Factura {$f->folio}%")
                        ->where('tipo', 'ingreso')
                        ->exists();
                        
                    if (!$integrada) {
                        try {
                            DB::transaction(function() use ($f, $p, $empresaId, $userId, $service, $cliente) {
                                // 1. Marcar factura como pagada
                                $f->update(['estado' => 'pagado']);
                                
                                // 2. Crear Póliza de Ingreso (Cobro)
                                $tipo = 'ingreso';
                                $numero = $service->generarSiguienteNumero($empresaId, $tipo, date('Y'));
                                
                                $poliza = \App\Models\Contab\PolizaContable::create([
                                    'empresa_id' => $empresaId,
                                    'tipo' => $tipo,
                                    'fecha' => now()->toDateString(),
                                    'numero' => $numero,
                                    'concepto' => "Cobro Factura {$f->folio} - {$cliente} - Banco: {$p['original_concepto']}",
                                    'total' => $f->total,
                                    'estado' => 'borrador',
                                    'created_by' => $userId,
                                    'cfdi_uuid' => $f->uuid,
                                ]);
                                
                                // 3. Asientos (Banco vs Cliente)
                                $ctaBanco = $service->obtenerCuentaBancoContable($empresaId);
                                $rfcContra = $f->direccion === 'emitido' ? ($f->rfc_receptor ?: 'XAXX010101000') : ($f->rfc_emisor ?: 'XAXX010101000');
                                $ctaContra = $service->obtenerCuentaPorRfc($empresaId, $rfcContra, 'Clientes', $cliente);
                                
                                $poliza->asientos()->create(['cuenta_id' => $ctaBanco->id, 'debe' => $f->total, 'haber' => 0, 'referencia' => "Cobro {$f->folio}"]);
                                $poliza->asientos()->create(['cuenta_id' => $ctaContra->id, 'debe' => 0, 'haber' => $f->total, 'referencia' => "Abono Cliente {$f->folio}"]);
                            });
                            $this->info("   -> Póliza de ingreso generada exitosamente.");
                            $matches++;
                        } catch (\Exception $e) {
                            $this->error("   -> Error al generar póliza: " . $e->getMessage());
                        }
                    } else {
                        $this->warn("   -> Ya existe una póliza de ingreso para esta factura.");
                    }
                }
            }
        }

        $this->info("Proceso completado. Coincidencias procesadas: $matches");
        return 0;
    }
}
