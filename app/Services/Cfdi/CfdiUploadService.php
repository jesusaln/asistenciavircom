<?php

namespace App\Services\Cfdi;

use App\Models\Cfdi;
use App\Services\CfdiXmlParserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class CfdiUploadService
{
    protected CfdiXmlParserService $parser;
    protected \App\Services\PaymentProcessingService $paymentService;

    public function __construct(CfdiXmlParserService $parser, \App\Services\PaymentProcessingService $paymentService)
    {
        $this->parser = $parser;
        $this->paymentService = $paymentService;
    }

    /**
     * Subir y procesar un XML de CFDI recibido (de proveedor)
     *
     * @param UploadedFile $file Archivo XML
     * @return Cfdi El registro creado
     * @throws Exception Si el XML no es válido o el UUID ya existe
     */
    public function uploadFromXml(UploadedFile $file): Cfdi
    {
        // Leer contenido del XML
        $xmlContent = file_get_contents($file->getRealPath());

        if (empty($xmlContent)) {
            throw new Exception('El archivo XML está vacío.');
        }

        // Parsear el XML
        $data = $this->parser->parseCfdiXml($xmlContent);

        if (empty($data['uuid'])) {
            throw new Exception('El XML no contiene un UUID válido. Asegúrate de que sea un CFDI timbrado.');
        }

        // Verificar si existe (incluyendo soft-deleted)
        $existing = Cfdi::withTrashed()->where('uuid', $data['uuid'])->first();
        if ($existing) {
            if ($existing->trashed()) {
                // Si fue eliminado, restaurarlo y actualizar datos
                $existing->restore();
                Log::info("CFDI restaurado: {$existing->uuid}");
                return $this->updateExistingCfdi($existing, $data, $file);
            }
            throw new Exception("Ya existe un CFDI con el UUID {$data['uuid']}.");
        }

        // Guardar el archivo XML con organización por Año/Mes
        $xmlPath = $this->storeXmlFile($file, $data['uuid'], $data['fecha'] ?? null);

        // Crear el registro en la base de datos
        $cfdi = Cfdi::create([
            'uuid' => $data['uuid'],
            'direccion' => Cfdi::DIRECCION_RECIBIDO,
            'tipo_comprobante' => $data['tipo_comprobante'] ?? 'I',
            'serie' => $data['serie'] ?? null,
            'folio' => $data['folio'] ?? null,
            'fecha_emision' => $data['fecha'] ?? now(),
            'fecha_timbrado' => $data['fecha_timbrado'] ?? null,

            // Datos del emisor (proveedor)
            'rfc_emisor' => $data['emisor']['rfc'] ?? null,
            'nombre_emisor' => $data['emisor']['nombre'] ?? null,
            'regimen_fiscal_emisor' => $data['emisor']['regimen_fiscal'] ?? null,

            // Montos
            'subtotal' => $data['subtotal'] ?? 0,
            'descuento' => $data['descuento'] ?? 0,
            'total' => $data['total'] ?? 0,
            'total_impuestos_trasladados' => $data['impuestos']['total_traslados'] ?? 0,
            'total_impuestos_retenidos' => $data['impuestos']['total_retenciones'] ?? 0,

            // Datos del receptor (tu empresa)
            'rfc_receptor' => $data['receptor']['rfc'] ?? null,
            'nombre_receptor' => $data['receptor']['nombre'] ?? null,

            // Pago
            'moneda' => $data['moneda'] ?? 'MXN',
            'tipo_cambio' => $data['tipo_cambio'] ?? 1,
            'forma_pago' => $data['forma_pago'] ?? null,
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'uso_cfdi' => $data['receptor']['uso_cfdi'] ?? $data['uso_cfdi'] ?? null,

            // Timbrado
            'no_certificado_sat' => $data['no_certificado_sat'] ?? null,
            'no_certificado_cfdi' => $data['no_certificado_cfdi'] ?? null,
            'sello_sat' => $data['sello_sat'] ?? null,
            'sello_cfdi' => $data['sello_cfdi'] ?? null,
            'cadena_original' => $data['cadena_original'] ?? null,

            'xml_url' => $xmlPath,
            'estatus' => Cfdi::ESTATUS_VIGENTE,

            // Datos adicionales
            'datos_adicionales' => [
                'lugar_expedicion' => $data['lugar_expedicion'] ?? null,
                'receptor' => $data['receptor'] ?? null,
                'conceptos_count' => count($data['conceptos'] ?? []),
            ],
        ]);

        if (!empty($data['conceptos'])) {
            $this->storeConceptos($cfdi, $data['conceptos']);
        }

        // ✅ FIX: Auto-procesar pagos si es tipo P
        if ($cfdi->tipo_comprobante === 'P') {
            try {
                $this->paymentService->processAndApplyAuto($xmlContent);
                Log::info("Auto-procesamiento de pago completado para CFDI: {$cfdi->uuid}");
            } catch (\Exception $e) {
                Log::error("Error en auto-procesamiento de pago para CFDI {$cfdi->uuid}: " . $e->getMessage());
            }
        }

        Log::info("CFDI recibido guardado: {$cfdi->uuid}", [
            'emisor' => $cfdi->nombre_emisor,
            'total' => $cfdi->total,
        ]);

        return $cfdi;
    }

    /**
     * Actualizar un CFDI existente (restaurado de soft-delete)
     */
    protected function updateExistingCfdi(Cfdi $cfdi, array $data, UploadedFile $file): Cfdi
    {
        // Actualizar el archivo XML
        $xmlPath = $this->storeXmlFile($file, $data['uuid'], $data['fecha'] ?? null);

        // Actualizar el registro
        $cfdi->update([
            'direccion' => Cfdi::DIRECCION_RECIBIDO,
            'tipo_comprobante' => $data['tipo_comprobante'] ?? 'I',
            'serie' => $data['serie'] ?? null,
            'folio' => $data['folio'] ?? null,
            'fecha_emision' => $data['fecha'] ?? now(),
            'fecha_timbrado' => $data['fecha_timbrado'] ?? null,
            'rfc_emisor' => $data['emisor']['rfc'] ?? null,
            'nombre_emisor' => $data['emisor']['nombre'] ?? null,
            'regimen_fiscal_emisor' => $data['emisor']['regimen_fiscal'] ?? null,
            'rfc_receptor' => $data['receptor']['rfc'] ?? null,
            'nombre_receptor' => $data['receptor']['nombre'] ?? null,
            'subtotal' => $data['subtotal'] ?? 0,
            'descuento' => $data['descuento'] ?? 0,
            'total' => $data['total'] ?? 0,
            'total_impuestos_trasladados' => $data['impuestos']['total_traslados'] ?? 0,
            'total_impuestos_retenidos' => $data['impuestos']['total_retenciones'] ?? 0,
            'moneda' => $data['moneda'] ?? 'MXN',
            'tipo_cambio' => $data['tipo_cambio'] ?? 1,
            'forma_pago' => $data['forma_pago'] ?? null,
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'uso_cfdi' => $data['receptor']['uso_cfdi'] ?? $data['uso_cfdi'] ?? null,
            'no_certificado_sat' => $data['no_certificado_sat'] ?? null,
            'no_certificado_cfdi' => $data['no_certificado_cfdi'] ?? null,
            'sello_sat' => $data['sello_sat'] ?? null,
            'sello_cfdi' => $data['sello_cfdi'] ?? null,
            'cadena_original' => $data['cadena_original'] ?? null,
            'xml_url' => $xmlPath,
            'estatus' => Cfdi::ESTATUS_VIGENTE,
            'datos_adicionales' => [
                'lugar_expedicion' => $data['lugar_expedicion'] ?? null,
                'receptor' => $data['receptor'] ?? null,
                'conceptos_count' => count($data['conceptos'] ?? []),
            ],
        ]);

        // Eliminar conceptos anteriores y guardar los nuevos
        $cfdi->conceptos()->delete();
        if (!empty($data['conceptos'])) {
            $this->storeConceptos($cfdi, $data['conceptos']);
        }

        Log::info("CFDI restaurado y actualizado: {$cfdi->uuid}", [
            'emisor' => $cfdi->nombre_emisor,
            'total' => $cfdi->total,
        ]);

        return $cfdi;
    }

    /**
     * Guardar el archivo XML en storage (Organizado por Año/Mes)
     */
    protected function storeXmlFile(UploadedFile $file, string $uuid, ?string $fecha = null): string
    {
        $date = $fecha ? \Carbon\Carbon::parse($fecha) : now();
        $year = $date->format('Y');
        $month = $date->format('m');

        $directory = "cfdis/recibidos/{$year}/{$month}";
        $filename = "{$uuid}.xml";

        // Asegurar que el directorio existe
        Storage::disk('public')->makeDirectory($directory);

        // Guardar el archivo
        $path = "{$directory}/{$filename}";
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    /**
     * Guardar los conceptos del CFDI
     */
    protected function storeConceptos(Cfdi $cfdi, array $conceptos): void
    {
        foreach ($conceptos as $concepto) {
            $cfdi->conceptos()->create([
                'clave_prod_serv' => $concepto['clave_prod_serv'] ?? null,
                'no_identificacion' => $concepto['no_identificacion'] ?? null,
                'cantidad' => $concepto['cantidad'] ?? 1,
                'clave_unidad' => $concepto['clave_unidad'] ?? null,
                'unidad' => $concepto['unidad'] ?? null,
                'descripcion' => $concepto['descripcion'] ?? '',
                'valor_unitario' => $concepto['valor_unitario'] ?? 0,
                'importe' => $concepto['importe'] ?? 0,
                'descuento' => $concepto['descuento'] ?? 0,
                'objeto_imp' => $concepto['objeto_imp'] ?? '02',
                'impuestos' => $concepto['impuestos'] ?? null,
            ]);
        }
    }

    /**
     * Obtener preview de un XML sin guardarlo
     */
    public function previewXml(UploadedFile $file): array
    {
        $xmlContent = file_get_contents($file->getRealPath());

        if (empty($xmlContent)) {
            throw new Exception('El archivo XML está vacío.');
        }

        $data = $this->parser->parseCfdiXml($xmlContent);

        // Verificar si ya existe
        $existing = null;
        if (!empty($data['uuid'])) {
            $existing = Cfdi::where('uuid', $data['uuid'])->first();
        }

        return [
            'data' => $data,
            'is_duplicate' => $existing !== null,
            'existing_cfdi' => $existing ? [
                'id' => $existing->id,
                'fecha_emision' => $existing->fecha_emision,
                'total' => $existing->total,
            ] : null,
        ];
    }
}
