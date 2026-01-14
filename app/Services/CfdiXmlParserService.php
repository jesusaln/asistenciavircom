<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\SatClaveProdServ;
use App\Models\SatClaveUnidad;
use Illuminate\Support\Facades\Log;


class CfdiXmlParserService
{
    /**
     * Parsear contenido XML de un CFDI 4.0 y extraer información relevante
     *
     * @param string $xmlContent Contenido del archivo XML
     * @return array Datos extraídos del CFDI
     * @throws \Exception Si el XML no es válido o no es un CFDI
     */
    public function parseCfdiXml(string $xmlContent): array
    {
        // Limpiar espacios y BOM si existe
        $xmlContent = trim($xmlContent);
        $xmlContent = preg_replace('/^\xEF\xBB\xBF/', '', $xmlContent);

        // Cargar XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception('XML inválido: ' . ($errors[0]->message ?? 'Error desconocido'));
        }

        // Registrar namespaces del CFDI
        $namespaces = $xml->getNamespaces(true);
        $cfdiNs = $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
        $tfdNs = $namespaces['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital';

        $xml->registerXPathNamespace('cfdi', $cfdiNs);
        $xml->registerXPathNamespace('tfd', $tfdNs);
        // Register Payment namespaces
        $xml->registerXPathNamespace('pago10', 'http://www.sat.gob.mx/Pagos');
        $xml->registerXPathNamespace('pago20', 'http://www.sat.gob.mx/Pagos20');
        // Register Nomina namespace
        $xml->registerXPathNamespace('nomina12', 'http://www.sat.gob.mx/nomina12');

        // Extraer atributos del comprobante
        $attrs = $xml->attributes();

        // Extraer datos del Emisor (Proveedor)
        $emisor = $xml->xpath('//cfdi:Emisor')[0] ?? null;
        $emisorData = $emisor ? [
            'rfc' => trim((string) ($emisor['Rfc'] ?? '')),
            'nombre' => trim((string) ($emisor['Nombre'] ?? '')),
            'regimen_fiscal' => trim((string) ($emisor['RegimenFiscal'] ?? '')),
            'fac_atr_adquirente' => trim((string) ($emisor['FacAtrAdquirente'] ?? '')),
        ] : null;

        // Extraer datos del Receptor
        $receptor = $xml->xpath('//cfdi:Receptor')[0] ?? null;
        $receptorData = $receptor ? [
            'rfc' => trim((string) ($receptor['Rfc'] ?? '')),
            'nombre' => trim((string) ($receptor['Nombre'] ?? '')),
            'uso_cfdi' => trim((string) ($receptor['UsoCFDI'] ?? '')),
            'domicilio_fiscal' => trim((string) ($receptor['DomicilioFiscalReceptor'] ?? '')),
            'regimen_fiscal' => trim((string) ($receptor['RegimenFiscalReceptor'] ?? '')),
        ] : null;

        // Extraer conceptos
        $conceptosXml = $xml->xpath('//cfdi:Concepto');
        $conceptos = [];

        foreach ($conceptosXml as $concepto) {
            $conceptoAttrs = $concepto->attributes();
            $conceptoData = [
                'clave_prod_serv' => trim((string) ($conceptoAttrs['ClaveProdServ'] ?? '')),
                'no_identificacion' => trim((string) ($conceptoAttrs['NoIdentificacion'] ?? '')),
                'cantidad' => (float) ($conceptoAttrs['Cantidad'] ?? 0),
                'clave_unidad' => trim((string) ($conceptoAttrs['ClaveUnidad'] ?? '')),
                'unidad' => trim((string) ($conceptoAttrs['Unidad'] ?? '')),
                'descripcion' => trim((string) ($conceptoAttrs['Descripcion'] ?? '')),
                'valor_unitario' => (float) ($conceptoAttrs['ValorUnitario'] ?? 0),
                'importe' => (float) ($conceptoAttrs['Importe'] ?? 0),
                'descuento' => (float) ($conceptoAttrs['Descuento'] ?? 0),
                'objeto_imp' => trim((string) ($conceptoAttrs['ObjetoImp'] ?? '')),
                'impuestos' => ['traslados' => [], 'retenciones' => []],
            ];

            // Impuestos del concepto
            $trasladosConcepto = $concepto->xpath('.//cfdi:Traslado');
            foreach ($trasladosConcepto as $traslado) {
                $tAttrs = $traslado->attributes();
                $conceptoData['impuestos']['traslados'][] = [
                    'base' => (float) ($tAttrs['Base'] ?? 0),
                    'impuesto' => (string) ($tAttrs['Impuesto'] ?? ''),
                    'tipo_factor' => (string) ($tAttrs['TipoFactor'] ?? ''),
                    'tasa_o_cuota' => (float) ($tAttrs['TasaOCuota'] ?? 0),
                    'importe' => (float) ($tAttrs['Importe'] ?? 0),
                ];
            }

            $retencionesConcepto = $concepto->xpath('.//cfdi:Retencion');
            foreach ($retencionesConcepto as $retencion) {
                $rAttrs = $retencion->attributes();
                $conceptoData['impuestos']['retenciones'][] = [
                    'base' => (float) ($rAttrs['Base'] ?? 0),
                    'impuesto' => (string) ($rAttrs['Impuesto'] ?? ''),
                    'tipo_factor' => (string) ($rAttrs['TipoFactor'] ?? ''),
                    'tasa_o_cuota' => (float) ($rAttrs['TasaOCuota'] ?? 0),
                    'importe' => (float) ($rAttrs['Importe'] ?? 0),
                ];
            }

            $conceptos[] = $conceptoData;
        }

        // Extraer timbre fiscal (UUID)
        $timbre = $xml->xpath('//tfd:TimbreFiscalDigital')[0] ?? null;
        $timbreData = $timbre ? [
            'uuid' => strtolower(trim((string) ($timbre['UUID'] ?? ''))),
            'fecha_timbrado' => trim((string) ($timbre['FechaTimbrado'] ?? '')),
            'rfc_prov_certif' => trim((string) ($timbre['RfcProvCertif'] ?? '')),
            'no_certificado_sat' => trim((string) ($timbre['NoCertificadoSAT'] ?? '')),
            'sello_cfd' => trim((string) ($timbre['SelloCFD'] ?? '')),
            'sello_sat' => trim((string) ($timbre['SelloSAT'] ?? '')),
        ] : null;

        // Extraer totales de impuestos (del comprobante principal)
        $impuestos = $xml->xpath('/cfdi:Comprobante/cfdi:Impuestos')[0] ?? null;
        $impuestosData = [
            'total_impuestos_trasladados' => (float) ($impuestos['TotalImpuestosTrasladados'] ?? 0),
            'total_impuestos_retenidos' => (float) ($impuestos['TotalImpuestosRetenidos'] ?? 0),
            'traslados' => [],
            'retenciones' => [],
        ];

        if ($impuestos) {
            $traslados = $impuestos->xpath('cfdi:Traslados/cfdi:Traslado');
            foreach ($traslados as $traslado) {
                $tAttrs = $traslado->attributes();
                $impuestosData['traslados'][] = [
                    'base' => (float) ($tAttrs['Base'] ?? 0),
                    'impuesto' => (string) ($tAttrs['Impuesto'] ?? ''),
                    'tipo_factor' => (string) ($tAttrs['TipoFactor'] ?? ''),
                    'tasa_o_cuota' => (float) ($tAttrs['TasaOCuota'] ?? 0),
                    'importe' => (float) ($tAttrs['Importe'] ?? 0),
                ];
            }

            $retenciones = $impuestos->xpath('cfdi:Retenciones/cfdi:Retencion');
            foreach ($retenciones as $retencion) {
                $rAttrs = $retencion->attributes();
                $impuestosData['retenciones'][] = [
                    'impuesto' => (string) ($rAttrs['Impuesto'] ?? ''),
                    'importe' => (float) ($rAttrs['Importe'] ?? 0),
                ];
            }
        }

        // Extraer CFDIs relacionados (para notas de crédito, egresos por anticipo, etc.)
        $cfdiRelacionadosData = [];
        $cfdiRelacionadosNode = $xml->xpath('/cfdi:Comprobante/cfdi:CfdiRelacionados');
        if (!empty($cfdiRelacionadosNode)) {
            foreach ($cfdiRelacionadosNode as $relacionadosGroup) {
                $tipoRelacion = (string) ($relacionadosGroup['TipoRelacion'] ?? '');
                $uuids = [];
                foreach ($relacionadosGroup->xpath('cfdi:CfdiRelacionado') as $relacionado) {
                    $uuids[] = strtoupper(trim((string) ($relacionado['UUID'] ?? '')));
                }
                if (!empty($uuids)) {
                    $cfdiRelacionadosData[] = [
                        'tipo_relacion' => $tipoRelacion,
                        'uuids' => $uuids,
                    ];
                }
            }
        }

        return [
            'version' => trim((string) ($attrs['Version'] ?? '4.0')),
            'uuid' => $timbreData['uuid'] ?? null, // ✅ FIX: Exponer UUID en nivel superior
            'serie' => trim((string) ($attrs['Serie'] ?? '')),
            'folio' => trim((string) ($attrs['Folio'] ?? '')),
            'fecha' => trim((string) ($attrs['Fecha'] ?? '')),
            'forma_pago' => strtoupper(trim((string) ($attrs['FormaPago'] ?? ''))),
            'metodo_pago' => strtoupper(trim((string) ($attrs['MetodoPago'] ?? ''))),
            'tipo_comprobante' => strtoupper(trim((string) ($attrs['TipoDeComprobante'] ?? 'I'))),
            'moneda' => trim((string) ($attrs['Moneda'] ?? 'MXN')),
            'subtotal' => (float) ($attrs['SubTotal'] ?? 0),
            'descuento' => (float) ($attrs['Descuento'] ?? 0),
            'total' => (float) ($attrs['Total'] ?? 0),
            'lugar_expedicion' => trim((string) ($attrs['LugarExpedicion'] ?? '')),
            'exportacion' => trim((string) ($attrs['Exportacion'] ?? '')),
            'condiciones_de_pago' => trim((string) ($attrs['CondicionesDePago'] ?? '')),
            'emisor' => $emisorData,
            'receptor' => $receptorData,
            'conceptos' => $conceptos,
            'timbre' => $timbreData,
            'impuestos' => $impuestosData,
            'cfdi_relacionados' => $cfdiRelacionadosData,

            'complementos' => $this->extraerComplementos($xml), // Extract complements
            'es_factura_valida' => $this->esFacturaValida((string) ($attrs['TipoDeComprobante'] ?? '')),
            'tipo_comprobante_nombre' => $this->getNombreTipoComprobante((string) ($attrs['TipoDeComprobante'] ?? '')),
        ];
    }

    /**
     * Mapear conceptos del CFDI a productos existentes en el sistema
     *
     * @param array $conceptos Lista de conceptos del CFDI
     * @return array Conceptos con información de mapeo
     */
    public function mapConceptosToProductos(array $conceptos): array
    {
        return array_map(function ($concepto) {
            $match = $this->findProductoMatch($concepto);

            $noIdentificacion = trim($concepto['no_identificacion'] ?? '');
            $requiereSerie = $match['producto']?->requiere_serie ?? false;
            $seriales = [];

            // Si detectamos que el NoIdentificacion es un número de serie (formato CONTPAQI común)
            // y el producto encontrado requiere serie, lo pre-llenamos.
            if ($requiereSerie && preg_match('/^\d{4,8}-\d{1,4}-\d{3,6}$/', $noIdentificacion)) {
                $seriales[] = $noIdentificacion;
            }

            return array_merge($concepto, [
                'producto_id' => $match['producto']?->id,
                'producto_nombre' => $match['producto']?->nombre,
                'producto_codigo' => $match['producto']?->codigo,
                'match_type' => $match['type'], // 'exact', 'similar', 'none'
                'match_confidence' => $match['confidence'], // 0-100
                'requiere_serie' => $requiereSerie,
                'seriales' => $seriales,
            ]);
        }, $conceptos);
    }

    /**
     * Mapear conceptos con precarga de productos para reducir queries.
     *
     * @param array $conceptos
     * @return array
     */
    public function mapConceptosToProductosBulk(array $conceptos): array
    {
        $codes = [];
        $descriptions = [];
        $clavesSat = [];

        foreach ($conceptos as $concepto) {
            $noIdentificacion = trim($concepto['no_identificacion'] ?? '');
            $descripcion = trim($concepto['descripcion'] ?? '');
            $claveSat = trim($concepto['clave_prod_serv'] ?? '');

            if ($noIdentificacion !== '') {
                $codes[] = $noIdentificacion;
            }
            if ($descripcion !== '') {
                $descriptions[] = $descripcion;
            }
            if ($claveSat !== '') {
                $clavesSat[] = $claveSat;
            }
        }

        $codes = array_values(array_unique($codes));
        $descriptions = array_values(array_unique($descriptions));
        $clavesSat = array_values(array_unique($clavesSat));

        $productosPorCodigo = [];
        if (!empty($codes)) {
            $productos = Producto::where('estado', 'activo')
                ->where(function ($query) use ($codes) {
                    $query->whereIn('codigo', $codes)
                        ->orWhereIn('codigo_barras', $codes);
                })
                ->get();

            foreach ($productos as $producto) {
                if (!empty($producto->codigo)) {
                    $productosPorCodigo[$producto->codigo] = $producto;
                }
                if (!empty($producto->codigo_barras)) {
                    $productosPorCodigo[$producto->codigo_barras] = $producto;
                }
            }
        }

        $productosPorNombre = [];
        if (!empty($descriptions)) {
            $productos = Producto::where('estado', 'activo')
                ->whereIn('nombre', $descriptions)
                ->get();

            foreach ($productos as $producto) {
                if (!empty($producto->nombre)) {
                    $productosPorNombre[$producto->nombre] = $producto;
                }
            }
        }

        $productosPorClaveSat = [];
        if (!empty($clavesSat)) {
            $productos = Producto::where('estado', 'activo')
                ->whereIn('sat_clave_prod_serv', $clavesSat)
                ->get();

            foreach ($productos as $producto) {
                if (!empty($producto->sat_clave_prod_serv)) {
                    $productosPorClaveSat[$producto->sat_clave_prod_serv] = $producto;
                }
            }
        }

        return array_map(function ($concepto) use ($productosPorCodigo, $productosPorNombre, $productosPorClaveSat) {
            $noIdentificacion = trim($concepto['no_identificacion'] ?? '');
            $descripcion = trim($concepto['descripcion'] ?? '');
            $claveSat = trim($concepto['clave_prod_serv'] ?? '');

            if ($noIdentificacion !== '' && isset($productosPorCodigo[$noIdentificacion])) {
                $match = [
                    'producto' => $productosPorCodigo[$noIdentificacion],
                    'type' => 'exact',
                    'confidence' => 100,
                ];
            } elseif ($descripcion !== '' && isset($productosPorNombre[$descripcion])) {
                $match = [
                    'producto' => $productosPorNombre[$descripcion],
                    'type' => 'exact',
                    'confidence' => 100,
                ];
            } elseif ($claveSat !== '' && isset($productosPorClaveSat[$claveSat])) {
                $match = [
                    'producto' => $productosPorClaveSat[$claveSat],
                    'type' => 'similar',
                    'confidence' => 70,
                ];
            } else {
                $match = $this->findProductoMatch($concepto);
            }

            $requiereSerie = $match['producto']?->requiere_serie ?? false;
            $seriales = [];
            if ($requiereSerie && preg_match('/^\d{4,8}-\d{1,4}-\d{3,6}$/', $noIdentificacion)) {
                $seriales[] = $noIdentificacion;
            }

            return array_merge($concepto, [
                'producto_id' => $match['producto']?->id,
                'producto_nombre' => $match['producto']?->nombre,
                'producto_codigo' => $match['producto']?->codigo,
                'match_type' => $match['type'],
                'match_confidence' => $match['confidence'],
                'requiere_serie' => $requiereSerie,
                'seriales' => $seriales,
            ]);
        }, $conceptos);
    }

    /**
     * Buscar producto que coincida con el concepto del CFDI
     *
     * @param array $concepto Datos del concepto
     * @return array ['producto' => Producto|null, 'type' => string, 'confidence' => int]
     */
    private function findProductoMatch(array $concepto): array
    {
        $noIdentificacion = trim($concepto['no_identificacion'] ?? '');
        $descripcion = trim($concepto['descripcion'] ?? '');

        // 1. Búsqueda exacta por código
        if (!empty($noIdentificacion)) {
            $producto = Producto::where('estado', 'activo')
                ->where(function ($query) use ($noIdentificacion) {
                    $query->where('codigo', $noIdentificacion)
                        ->orWhere('codigo_barras', $noIdentificacion);
                })
                ->first();

            if ($producto) {
                return [
                    'producto' => $producto,
                    'type' => 'exact',
                    'confidence' => 100,
                ];
            }

            // 1.1 Buscar en productos eliminados (soft deleted) y restaurar si se encuentra
            $productoEliminado = Producto::withTrashed()
                ->whereNotNull('deleted_at')
                ->where(function ($query) use ($noIdentificacion) {
                    $query->where('codigo', $noIdentificacion)
                        ->orWhere('codigo_barras', $noIdentificacion);
                })
                ->first();

            if ($productoEliminado) {
                // Restaurar el producto
                $productoEliminado->restore();
                $productoEliminado->update(['estado' => 'activo']);

                \Log::info('Producto restaurado automáticamente desde soft-delete', [
                    'producto_id' => $productoEliminado->id,
                    'codigo' => $noIdentificacion,
                ]);

                return [
                    'producto' => $productoEliminado->fresh(),
                    'type' => 'exact',
                    'confidence' => 100,
                ];
            }
        }

        // 2. Búsqueda por nombre similar
        if (!empty($descripcion)) {
            // Limpiar descripción para búsqueda
            $searchTerm = $this->cleanSearchTerm($descripcion);

            // Búsqueda por coincidencia parcial
            $producto = Producto::where('estado', 'activo')
                ->where(function ($query) use ($searchTerm, $descripcion) {
                    $query->where('nombre', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('descripcion', 'LIKE', "%{$searchTerm}%");
                })
                ->first();

            if ($producto) {
                // Calcular confianza basada en similitud
                $confidence = $this->calculateSimilarity($descripcion, $producto->nombre);

                return [
                    'producto' => $producto,
                    'type' => $confidence >= 80 ? 'exact' : 'similar',
                    'confidence' => $confidence,
                ];
            }
        }

        // 3. Búsqueda por Clave SAT (si el producto tiene configurada una clave genérica)
        if (!empty($concepto['clave_prod_serv'])) {
            $producto = Producto::where('estado', 'activo')
                ->where('sat_clave_prod_serv', $concepto['clave_prod_serv'])
                ->first();

            if ($producto) {
                return [
                    'producto' => $producto,
                    'type' => 'similar',
                    'confidence' => 70,
                ];
            }
        }

        // 4. No se encontró coincidencia
        return [
            'producto' => null,
            'type' => 'none',
            'confidence' => 0,
        ];
    }

    /**
     * Buscar proveedor por RFC del emisor
     *
     * @param string $rfc RFC del emisor
     * @return Proveedor|null
     */
    public function findProveedorByRfc(string $rfc): ?Proveedor
    {
        if (empty($rfc)) {
            return null;
        }

        return Proveedor::where('rfc', $rfc)
            ->where('activo', true)
            ->first();
    }

    /**
     * Limpiar término de búsqueda
     */
    private function cleanSearchTerm(string $text): string
    {
        // Tomar las primeras palabras significativas
        $words = preg_split('/\s+/', $text);
        $significantWords = array_filter($words, function ($word) {
            return strlen($word) > 3;
        });

        return implode(' ', array_slice($significantWords, 0, 3));
    }

    /**
     * Calcular similitud entre dos cadenas (0-100)
     */
    private function calculateSimilarity(string $str1, string $str2): int
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 100;
        }

        similar_text($str1, $str2, $percent);

        return (int) $percent;
    }

    /**
     * Verificar si el tipo de comprobante es válido para importar como compra
     * Solo aceptamos facturas de Ingreso (I) o Egreso (E)
     */
    private function esFacturaValida(string $tipoComprobante): bool
    {
        return in_array(strtoupper($tipoComprobante), ['I', 'E']);
    }

    /**
     * Obtener nombre legible del tipo de comprobante
     */
    private function getNombreTipoComprobante(string $tipoComprobante): string
    {
        $tipos = [
            'I' => 'Ingreso (Factura)',
            'E' => 'Egreso (Nota de Crédito)',
            'T' => 'Traslado',
            'N' => 'Nómina',
            'P' => 'Pago (Complemento de Pago)',
        ];

        return $tipos[strtoupper($tipoComprobante)] ?? 'Desconocido';
    }

    /**
     * Poblar catálogos SAT desde los conceptos del XML
     * Agrega claves de productos/servicios y unidades que no existan
     * 
     * @param array $conceptos Lista de conceptos del CFDI
     * @return array Estadísticas de lo que se agregó
     */
    public function poblarCatalogosSatDesdeConceptos(array $conceptos): array
    {
        $stats = [
            'claves_prod_serv_nuevas' => 0,
            'claves_unidad_nuevas' => 0,
        ];

        foreach ($conceptos as $concepto) {
            // Poblar catálogo de productos/servicios
            $claveProdServ = $concepto['clave_prod_serv'] ?? '';
            $descripcion = $concepto['descripcion'] ?? '';

            if (!empty($claveProdServ) && strlen($claveProdServ) === 8) {
                $existeProdServ = SatClaveProdServ::find($claveProdServ);

                if (!$existeProdServ) {
                    SatClaveProdServ::create([
                        'clave' => $claveProdServ,
                        'descripcion' => $descripcion ?: "Importado desde XML - Clave {$claveProdServ}",
                        'incluir_iva_trasladado' => true,
                        'incluir_ieps_trasladado' => false,
                        'activo' => true,
                        'importado_xml' => true,
                    ]);
                    $stats['claves_prod_serv_nuevas']++;

                    Log::info("Catálogo SAT: Nueva clave producto/servicio agregada: {$claveProdServ}");
                }
            }

            // Poblar catálogo de unidades
            $claveUnidad = $concepto['clave_unidad'] ?? '';
            $unidadNombre = $concepto['unidad'] ?? '';

            if (!empty($claveUnidad)) {
                $existeUnidad = SatClaveUnidad::find($claveUnidad);

                if (!$existeUnidad) {
                    SatClaveUnidad::create([
                        'clave' => $claveUnidad,
                        'nombre' => $unidadNombre ?: $claveUnidad,
                        'descripcion' => "Importado desde XML",
                        'activo' => true,
                        'uso_comun' => false,
                    ]);
                    $stats['claves_unidad_nuevas']++;

                    Log::info("Catálogo SAT: Nueva clave unidad agregada: {$claveUnidad}");
                }
            }
        }

        return $stats;
    }
    /**
     * Extraer complementos (Pagos, etc.)
     */
    private function extraerComplementos($xml): array
    {
        $complementos = [];

        // --- Pagos 2.0 ---
        $pagos20 = $xml->xpath('//pago20:Pagos/pago20:Pago');
        if (!empty($pagos20)) {
            $listaPagos = [];
            foreach ($pagos20 as $pago) {
                $pagoAttrs = $pago->attributes();
                $doctosRelacionados = [];

                foreach ($pago->xpath('pago20:DoctoRelacionado') as $docto) {
                    $docAttrs = $docto->attributes();

                    // Extract ImpuestosDR
                    $impuestosDr = ['traslados' => [], 'retenciones' => []];

                    // TrasladosDR
                    foreach ($docto->xpath('pago20:ImpuestosDR/pago20:TrasladosDR/pago20:TrasladoDR') as $traslado) {
                        $tAttrs = $traslado->attributes();
                        $impuestosDr['traslados'][] = [
                            'base' => (float) ($tAttrs['BaseDR'] ?? 0),
                            'impuesto' => (string) ($tAttrs['ImpuestoDR'] ?? ''),
                            'tipo_factor' => (string) ($tAttrs['TipoFactorDR'] ?? ''),
                            'tasa_o_cuota' => (float) ($tAttrs['TasaOCuotaDR'] ?? 0),
                            'importe' => (float) ($tAttrs['ImporteDR'] ?? 0),
                        ];
                    }

                    // RetencionesDR
                    foreach ($docto->xpath('pago20:ImpuestosDR/pago20:RetencionesDR/pago20:RetencionDR') as $retencion) {
                        $rAttrs = $retencion->attributes();
                        $impuestosDr['retenciones'][] = [
                            'base' => (float) ($rAttrs['BaseDR'] ?? 0),
                            'impuesto' => (string) ($rAttrs['ImpuestoDR'] ?? ''),
                            'tipo_factor' => (string) ($rAttrs['TipoFactorDR'] ?? ''),
                            'tasa_o_cuota' => (float) ($rAttrs['TasaOCuotaDR'] ?? 0),
                            'importe' => (float) ($rAttrs['ImporteDR'] ?? 0),
                        ];
                    }

                    $doctosRelacionados[] = [
                        'id_documento' => trim((string) ($docAttrs['IdDocumento'] ?? '')),
                        'serie' => trim((string) ($docAttrs['Serie'] ?? '')),
                        'folio' => trim((string) ($docAttrs['Folio'] ?? '')),
                        'moneda_dr' => trim((string) ($docAttrs['MonedaDR'] ?? '')),
                        'equivalencia_dr' => (float) ($docAttrs['EquivalenciaDR'] ?? 1),
                        'num_parcialidad' => (int) ($docAttrs['NumParcialidad'] ?? 0),
                        'imp_saldo_ant' => (float) ($docAttrs['ImpSaldoAnt'] ?? 0),
                        'imp_pagado' => (float) ($docAttrs['ImpPagado'] ?? 0),
                        'imp_saldo_insoluto' => (float) ($docAttrs['ImpSaldoInsoluto'] ?? 0),
                        'objeto_imp_dr' => trim((string) ($docAttrs['ObjetoImpDR'] ?? '')),
                        'impuestos_dr' => $impuestosDr,
                    ];
                }

                // Extract ImpuestosP (Payment Taxes)
                $impuestosP = ['traslados' => [], 'retenciones' => []];

                foreach ($pago->xpath('pago20:ImpuestosP/pago20:TrasladosP/pago20:TrasladoP') as $trasladoP) {
                    $tpAttrs = $trasladoP->attributes();
                    $impuestosP['traslados'][] = [
                        'base' => (float) ($tpAttrs['BaseP'] ?? 0),
                        'impuesto' => (string) ($tpAttrs['ImpuestoP'] ?? ''),
                        'tipo_factor' => (string) ($tpAttrs['TipoFactorP'] ?? ''),
                        'tasa_o_cuota' => (float) ($tpAttrs['TasaOCuotaP'] ?? 0),
                        'importe' => (float) ($tpAttrs['ImporteP'] ?? 0),
                    ];
                }

                foreach ($pago->xpath('pago20:ImpuestosP/pago20:RetencionesP/pago20:RetencionP') as $retencionP) {
                    $rpAttrs = $retencionP->attributes();
                    $impuestosP['retenciones'][] = [
                        'impuesto' => (string) ($rpAttrs['ImpuestoP'] ?? ''),
                        'importe' => (float) ($rpAttrs['ImporteP'] ?? 0),
                    ];
                }

                $listaPagos[] = [
                    'version' => '2.0',
                    'fecha_pago' => trim((string) ($pagoAttrs['FechaPago'] ?? '')),
                    'forma_pago' => trim((string) ($pagoAttrs['FormaDePagoP'] ?? '')),
                    'moneda' => trim((string) ($pagoAttrs['MonedaP'] ?? '')),
                    'tipo_cambio' => (float) ($pagoAttrs['TipoCambioP'] ?? 1),
                    'monto' => (float) ($pagoAttrs['Monto'] ?? 0),
                    'num_operacion' => trim((string) ($pagoAttrs['NumOperacion'] ?? '')),
                    'rfc_emisor_cta_ord' => trim((string) ($pagoAttrs['RfcEmisorCtaOrd'] ?? '')),
                    'nom_banco_ord_ext' => trim((string) ($pagoAttrs['NomBancoOrdExt'] ?? '')),
                    'cta_ordenante' => trim((string) ($pagoAttrs['CtaOrdenante'] ?? '')),
                    'rfc_emisor_cta_ben' => trim((string) ($pagoAttrs['RfcEmisorCtaBen'] ?? '')),
                    'cta_beneficiario' => trim((string) ($pagoAttrs['CtaBeneficiario'] ?? '')),
                    'doctos_relacionados' => $doctosRelacionados,
                    'impuestos_p' => $impuestosP,
                ];
            }
            $complementos['pagos'] = $listaPagos;
        }

        // --- Pagos 1.0 (Fallback si no hay 2.0) ---
        if (empty($complementos['pagos'])) {
            $pagos10 = $xml->xpath('//pago10:Pagos/pago10:Pago');
            if (!empty($pagos10)) {
                $listaPagos = [];
                foreach ($pagos10 as $pago) {
                    $pagoAttrs = $pago->attributes();
                    $doctosRelacionados = [];

                    foreach ($pago->xpath('pago10:DoctoRelacionado') as $docto) {
                        $docAttrs = $docto->attributes();
                        $doctosRelacionados[] = [
                            'id_documento' => trim((string) ($docAttrs['IdDocumento'] ?? '')),
                            'serie' => trim((string) ($docAttrs['Serie'] ?? '')),
                            'folio' => trim((string) ($docAttrs['Folio'] ?? '')),
                            'moneda_dr' => trim((string) ($docAttrs['MonedaDR'] ?? '')),
                            'metodo_de_pago_dr' => trim((string) ($docAttrs['MetodoDePagoDR'] ?? '')),
                            'num_parcialidad' => (int) ($docAttrs['NumParcialidad'] ?? 0),
                            'imp_saldo_ant' => (float) ($docAttrs['ImpSaldoAnt'] ?? 0),
                            'imp_pagado' => (float) ($docAttrs['ImpPagado'] ?? 0),
                            'imp_saldo_insoluto' => (float) ($docAttrs['ImpSaldoInsoluto'] ?? 0),
                        ];
                    }

                    $listaPagos[] = [
                        'version' => '1.0',
                        'fecha_pago' => trim((string) ($pagoAttrs['FechaPago'] ?? '')),
                        'forma_pago' => trim((string) ($pagoAttrs['FormaDePagoP'] ?? '')),
                        'moneda' => trim((string) ($pagoAttrs['MonedaP'] ?? '')),
                        'tipo_cambio' => (float) ($pagoAttrs['TipoCambioP'] ?? 1),
                        'monto' => (float) ($pagoAttrs['Monto'] ?? 0),
                        'num_operacion' => trim((string) ($pagoAttrs['NumOperacion'] ?? '')),
                        'rfc_emisor_cta_ord' => trim((string) ($pagoAttrs['RfcEmisorCtaOrd'] ?? '')),
                        'cta_ordenante' => trim((string) ($pagoAttrs['CtaOrdenante'] ?? '')),
                        'rfc_emisor_cta_ben' => trim((string) ($pagoAttrs['RfcEmisorCtaBen'] ?? '')),
                        'cta_beneficiario' => trim((string) ($pagoAttrs['CtaBeneficiario'] ?? '')),
                        'doctos_relacionados' => $doctosRelacionados,
                    ];
                }
                $complementos['pagos'] = $listaPagos;
            }
        }

        // --- Nómina 1.2 ---
        $nominaNodes = $xml->xpath('//nomina12:Nomina');
        if (!empty($nominaNodes)) {
            $nomina = $nominaNodes[0];
            $nominaAttrs = $nomina->attributes();

            // Datos del receptor (empleado)
            $receptorNomina = $nomina->xpath('nomina12:Receptor')[0] ?? null;
            $receptorData = $receptorNomina ? [
                'curp' => trim((string) ($receptorNomina['Curp'] ?? '')),
                'num_seguridad_social' => trim((string) ($receptorNomina['NumSeguridadSocial'] ?? '')),
                'fecha_inicio_rel_laboral' => trim((string) ($receptorNomina['FechaInicioRelLaboral'] ?? '')),
                'antiguedad' => trim((string) ($receptorNomina['Antigüedad'] ?? $receptorNomina['Antiguedad'] ?? '')),
                'tipo_contrato' => trim((string) ($receptorNomina['TipoContrato'] ?? '')),
                'sindicalizado' => trim((string) ($receptorNomina['Sindicalizado'] ?? '')),
                'tipo_jornada' => trim((string) ($receptorNomina['TipoJornada'] ?? '')),
                'tipo_regimen' => trim((string) ($receptorNomina['TipoRegimen'] ?? '')),
                'num_empleado' => trim((string) ($receptorNomina['NumEmpleado'] ?? '')),
                'departamento' => trim((string) ($receptorNomina['Departamento'] ?? '')),
                'puesto' => trim((string) ($receptorNomina['Puesto'] ?? '')),
                'riesgo_puesto' => trim((string) ($receptorNomina['RiesgoPuesto'] ?? '')),
                'periodicidad_pago' => trim((string) ($receptorNomina['PeriodicidadPago'] ?? '')),
                'salario_base_cot_apor' => (float) ($receptorNomina['SalarioBaseCotApor'] ?? 0),
                'salario_diario_integrado' => (float) ($receptorNomina['SalarioDiarioIntegrado'] ?? 0),
                'clave_ent_fed' => trim((string) ($receptorNomina['ClaveEntFed'] ?? '')),
            ] : null;

            // Datos del emisor (patrón)
            $emisorNomina = $nomina->xpath('nomina12:Emisor')[0] ?? null;
            $emisorNominaData = $emisorNomina ? [
                'curp' => trim((string) ($emisorNomina['Curp'] ?? '')),
                'registro_patronal' => trim((string) ($emisorNomina['RegistroPatronal'] ?? '')),
            ] : null;

            // Percepciones
            $percepciones = [];
            $percepcionesNode = $nomina->xpath('nomina12:Percepciones')[0] ?? null;
            $percepcionesData = null;
            if ($percepcionesNode) {
                $percAttrs = $percepcionesNode->attributes();
                foreach ($percepcionesNode->xpath('nomina12:Percepcion') as $percepcion) {
                    $pAttrs = $percepcion->attributes();
                    $percepciones[] = [
                        'tipo' => trim((string) ($pAttrs['TipoPercepcion'] ?? '')),
                        'clave' => trim((string) ($pAttrs['Clave'] ?? '')),
                        'concepto' => trim((string) ($pAttrs['Concepto'] ?? '')),
                        'importe_gravado' => (float) ($pAttrs['ImporteGravado'] ?? 0),
                        'importe_exento' => (float) ($pAttrs['ImporteExento'] ?? 0),
                    ];
                }
                $percepcionesData = [
                    'total_sueldos' => (float) ($percAttrs['TotalSueldos'] ?? 0),
                    'total_gravado' => (float) ($percAttrs['TotalGravado'] ?? 0),
                    'total_exento' => (float) ($percAttrs['TotalExento'] ?? 0),
                    'detalle' => $percepciones,
                ];
            }

            // Deducciones
            $deducciones = [];
            $deduccionesNode = $nomina->xpath('nomina12:Deducciones')[0] ?? null;
            $deduccionesData = null;
            if ($deduccionesNode) {
                $dedAttrs = $deduccionesNode->attributes();
                foreach ($deduccionesNode->xpath('nomina12:Deduccion') as $deduccion) {
                    $dAttrs = $deduccion->attributes();
                    $deducciones[] = [
                        'tipo' => trim((string) ($dAttrs['TipoDeduccion'] ?? '')),
                        'clave' => trim((string) ($dAttrs['Clave'] ?? '')),
                        'concepto' => trim((string) ($dAttrs['Concepto'] ?? '')),
                        'importe' => (float) ($dAttrs['Importe'] ?? 0),
                    ];
                }
                $deduccionesData = [
                    'total_otras_deducciones' => (float) ($dedAttrs['TotalOtrasDeducciones'] ?? 0),
                    'total_impuestos_retenidos' => (float) ($dedAttrs['TotalImpuestosRetenidos'] ?? 0),
                    'detalle' => $deducciones,
                ];
            }

            // Otros Pagos
            $otrosPagos = [];
            foreach ($nomina->xpath('nomina12:OtrosPagos/nomina12:OtroPago') as $otroPago) {
                $opAttrs = $otroPago->attributes();
                $subsidio = $otroPago->xpath('nomina12:SubsidioAlEmpleo')[0] ?? null;
                $otrosPagos[] = [
                    'tipo' => trim((string) ($opAttrs['TipoOtroPago'] ?? '')),
                    'clave' => trim((string) ($opAttrs['Clave'] ?? '')),
                    'concepto' => trim((string) ($opAttrs['Concepto'] ?? '')),
                    'importe' => (float) ($opAttrs['Importe'] ?? 0),
                    'subsidio_causado' => $subsidio ? (float) ($subsidio['SubsidioCausado'] ?? 0) : null,
                ];
            }

            $complementos['nomina'] = [
                'version' => trim((string) ($nominaAttrs['Version'] ?? '1.2')),
                'tipo_nomina' => trim((string) ($nominaAttrs['TipoNomina'] ?? '')),
                'fecha_pago' => trim((string) ($nominaAttrs['FechaPago'] ?? '')),
                'fecha_inicial_pago' => trim((string) ($nominaAttrs['FechaInicialPago'] ?? '')),
                'fecha_final_pago' => trim((string) ($nominaAttrs['FechaFinalPago'] ?? '')),
                'num_dias_pagados' => (float) ($nominaAttrs['NumDiasPagados'] ?? 0),
                'total_percepciones' => (float) ($nominaAttrs['TotalPercepciones'] ?? 0),
                'total_deducciones' => (float) ($nominaAttrs['TotalDeducciones'] ?? 0),
                'total_otros_pagos' => (float) ($nominaAttrs['TotalOtrosPagos'] ?? 0),
                'emisor' => $emisorNominaData,
                'receptor' => $receptorData,
                'percepciones' => $percepcionesData,
                'deducciones' => $deduccionesData,
                'otros_pagos' => $otrosPagos,
            ];
        }

        return $complementos;
    }
}
