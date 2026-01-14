<?php

namespace App\Services\Cfdi;

use App\Models\Venta;
use App\Models\Cfdi;
use App\Services\ContpaqiService;
use App\Services\SatConsultaDirectaService;
use App\Services\Cfdi\CfdiJsonBuilder;
use App\Services\Cfdi\CertService;
use App\Models\CuentasPorCobrar;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use App\Services\Cfdi\CfdiPagoJsonBuilder; // Importar builder
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CfdiService
{
    protected $jsonBuilder;
    protected $pagoBuilder; // ✅ Nueva dependencia
    protected $certService;
    protected $pac;
    protected $uploadService;
    protected $satConsultaDirecta;
    protected $contpaqiService;

    public function __construct(
        CfdiJsonBuilder $jsonBuilder,
        CfdiPagoJsonBuilder $pagoBuilder,
        CertService $certService,
        ContpaqiService $contpaqiService,
        CfdiUploadService $uploadService,
        SatConsultaDirectaService $satConsultaDirecta
    ) {
        $this->jsonBuilder = $jsonBuilder;
        $this->pagoBuilder = $pagoBuilder;
        $this->certService = $certService;
        $this->contpaqiService = $contpaqiService;
        $this->uploadService = $uploadService;
        $this->satConsultaDirecta = $satConsultaDirecta;
    }

    /**
     * Proceso completo de facturación de una venta.
     */
    public function facturarVenta(Venta $venta, array $options = []): array
    {
        try {
            // 0. Si Contpaqi está habilitado, delegar completamente
            if (config('services.contpaqi.enabled')) {
                return $this->contpaqiService->procesarVenta($venta);
            }

            // 1. Validar que la venta ya no esté facturada
            if ($venta->cfdis()->timbrados()->exists()) {
                throw new \Exception("Esta venta ya cuenta con una factura timbrada.");
            }

            // 2. Validar que el cliente requiere factura
            $cliente = $venta->cliente;
            if (!$cliente) {
                throw new \Exception("La venta no tiene un cliente asociado.");
            }

            if (!$cliente->requiere_factura) {
                throw new \Exception(
                    "⚠️ Este cliente no requiere factura.\n\n" .
                    "Si desea facturar esta venta, por favor:\n" .
                    "1. Vaya al catálogo de Clientes\n" .
                    "2. Edite el cliente \"{$cliente->nombre_razon_social}\"\n" .
                    "3. Active la casilla \"Requiere Factura\"\n" .
                    "4. Complete los datos fiscales requeridos (RFC, Régimen Fiscal, Uso CFDI, C.P. Domicilio Fiscal)\n" .
                    "5. Guarde los cambios e intente facturar nuevamente"
                );
            }

            // 3. Validar datos fiscales del cliente
            $erroresDatos = [];
            if (empty($cliente->rfc)) {
                $erroresDatos[] = "RFC";
            }
            if (empty($cliente->regimen_fiscal)) {
                $erroresDatos[] = "Régimen Fiscal";
            }
            if (empty($cliente->uso_cfdi)) {
                $erroresDatos[] = "Uso de CFDI";
            }
            if (empty($cliente->domicilio_fiscal_cp) && empty($cliente->codigo_postal)) {
                $erroresDatos[] = "Código Postal del Domicilio Fiscal";
            }

            if (!empty($erroresDatos)) {
                throw new \Exception(
                    "⚠️ El cliente \"{$cliente->nombre_razon_social}\" tiene datos fiscales incompletos.\n\n" .
                    "Faltan los siguientes campos: " . implode(", ", $erroresDatos) . ".\n\n" .
                    "Por favor complete los datos fiscales del cliente antes de facturar."
                );
            }

            // 3.5 Validaciones Pre-Timbrado (Locales) para evitar errores del PAC
            $this->validarPreTimbrado($venta, $cliente);

            // 4. Preparar los datos (JSON)
            $cfdiJson = $this->jsonBuilder->buildFromVenta($venta, $options);
            $jsonString = json_encode($cfdiJson);

            // 5. Validar y preparar certificados
            // ✅ SECURITY FIX: Validate certificate before using
            $certValidation = $this->certService->validateCertificate();
            if (!$certValidation['success']) {
                throw new \Exception($certValidation['message']);
            }

            $cerPem = $this->certService->getCsdCerPem();
            $keyPem = $this->certService->getCsdKeyPem();

            if (!$cerPem || !$keyPem) {
                throw new \Exception("Faltan los certificados CSD de la empresa para poder facturar.");
            }

            // 6. Llamar al PAC (Timbrar) - ELIMINADO: FacturaLOPlus removido
            // En un futuro, si se agrega otro PAC, se implementaría aquí.
            throw new \Exception("No hay un servicio de timbrado activo configurado (FacturaLOPlus fue removido).");

            // 5. Guardar archivos físicamente
            $xmlFilename = 'cfdis/' . $uuid . '.xml';
            Storage::disk('public')->put($xmlFilename, $xml);

            // 6. Generar PDF Localmente
            $pdfFilename = 'cfdis/' . $uuid . '.pdf';
            $this->generarPdfManual($venta->id, $data, $cfdiJson);

            // 6. Registrar en la base de datos
            $finalCfdi = DB::transaction(function () use ($venta, $cfdiJson, $data, $uuid, $xmlFilename, $pdfFilename, $options) {
                $cfdi = Cfdi::create([
                    'cliente_id' => $venta->cliente_id,
                    'empresa_id' => EmpresaResolver::resolveId() ?? 1,
                    'venta_id' => $venta->id,
                    'tipo_comprobante' => 'I',
                    'serie' => $cfdiJson['Comprobante']['Serie'],
                    'folio' => $cfdiJson['Comprobante']['Folio'],
                    'uuid' => $uuid,
                    'fecha_timbrado' => $data['fechaTimbrado'] ?? $data['FechaTimbrado'] ?? now(),
                    'fecha_emision' => $cfdiJson['Comprobante']['Fecha'],
                    'subtotal' => $venta->subtotal,
                    'total_impuestos_trasladados' => $venta->iva,
                    'total' => $venta->total,
                    'moneda' => $venta->moneda ?: 'MXN',
                    'tipo_cambio' => 1,
                    'metodo_pago' => $cfdiJson['Comprobante']['MetodoPago'],
                    'forma_pago' => $cfdiJson['Comprobante']['FormaPago'],
                    'uso_cfdi' => $cfdiJson['Comprobante']['Receptor']['UsoCFDI'],
                    'estatus' => Cfdi::ESTATUS_TIMBRADO,
                    'estatus_sat' => 'Vigente',
                    'xml_path' => $xmlFilename,
                    'pdf_path' => $pdfFilename,
                    'sello_sat' => $data['selloSAT'] ?? $data['SelloSAT'] ?? null,
                    'sello_cfdi' => $data['selloCFDI'] ?? $data['SelloCFDI'] ?? null,
                    'no_certificado_sat' => $data['noCertificadoSAT'] ?? $data['NoCertificadoSAT'] ?? null,
                    'cadena_original' => $data['cadenaOriginal'] ?? null,
                    'datos_adicionales' => !empty($options) ? [
                        'cfdi_relacion_tipo' => $options['cfdi_relacion_tipo'] ?? null,
                        'cfdi_relacion_uuids' => $options['cfdi_relacion_uuids'] ?? null,
                        'tipo_factura' => $options['tipo_factura'] ?? null,
                    ] : null,
                ]);

                // Crear conceptos de la factura
                foreach ($cfdiJson['Comprobante']['Conceptos'] as $con) {
                    $cfdi->conceptos()->create([
                        'clave_prod_serv' => $con['ClaveProdServ'],
                        'cantidad' => $con['Cantidad'],
                        'clave_unidad' => $con['ClaveUnidad'],
                        'descripcion' => $con['Descripcion'],
                        'valor_unitario' => $con['ValorUnitario'],
                        'importe' => $con['Importe'],
                        'objeto_imp' => $con['ObjetoImp'],
                    ]);
                }

                return $cfdi;
            });

            // 7. Enviar Correo (ELIMINADO: Se hará manual a petición del usuario)
            /*
            try {
                if ($venta->cliente && $venta->cliente->email) {
                    \Illuminate\Support\Facades\Mail::to($venta->cliente->email)
                        ->send(new \App\Mail\FacturaMail($finalCfdi));
                    Log::info("Correo de factura enviado a: " . $venta->cliente->email);
                }
            } catch (\Exception $e) {
                Log::error("Error enviando correo de factura: " . $e->getMessage());
            }
            */

            return [
                'success' => true,
                'cfdi' => $finalCfdi,
                'message' => 'Factura generada y timbrada exitosamente.'
            ];

        } catch (\Exception $e) {
            Log::error("Error en CfdiService: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    /**
     * Timbrar un Complemento de Pago (REP 2.0).
     */
    public function timbrarPago(CuentasPorCobrar $cxc, float $monto, string $metodoPago, Carbon $fechaPago): array
    {
        // Si CONTPAQi está habilitado, delegar
        if (config('services.contpaqi.enabled')) {
            Log::info("CfdiService: Delegando timbrarPago a ContpaqiService");
            return $this->contpaqiService->procesarPago($cxc, $monto, $metodoPago, $fechaPago);
        }

        return [
            'success' => false,
            'message' => 'Servicio de timbrado (CONTPAQi) no habilitado.'
        ];
    }

    public function facturarAnticipo(Venta $venta, float $montoTotal, string $metodoPagoInterno): array
    {
        if (config('services.contpaqi.enabled')) {
            Log::info("CfdiService: Delegando facturarAnticipo a ContpaqiService");
            return $this->contpaqiService->facturarAnticipo($venta, $montoTotal, $metodoPagoInterno);
        }

        return [
            'success' => false,
            'message' => 'Servicio de anticipos (CONTPAQi) no habilitado.'
        ];
    }

    /**
     * Genera el PDF de la factura basado en los datos del timbrado y el JSON enviado.
     */
    public function generarPdfManual(int $ventaId, array $data, array $cfdiJson): bool
    {
        try {
            $venta = Venta::with(['cliente'])->findOrFail($ventaId);
            $cfdi = Cfdi::where('venta_id', $ventaId)->first(); // Buscamos si ya existe o lo usaremos para metadata

            $comprobante = $cfdiJson['Comprobante'];
            $emisor = $comprobante['Emisor'];
            $receptor = $comprobante['Receptor'];

            // Enriquecer datos con descripciones de catálogos SAT si están disponibles
            try {
                $emisor['RegimenFiscalNombre'] = \App\Models\SatRegimenFiscal::where('clave', $emisor['RegimenFiscal'])->value('descripcion');
                $receptor['RegimenFiscalReceptorNombre'] = \App\Models\SatRegimenFiscal::where('clave', $receptor['RegimenFiscalReceptor'])->value('descripcion');
                $receptor['UsoCFDINombre'] = \App\Models\SatUsoCfdi::where('clave', $receptor['UsoCFDI'])->value('descripcion');
                $comprobante['FormaPagoNombre'] = \App\Models\SatFormaPago::where('clave', $comprobante['FormaPago'])->value('descripcion');
                $comprobante['MetodoPagoNombre'] = \App\Models\SatMetodoPago::where('clave', $comprobante['MetodoPago'])->value('descripcion');
            } catch (\Exception $e) {
                Log::warning("Error cargando descripciones SAT para PDF: " . $e->getMessage());
            }

            // Datos de la empresa (logo, etc)
            $empresaConfig = \App\Models\EmpresaConfiguracion::getInfoEmpresa();
            $colores = \App\Models\EmpresaConfiguracion::getColores();
            $color_principal = $colores['principal'] ?? '#2563eb';

            // Generar URL del QR SAT
            // https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=UUID&re=RFC_EMISOR&rr=RFC_RECEPTOR&tt=TOTAL&fe=SELLO_EMISOR_ULTIMOS_8
            $totalFixed = number_format($comprobante['Total'], 6, '.', '');
            $totalFixed = str_pad($totalFixed, 17, '0', STR_PAD_LEFT);
            $sello8 = substr($data['selloCFDI'], -8);

            $qr_url = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?" .
                "id=" . $data['uuid'] .
                "&re=" . $emisor['Rfc'] .
                "&rr=" . $receptor['Rfc'] .
                "&tt=" . $totalFixed .
                "&fe=" . $sello8;

            $pdfData = array_merge($data, [
                'serie' => $comprobante['Serie'] ?? '',
                'folio' => $comprobante['Folio'] ?? '',
            ]);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ventas.cfdi_pdf', [
                'venta' => $venta,
                'cfdi' => (object) $pdfData,
                'comprobante' => $comprobante,
                'emisor' => $emisor,
                'receptor' => $receptor,
                'data' => $data,
                'qr_url' => $qr_url,
                'empresa' => $empresaConfig,
                'color_principal' => $color_principal
            ]);

            $pdf->setPaper('letter', 'portrait');
            $pdfPath = 'cfdis/' . $data['uuid'] . '.pdf';
            Storage::disk('public')->put($pdfPath, $pdf->output());

            return true;
        } catch (\Exception $e) {
            Log::error("Error generando PDF manual de CFDI: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapea los valores internos de metodo_pago a claves SAT c_FormaPago.
     */
    private function mapFormaPago(?string $metodoPagoInterno): string
    {
        if (!$metodoPagoInterno) {
            return '99'; // Por definir
        }

        $mapeo = [
            'efectivo' => '01', // Efectivo
            'transferencia' => '03', // Transferencia electrónica de fondos
            'cheque' => '02', // Cheque nominativo
            'tarjeta' => '04', // Tarjeta de crédito (o 28 para débito)
            'tarjeta_credito' => '04',
            'tarjeta_debito' => '28',
            'otros' => '99', // Por definir
        ];

        return $mapeo[strtolower($metodoPagoInterno)] ?? '99';
    }

    /**
     * Realiza validaciones locales estrictas antes de enviar al PAC.
     * Esto ahorra costos y tiempos de respuesta.
     */
    private function validarPreTimbrado(Venta $venta, $cliente)
    {
        // 1. Validar Formato RFC (Persona Física o Moral)
        // Permitir RFCs genéricos (XAXX010101000, XEXX010101000)
        $rfc = strtoupper($cliente->rfc);
        if ($rfc !== 'XAXX010101000' && $rfc !== 'XEXX010101000') {
            if (!preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z\d]{3}$/', $rfc)) {
                throw new \Exception("El RFC '{$cliente->rfc}' no tiene un formato válido.");
            }
        }

        // 2. Validar Código Postal (Lugar de Expedición)
        $configuracion = \App\Models\EmpresaConfiguracion::getConfig();
        $cpEmisor = $configuracion->codigo_postal;

        // Check simple de longitud si no queremos consultar DB siempre
        if (strlen($cpEmisor) !== 5) {
            Log::warning("El CP de Emision $cpEmisor no parece válido (debe ser 5 dígitos).");
        }

        // 3. Reglas de Pago (PUE vs PPD)
        // PUE: Pago en Una sola Exhibición -> Forma de pago NO puede ser 99
        // PPD: Pago en Parcialidades o Diferido -> Forma de pago DEBE ser 99
        $metodoPagoSat = $venta->metodo_pago_sat ?: 'PUE'; // Default PUE
        $formaPagoSat = $venta->forma_pago_sat ?: $this->mapFormaPago($venta->metodo_pago);

        if ($metodoPagoSat === 'PUE' && $formaPagoSat === '99') {
            throw new \Exception("Error SAT: Si el método de pago es PUE (Pago en Una sola Exhibición), la forma de pago NO puede ser '99'. Debe especificar cómo se pagó.");
        }

        if ($metodoPagoSat === 'PPD' && $formaPagoSat !== '99') {
            throw new \Exception("Error SAT: Si el método de pago es PPD (Pago en Parcialidades o Diferido), la forma de pago DEBE ser '99' (Por definir).");
        }

        // 4. Reglas de Régimen Fiscal vs Uso CFDI
        // Si el régimen es 616 (Sin obligaciones), Uso debe ser S01 o CP01
        // Usamos el helper local ya que el del builder es privado
        $regimenCliente = $this->getRegimenFiscalClave($cliente->regimen_fiscal);

        if ($regimenCliente === '616' && $cliente->uso_cfdi !== 'S01' && $cliente->uso_cfdi !== 'CP01') {
            throw new \Exception("Error SAT: El régimen '616' (Sin obligaciones fiscales) solo permite el uso de CFDI 'S01' (Sin efectos fiscales).");
        }
    }

    private function getRegimenFiscalClave($regimen)
    {
        // Wrapper simple si jsonBuilder no es accesible públicamente o clonar lógica
        // Como jsonBuilder es protected, no podemos llamar su metodo privado directamente
        // Replicamos la lógica simple o hacemos public el metodo en JsonBuilder.
        // Por consistencia, replicaremos la logica basica aca o asumimos que jsonBuilder->getRegimenFiscalClave fuera público.
        // Dado que era privado en el archivo leido, mejor lo copio.

        if (empty($regimen))
            return '601';
        if (preg_match('/^\d{3}$/', $regimen))
            return $regimen;
        if (preg_match('/^(\d{3})/', $regimen, $matches))
            return $matches[1];
        return '601';
    }

    public function consultarEstadoSat(string $uuid, float $total, string $rfcEmisor, string $rfcReceptor): array
    {
        // En el futuro, si el bridge de Contpaqi soporta consulta de estado SAT,
        // podríamos agregarlo aquí. Por ahora, usamos la consulta directa al SAT.

        return $this->satConsultaDirecta->consultarEstado($uuid, $rfcEmisor, $rfcReceptor, $total);
    }

    /**
     * Solicita la cancelación de un CFDI.
     */
    public function cancelar(Cfdi $cfdi, string $motivo = '02', ?string $uuidSustitucion = null): array
    {
        if (config('services.contpaqi.enabled')) {
            $res = $this->contpaqiService->cancelarFactura($cfdi->uuid, $motivo, $uuidSustitucion);

            // Actualizar localmente si fue exitoso
            $cfdi->update([
                'estatus' => Cfdi::ESTATUS_CANCELADO, // O el que corresponda
                'motivo_cancelacion' => $motivo,
                'folio_sustitucion' => $uuidSustitucion,
                'fecha_cancelacion' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Factura cancelada exitosamente a través de Contpaqi Bridge.',
                'data' => $res
            ];
        }

        throw new \Exception("Servicio de cancelación no disponible (FacturaLOPlus fue removido).");
    }

    /**
     * Importa un archivo XML de CFDI.
     */
    public function importarXml(\Illuminate\Http\UploadedFile $file, string $direccion): Cfdi
    {
        if ($direccion === 'recibido') {
            return $this->uploadService->uploadFromXml($file);
        } else {
            // Lógica para importar emitidos si fuera necesario (usualmente ya están en el sistema)
            // Por ahora reusamos uploadFromXml pero marcamos como emitido si es el caso
            $cfdi = $this->uploadService->uploadFromXml($file);
            $cfdi->update(['direccion' => 'emitido']);
            return $cfdi;
        }
    }
}
