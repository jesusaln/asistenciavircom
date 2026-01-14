<?php

namespace App\Services;

use App\Models\Cfdi;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use App\Models\EmpresaConfiguracion;
use App\Models\Proveedor;
use App\Models\SatDescargaMasiva;
use App\Models\SatDescargaDetalle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\PackageReader\CfdiPackageReader;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;

class SatDescargaMasivaService
{
    public function crearSolicitudesPorRango(
        string $direccion,
        Carbon $inicio,
        Carbon $fin,
        ?int $createdBy = null
    ): array {
        $segmentDays = (int) config('services.sat_descarga_masiva.segment_days', 7);
        $segmentDays = max(1, $segmentDays);

        $rangos = $this->splitRangeByDays($inicio, $fin, $segmentDays);
        $descargas = [];

        foreach ($rangos as [$rangoInicio, $rangoFin]) {
            $descargas[] = SatDescargaMasiva::create([
                'direccion' => $direccion,
                'fecha_inicio' => $rangoInicio->format('Y-m-d'),
                'fecha_fin' => $rangoFin->format('Y-m-d'),
                'status' => 'pendiente',
                'created_by' => $createdBy,
            ]);
        }

        return $descargas;
    }

    public function crearSolicitud(string $rfc, string $password, Carbon $inicio, Carbon $fin, string $tipo): SatDescargaMasiva
    {
        // 1. Crear registro en BD
        $descarga = SatDescargaMasiva::create([
            'solicitante_rfc' => $rfc,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'direccion' => $tipo === 'recibidos' ? Cfdi::DIRECCION_RECIBIDO : Cfdi::DIRECCION_EMITIDO,
            'status' => 'solicitando', // estado inicial
        ]);

        // Procesar la solicitud
        $this->procesarSolicitud($descarga, $password);

        // Retornar el objeto descarga actualizado
        return $descarga->fresh();
    }

    private function splitRangeByDays(Carbon $inicio, Carbon $fin, int $days): array
    {
        $inicio = $inicio->copy()->startOfDay();
        $fin = $fin->copy()->startOfDay();

        $rangos = [];
        $cursor = $inicio->copy();
        while ($cursor->lte($fin)) {
            $rangoFin = $cursor->copy()->addDays($days - 1);
            if ($rangoFin->gt($fin)) {
                $rangoFin = $fin->copy();
            }
            $rangos[] = [$cursor->copy(), $rangoFin];
            $cursor = $rangoFin->copy()->addDay();
        }

        return $rangos;
    }

    /**
     * Procesa una solicitud de descarga masiva existente
     * Este método es llamado por el Job para procesar solicitudes
     */
    public function procesarSolicitud(SatDescargaMasiva $descarga, ?string $password = null): array
    {
        // Construir Servicio
        $service = $this->buildService($password);

        if (!$service) {
            $descarga->update(['status' => 'error', 'last_error' => 'No se pudo inicializar servicio SAT (FIEL inválida o no configurada).']);
            return ['success' => false, 'message' => 'No se pudo inicializar servicio SAT.'];
        }

        $period = DateTimePeriod::createFromValues(
            Carbon::parse($descarga->fecha_inicio)->format('Y-m-d') . ' 00:00:00',
            Carbon::parse($descarga->fecha_fin)->format('Y-m-d') . ' 23:59:59'
        );

        $downloadType = ($descarga->direccion === Cfdi::DIRECCION_RECIBIDO) ? DownloadType::received() : DownloadType::issued();

        $query = QueryParameters::create()
            ->withPeriod($period)
            ->withDownloadType($downloadType)
            ->withRequestType(RequestType::xml());

        $documentStatus = $this->resolveDocumentStatus(config('services.sat_descarga_masiva.document_status', 'active'));
        if ($documentStatus !== null) {
            $query = $query->withDocumentStatus($documentStatus);
        }

        try {
            $result = $service->query($query);

            if ($result->getStatus()->isAccepted()) {
                $descarga->update([
                    'request_id' => $result->getRequestId(),
                    'status' => 'pendiente', // Ahora esperamos que el SAT procese
                ]);
                return ['success' => true, 'request_id' => $result->getRequestId()];
            } else {
                $message = $result->getStatus()->getMessage();
                $isLimit = false;

                // Detectar si es un error de límite del SAT
                // 5002: Se alcanzó el límite de solicitudes pendientes
                if (str_contains($message, '5002') || str_contains($message, 'por vida') || str_contains($message, 'límite')) {
                    $isLimit = true;
                }

                $descarga->update([
                    'status' => $isLimit ? 'pendiente' : 'error',
                    'last_error' => $message
                ]);
                return [
                    'success' => false,
                    'message' => $message,
                    'is_limit' => $isLimit
                ];
            }
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $isLimit = str_contains($message, '5002') || str_contains($message, 'por vida') || str_contains($message, 'límite');

            $descarga->update([
                'status' => $isLimit ? 'pendiente' : 'error',
                'last_error' => "Error de conexión SAT: " . $message
            ]);
            return [
                'success' => false,
                'message' => "Error de conexión SAT: " . $message,
                'is_limit' => $isLimit
            ];
        }
    }

    public function verificarYDescargar(SatDescargaMasiva $descarga): array
    {
        $service = $this->buildService();
        if (!$service) {
            return ['success' => false, 'message' => 'No se pudo crear el servicio SAT.'];
        }

        try {
            $verify = $service->verify($descarga->request_id);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "Error al verificar con el SAT: {$e->getMessage()}"];
        }

        if (!$verify->getStatus()->isAccepted()) {
            return ['success' => false, 'message' => $verify->getStatus()->getMessage()];
        }

        if (!$verify->getCodeRequest()->isAccepted()) {
            return ['success' => false, 'message' => $verify->getCodeRequest()->getMessage()];
        }

        $statusRequest = $verify->getStatusRequest();
        if ($statusRequest->isInProgress() || $statusRequest->isAccepted()) {
            return ['success' => true, 'pending' => true, 'message' => 'Solicitud en proceso.'];
        }
        if ($statusRequest->isExpired() || $statusRequest->isFailure() || $statusRequest->isRejected()) {
            return ['success' => false, 'message' => $statusRequest->getMessage()];
        }

        if (!$statusRequest->isFinished()) {
            return ['success' => true, 'pending' => true, 'message' => 'Solicitud no lista.'];
        }

        $packages = $verify->getPackagesIds();
        $stats = [
            'total' => 0,
            'staged' => 0,
            'duplicates' => 0,
            'duplicates_list' => [],
            'errors' => 0,
            'errors_list' => [],
        ];

        foreach ($packages as $packageId) {
            $download = $service->download($packageId);
            if (!$download->getStatus()->isAccepted()) {
                $stats['errors']++;
                $stats['errors_list'][] = "Paquete {$packageId}: {$download->getStatus()->getMessage()}";
                continue;
            }

            $zipContent = $download->getPackageContent();
            try {
                $reader = CfdiPackageReader::createFromContents($zipContent);
            } catch (\Throwable $e) {
                $stats['errors']++;
                $stats['errors_list'][] = "Paquete {$packageId}: {$e->getMessage()}";
                continue;
            }

            foreach ($reader->cfdis() as $uuid => $xmlContent) {
                $stats['total']++;
                $result = $this->guardarEnStaging($descarga->id, (string) $uuid, $xmlContent, $descarga->direccion);
                if ($result === 'staged') {
                    $stats['staged']++;
                } elseif ($result === 'duplicate') {
                    $stats['duplicates']++;
                    if (count($stats['duplicates_list']) < 200) {
                        $stats['duplicates_list'][] = (string) $uuid;
                    }
                } else {
                    $stats['errors']++;
                    if (count($stats['errors_list']) < 20) {
                        $stats['errors_list'][] = "UUID {$uuid}: {$result}";
                    }
                }

                // Actualización incremental para progreso en tiempo real (cada 5 CFDIs o al final)
                if ($stats['total'] % 5 === 0) {
                    $descarga->update([
                        'total_cfdis' => $stats['total'],
                        'inserted_cfdis' => $stats['staged'],
                        'duplicate_cfdis' => $stats['duplicates'],
                        'error_cfdis' => $stats['errors'],
                    ]);
                }
            }
        }

        // Asegurar actualización final antes de terminar el Job
        $errorsPayload = [
            'errors' => $stats['errors_list'] ?: [],
            'duplicates' => $stats['duplicates_list'] ?: [],
        ];

        $descarga->update([
            'total_cfdis' => $stats['total'],
            'inserted_cfdis' => $stats['staged'],
            'duplicate_cfdis' => $stats['duplicates'],
            'error_cfdis' => $stats['errors'],
            'errors' => ($errorsPayload['errors'] || $errorsPayload['duplicates']) ? $errorsPayload : null,
        ]);

        return [
            'success' => true,
            'pending' => false,
            'packages' => $packages,
            'stats' => $stats,
        ];
    }

    public function revalidarEstatusSat(SatDescargaMasiva $descarga): array
    {
        $stats = [
            'total' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => 0,
        ];

        $query = Cfdi::where('direccion', $descarga->direccion)
            ->whereDate('fecha_emision', '>=', $descarga->fecha_inicio)
            ->whereDate('fecha_emision', '<=', $descarga->fecha_fin);

        foreach ($query->cursor() as $cfdi) {
            $stats['total']++;
            $data = $this->getCfdiDataForSat($cfdi);
            if (!$data) {
                $stats['omitidos']++;
                continue;
            }
            try {
                $this->validarEstadoSat($cfdi, $data);
                $stats['actualizados']++;
            } catch (\Throwable $e) {
                $stats['errores']++;
            }
        }

        return ['success' => true, 'stats' => $stats];
    }

    private function guardarEnStaging(int $descargaId, string $uuid, string $xmlContent, string $direccion): string
    {
        try {
            // Si ya existe en cfdis, es duplicado
            if (Cfdi::where('uuid', $uuid)->exists()) {
                return 'duplicate';
            }

            // Si ya existe en staging, es duplicado
            if (SatDescargaDetalle::where('uuid', $uuid)->exists()) {
                return 'duplicate';
            }

            $parser = app(CfdiXmlParserService::class);
            $data = $parser->parseCfdiXml($xmlContent);

            SatDescargaDetalle::create([
                'sat_descarga_masiva_id' => $descargaId,
                'uuid' => $uuid,
                'direccion' => $direccion,
                'xml_data' => $data,
                'xml_content' => $xmlContent,
                'importado' => false,
                'fecha_emision' => $data['fecha'] ?? null,
                'rfc_emisor' => $data['emisor']['rfc'] ?? null,
                'nombre_emisor' => $data['emisor']['nombre'] ?? null,
                'rfc_receptor' => $data['receptor']['rfc'] ?? null,
                'nombre_receptor' => $data['receptor']['nombre'] ?? null,
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'I',
                'total' => $data['total'] ?? 0,
            ]);

            return 'staged';
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function importarDesdeStaging(array $ids): array
    {
        $stats = ['inserted' => 0, 'errors' => 0];
        $detalles = SatDescargaDetalle::whereIn('id', $ids)->where('importado', false)->get();

        foreach ($detalles as $detalle) {
            $result = $this->importarXml($detalle->uuid, $detalle->xml_content, $detalle->direccion);
            if ($result === 'inserted' || $result === 'duplicate') {
                $detalle->update(['importado' => true]);
                $stats['inserted']++;
            } else {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    private function buildService(?string $password = null): ?Service
    {
        $config = EmpresaConfiguracion::getConfig();
        // Use provided password or fallback to config
        $fielPassword = $password ?: $config->fiel_password;

        if (!$config || empty($config->fiel_cer_path) || empty($config->fiel_key_path) || empty($fielPassword)) {
            Log::warning('FIEL incompleta para descarga masiva SAT.');
            return null;
        }

        if (!Storage::disk('local')->exists($config->fiel_cer_path) || !Storage::disk('local')->exists($config->fiel_key_path)) {
            Log::warning('Archivos FIEL no encontrados en storage.');
            return null;
        }

        try {
            $fiel = Fiel::create(
                Storage::disk('local')->get($config->fiel_cer_path),
                Storage::disk('local')->get($config->fiel_key_path),
                $fielPassword
            );
        } catch (\Throwable $e) {
            Log::error("Error creando FIEL object: " . $e->getMessage());
            return null;
        }

        if (!$fiel->isValid()) {
            Log::warning('FIEL no valida para descarga masiva SAT.');
            return null;
        }

        $verify = config('services.sat_descarga_masiva.verify', true);
        if (is_string($verify)) {
            $verify = filter_var($verify, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($verify === null) {
                $verify = true;
            }
        }

        $guzzleOptions = [];
        if ($verify === false) {
            $guzzleOptions['verify'] = false;
        } else {
            $cafile = config('services.sat_descarga_masiva.cafile');
            if (!$cafile) {
                $fallback = 'C:\\Program Files\\Git\\usr\\ssl\\certs\\ca-bundle.crt';
                if (file_exists($fallback)) {
                    $cafile = $fallback;
                }
            }
            if (is_string($cafile) && $cafile !== '' && file_exists($cafile)) {
                $guzzleOptions['verify'] = $cafile;
            }
        }

        $requestBuilder = new FielRequestBuilder($fiel);
        $client = empty($guzzleOptions) ? new Client() : new Client($guzzleOptions);
        $webClient = new GuzzleWebClient($client);

        return new Service($requestBuilder, $webClient);
    }

    private function resolveDocumentStatus(string $status): ?DocumentStatus
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'active', 'vigente', '1' => DocumentStatus::active(),
            'cancelled', 'cancelado', '0' => DocumentStatus::cancelled(),
            'all', 'todos' => DocumentStatus::undefined(),
            default => DocumentStatus::active(), // Por defecto solo vigentes
        };
    }

    private function getDownloadType(string $direccion): DownloadType
    {
        return $direccion === Cfdi::DIRECCION_RECIBIDO
            ? DownloadType::received()
            : DownloadType::issued();
    }

    private function importarXml(string $uuid, string $xmlContent, string $direccion): string
    {
        try {
            if (Cfdi::where('uuid', $uuid)->exists()) {
                return 'duplicate';
            }

            $parser = app(CfdiXmlParserService::class);
            $data = $parser->parseCfdiXml($xmlContent);

            $fecha = $data['fecha'] ?? now()->format('Y-m-d');
            $xmlPath = $this->storeXmlContent($uuid, $xmlContent, $fecha, $direccion);

            $datosAdicionales = [
                'lugar_expedicion' => $data['lugar_expedicion'] ?? null,
                'exportacion' => $data['exportacion'] ?? null,
                'condiciones_de_pago' => $data['condiciones_de_pago'] ?? null,
                'receptor' => $data['receptor'] ?? null,
                'conceptos_count' => count($data['conceptos'] ?? []),
                'traslados' => $data['impuestos']['traslados'] ?? [],
                'retenciones' => $data['impuestos']['retenciones'] ?? [],
                'cfdi_relacionados' => $data['cfdi_relacionados'] ?? [],
            ];

            $cfdiData = [
                'uuid' => $uuid,
                'empresa_id' => $this->resolveEmpresaId(),
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'I',
                'serie' => $data['serie'] ?? null,
                'folio' => $data['folio'] ?? null,
                'fecha_emision' => $fecha,
                'fecha_timbrado' => $data['timbre']['fecha_timbrado'] ?? null,

                'rfc_emisor' => $data['emisor']['rfc'] ?? null,
                'nombre_emisor' => $data['emisor']['nombre'] ?? null,
                'regimen_fiscal_emisor' => $data['emisor']['regimen_fiscal'] ?? null,

                // Receptor
                'rfc_receptor' => $data['receptor']['rfc'] ?? $data['receptor']['Rfc'] ?? null,
                'nombre_receptor' => $data['receptor']['nombre'] ?? $data['receptor']['Nombre'] ?? null,

                'subtotal' => $data['subtotal'] ?? 0,
                'descuento' => $data['descuento'] ?? 0,
                'total' => $data['total'] ?? 0,
                'total_impuestos_trasladados' => $data['impuestos']['total_impuestos_trasladados'] ?? 0,
                'total_impuestos_retenidos' => $data['impuestos']['total_impuestos_retenidos'] ?? 0,

                'moneda' => trim($data['moneda'] ?? 'MXN'),
                'tipo_cambio' => $data['tipo_cambio'] ?? 1,
                'forma_pago' => trim($data['forma_pago'] ?? ''),
                'metodo_pago' => trim($data['metodo_pago'] ?? ''),
                'uso_cfdi' => trim($data['receptor']['uso_cfdi'] ?? $data['receptor']['UsoCFDI'] ?? 'G03'),

                'no_certificado_sat' => trim($data['no_certificado_sat'] ?? ''),
                'no_certificado_cfdi' => trim($data['no_certificado_cfdi'] ?? ''),
                'sello_sat' => trim($data['sello_sat'] ?? ''),
                'sello_cfdi' => trim($data['sello_cfdi'] ?? ''),
                'cadena_original' => trim($data['cadena_original'] ?? ''),
                'xml_url' => $xmlPath,
                'estatus' => Cfdi::ESTATUS_VIGENTE,
                'datos_adicionales' => $datosAdicionales,
                'complementos' => $data['complementos'] ?? [],
            ];

            $columns = Schema::getColumnListing('cfdis');
            if (in_array('cliente_id', $columns, true)) {
                $clienteId = $this->resolveClienteId($direccion, $data);
                if ($clienteId) {
                    $cfdiData['cliente_id'] = $clienteId;
                }
            }

            if (empty($cfdiData['uso_cfdi'])) {
                $cfdiData['uso_cfdi'] = $cfdiData['tipo_comprobante'] === 'P' ? 'CP01' : 'G03';
            }

            if (in_array('direccion', $columns, true)) {
                $cfdiData['direccion'] = $direccion;
            }

            $cfdiData = array_intersect_key($cfdiData, array_flip($columns));
            $cfdi = Cfdi::create($cfdiData);

            if (!empty($data['conceptos'])) {
                foreach ($data['conceptos'] as $concepto) {
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

            $this->validarEstadoSat($cfdi, $data);

            return 'inserted';
        } catch (\Throwable $e) {
            Log::error('Error importando CFDI SAT', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
            return $e->getMessage();
        }
    }

    private function resolveClienteId(string $direccion, array $data): ?int
    {
        $target = $direccion === Cfdi::DIRECCION_RECIBIDO
            ? ($data['emisor'] ?? [])
            : ($data['receptor'] ?? []);

        $rfc = trim((string) ($target['rfc'] ?? $target['Rfc'] ?? ''));
        $nombre = trim((string) ($target['nombre'] ?? $target['Nombre'] ?? ''));

        if (!$rfc && !$nombre) {
            // Si es CFDI de nómina o traslado, podría no tener emisor/receptor claro en el parser básico
            return Cliente::query()->value('id');
        }

        // 1. Buscar en Clientes por RFC
        if ($rfc) {
            $cliente = Cliente::where('rfc', $rfc)->first();
            if ($cliente) {
                return $cliente->id;
            }
        }

        // 2. Si es recibido, buscar en Proveedores por RFC
        if ($direccion === Cfdi::DIRECCION_RECIBIDO && $rfc) {
            $proveedor = Proveedor::where('rfc', $rfc)->first();
            // Nota: Si el sistema requiere cliente_id, y encontramos un proveedor, 
            // no podemos usar su ID directamente en cliente_id.
            // Pero podríamos crear un Cliente "espejo" o usar un cliente genérico.
            // Dada la restricción NOT NULL, crearemos un cliente para este RFC si no existe.
        }

        // 3. Buscar en Clientes por Nombre
        if ($nombre) {
            $cliente = Cliente::where('nombre_razon_social', $nombre)->first();
            if ($cliente) {
                return $cliente->id;
            }
        }

        // 4. Crear cliente automático para evitar fallo de integridad
        try {
            $clienteData = [
                'nombre_razon_social' => $nombre ?: "RFC: {$rfc}",
                'rfc' => $rfc ?: 'XAXX010101000',
                'regimen_fiscal' => $target['regimen_fiscal'] ?? $target['RegimenFiscal'] ?? '601',
                'uso_cfdi' => $target['uso_cfdi'] ?? $target['UsoCFDI'] ?? 'G03',
                'domicilio_fiscal_cp' => $target['domicilio_fiscal'] ?? $target['DomicilioFiscalReceptor'] ?? '00000',
                'activo' => true,
            ];

            $columns = Schema::getColumnListing('clientes');
            $clienteData = array_intersect_key($clienteData, array_flip($columns));

            $cliente = Cliente::create($clienteData);
            return $cliente->id;
        } catch (\Throwable $e) {
            // Fallback de último recurso: cualquier ID de cliente existente
            return Cliente::query()->value('id');
        }
    }

    private function resolveEmpresaId(): int
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresa = $empresaId ? Empresa::find($empresaId) : null;
        if ($empresa) {
            return $empresa->id;
        }

        // Si no hay empresa, crear una por defecto usando la configuración
        $config = EmpresaConfiguracion::getConfig();
        $rfc = $config->rfc ?: 'XAXX010101000';
        $tipoPersona = strlen($rfc) === 12 ? 'Moral' : 'Fisica';

        $nuevaEmpresa = Empresa::create([
            'nombre_razon_social' => $config->nombre_empresa ?: 'CDD - Empresa Principal',
            'tipo_persona' => $tipoPersona,
            'rfc' => $rfc,
            'regimen_fiscal' => $config->regimen_fiscal ?: '601',
            'uso_cfdi' => 'G03', // Requerido por la tabla
            'email' => $config->email ?: 'admin@cdd.com',
            'telefono' => $config->telefono ?: '0000000000',
            'calle' => $config->calle ?: 'Conocida',
            'numero_exterior' => $config->numero_exterior ?: 'SN',
            'colonia' => $config->colonia ?: 'Centro',
            'codigo_postal' => $config->codigo_postal ?: '00000',
            'municipio' => $config->ciudad ?: 'Municipio',
            'estado' => $config->estado ?: 'Estado',
            'pais' => $config->pais ?: 'México',
            'whatsapp_enabled' => false,
            'whatsapp_default_language' => 'es_MX',
        ]);

        return $nuevaEmpresa->id;
    }

    private function getCfdiColumnLength(string $column): ?int
    {
        try {
            $result = DB::table('information_schema.columns')
                ->where('table_name', 'cfdis')
                ->where('column_name', $column)
                ->value('character_maximum_length');
            return $result ? (int) $result : null;
        } catch (\Throwable $e) {
            return null;
        }
    }



    private function storeXmlContent(string $uuid, string $xmlContent, string $fecha, string $direccion): string
    {
        $date = Carbon::parse($fecha);
        $year = $date->format('Y');
        $month = $date->format('m');
        $tipo = $direccion === Cfdi::DIRECCION_RECIBIDO ? 'recibidos' : 'emitidos';
        $directory = "cfdis/{$tipo}/{$year}/{$month}";
        Storage::disk('public')->makeDirectory($directory);
        $path = "{$directory}/{$uuid}.xml";
        Storage::disk('public')->put($path, $xmlContent);
        return $path;
    }

    private function getCfdiDataForSat(Cfdi $cfdi): ?array
    {
        if (!empty($cfdi->datos_adicionales['receptor'])) {
            return [
                'emisor' => [
                    'rfc' => $cfdi->rfc_emisor,
                ],
                'receptor' => $cfdi->datos_adicionales['receptor'],
                'total' => $cfdi->total,
            ];
        }

        $xmlContent = $this->loadXmlContent($cfdi);
        if (!$xmlContent) {
            return null;
        }

        $parser = app(CfdiXmlParserService::class);
        return $parser->parseCfdiXml($xmlContent);
    }

    private function loadXmlContent(Cfdi $cfdi): ?string
    {
        $path = $cfdi->xml_url;
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }

        $uuid = $cfdi->uuid;
        $paths = [
            "cfdis/{$uuid}.xml",
            "cfdis/recibidos/{$uuid}.xml",
            "cfdis/emitidos/{$uuid}.xml",
            "cfdis/pagos/{$uuid}.xml",
        ];

        foreach ($paths as $fallback) {
            if (Storage::disk('public')->exists($fallback)) {
                return Storage::disk('public')->get($fallback);
            }
        }

        return null;
    }

    private function validarEstadoSat(Cfdi $cfdi, array $data): void
    {
        try {
            $rfcEmisor = $data['emisor']['rfc'] ?? $cfdi->rfc_emisor;
            $rfcReceptor = $data['receptor']['rfc'] ?? $data['receptor']['Rfc'] ?? null;
            $total = $data['total'] ?? $cfdi->total;

            if (empty($rfcEmisor) || empty($rfcReceptor) || $total === null) {
                return;
            }

            $sat = app(SatConsultaDirectaService::class);
            $res = $sat->consultarEstado(
                $cfdi->uuid,
                $rfcEmisor,
                $rfcReceptor,
                (float) $total
            );

            $estado = $res['estado'] ?? null;
            $codigo = $res['codigo_estatus'] ?? null;
            $esCancelable = $res['es_cancelable'] ?? null;

            $datosAdicionales = array_merge($cfdi->datos_adicionales ?? [], [
                'sat_estado' => $estado,
                'sat_codigo_estatus' => $codigo,
                'sat_es_cancelable' => $esCancelable,
                'sat_ultima_consulta' => now()->toDateTimeString(),
            ]);

            if (is_string($codigo) && Str::contains($codigo, '998')) {
                $datosAdicionales['sat_intermitencia'] = true;
                $cfdi->update(['datos_adicionales' => $datosAdicionales]);
                return;
            }

            if (is_string($estado) && Str::lower($estado) === 'cancelado') {
                $cfdi->update([
                    'estatus' => Cfdi::ESTATUS_CANCELADO,
                    'fecha_cancelacion' => $cfdi->fecha_cancelacion ?: now(),
                    'datos_adicionales' => $datosAdicionales,
                ]);
                return;
            }

            $cfdi->update(['datos_adicionales' => $datosAdicionales]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo validar estado SAT del CFDI descargado', [
                'uuid' => $cfdi->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
