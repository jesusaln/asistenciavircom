<?php

namespace App\Services\Cfdi;

use App\Models\Venta;
use App\Models\EmpresaConfiguracion;
use App\Models\Cliente;
use App\Models\VentaItem;
use App\Models\Producto;
use App\Models\Servicio;
use Carbon\Carbon;

class CfdiJsonBuilder
{
    protected $certService;

    public function __construct(CertService $certService)
    {
        $this->certService = $certService;
    }

    /**
     * Construye el JSON estructurado para el PAC FacturaLO Plus basado en una Venta.
     */
    public function buildFromVenta(Venta $venta, array $options = []): array
    {
        $configuracion = EmpresaConfiguracion::getConfig();
        $cliente = $venta->cliente;
        
        // Cargar ítems con sus relaciones para evitar N+1
        $venta->load('items.ventable');

        $json = [
            "Comprobante" => [
                "Version" => "4.0",
                "Serie" => "V", // Serie de Ventas
                "Folio" => (string)$venta->id,
                "Fecha" => Carbon::now()->format('Y-m-d\TH:i:s'),
                "NoCertificado" => $this->certService->getNoCertificado(),
                "SubTotal" => number_format($venta->subtotal, 2, '.', ''),
                "Moneda" => $venta->moneda ?: $configuracion->moneda ?: 'MXN',
                "Total" => number_format($venta->total, 2, '.', ''),
                "TipoDeComprobante" => "I",
                "Exportacion" => "01", // No aplica por defecto
                "LugarExpedicion" => (string)$configuracion->codigo_postal,
                "MetodoPago" => $venta->metodo_pago_sat ?: ($venta->metodo_pago === 'credito' ? 'PPD' : 'PUE'),
                "FormaPago" => $venta->forma_pago_sat ?: $this->mapFormaPago($venta->metodo_pago) ?: '99',
                
                "Emisor" => [
                    "Rfc" => $configuracion->rfc,
                    "Nombre" => $configuracion->razon_social ?: $configuracion->nombre_empresa,
                    "RegimenFiscal" => $this->getRegimenFiscalClave($configuracion->regimen_fiscal)
                ],
                
                "Receptor" => $this->buildReceptor($cliente),
                
                "Conceptos" => [],
            ]
        ];

        $conceptosData = $this->buildConceptos($venta->items, (float) $venta->descuento_general);
        $json["Comprobante"]["Conceptos"] = $conceptosData['conceptos'];

        $impuestos = $this->buildImpuestosGlobales($venta, $conceptosData['totals']);
        if (!empty($impuestos)) {
            $json["Comprobante"]["Impuestos"] = $impuestos;
        }

        if ($conceptosData['totals']['descuento_total'] > 0) {
            $json["Comprobante"]["Descuento"] = number_format($conceptosData['totals']['descuento_total'], 2, '.', '');
        }

        if (!empty($options['cfdi_relacion_tipo']) && !empty($options['cfdi_relacion_uuids'])) {
            $uuids = array_values(array_filter(array_map('trim', $options['cfdi_relacion_uuids'])));
            if (!empty($uuids)) {
                $json["Comprobante"]["CfdiRelacionados"] = [
                    "TipoRelacion" => $options['cfdi_relacion_tipo'],
                    "CfdiRelacionado" => array_map(function ($uuid) {
                        return ["UUID" => $uuid];
                    }, $uuids),
                ];
            }
        }

        return $json;
    }

    /**
     * Construye el JSON para un CFDI de Anticipo (Ingreso).
     */
    public function buildAnticipo(Venta $venta, float $montoTotal, string $metodoPagoInterno): array
    {
        $configuracion = EmpresaConfiguracion::getConfig();
        $cliente = $venta->cliente;

        $ivaRateDecimal = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
        $base = $montoTotal / (1 + $ivaRateDecimal);
        $iva = $montoTotal - $base;

        $formaPagoSat = $venta->forma_pago_sat ?: $this->mapFormaPago($metodoPagoInterno) ?: '99';

        $concepto = [
            "ClaveProdServ" => "84111506",
            "Cantidad" => "1",
            "ClaveUnidad" => "ACT",
            "Descripcion" => "Anticipo",
            "ValorUnitario" => number_format($base, 2, '.', ''),
            "Importe" => number_format($base, 2, '.', ''),
            "ObjetoImp" => "02",
            "Impuestos" => [
                "Traslados" => [
                    [
                        "Base" => number_format($base, 2, '.', ''),
                        "Impuesto" => "002",
                        "TipoFactor" => "Tasa",
                        "TasaOCuota" => number_format($ivaRateDecimal, 6, '.', ''),
                        "Importe" => number_format($iva, 2, '.', ''),
                    ]
                ]
            ]
        ];

        return [
            "Comprobante" => [
                "Version" => "4.0",
                "Serie" => "V",
                "Folio" => (string)$venta->id,
                "Fecha" => Carbon::now()->format('Y-m-d\TH:i:s'),
                "NoCertificado" => $this->certService->getNoCertificado(),
                "SubTotal" => number_format($base, 2, '.', ''),
                "Moneda" => $venta->moneda ?: $configuracion->moneda ?: 'MXN',
                "Total" => number_format($montoTotal, 2, '.', ''),
                "TipoDeComprobante" => "I",
                "Exportacion" => "01",
                "LugarExpedicion" => (string)$configuracion->codigo_postal,
                "MetodoPago" => "PUE",
                "FormaPago" => $formaPagoSat,
                "Emisor" => [
                    "Rfc" => $configuracion->rfc,
                    "Nombre" => $configuracion->razon_social ?: $configuracion->nombre_empresa,
                    "RegimenFiscal" => $this->getRegimenFiscalClave($configuracion->regimen_fiscal)
                ],
                "Receptor" => $this->buildReceptor($cliente),
                "Conceptos" => [$concepto],
                "Impuestos" => [
                    "TotalImpuestosTrasladados" => number_format($iva, 2, '.', ''),
                    "Traslados" => [
                        [
                            "Base" => number_format($base, 2, '.', ''),
                            "Impuesto" => "002",
                            "TipoFactor" => "Tasa",
                            "TasaOCuota" => number_format($ivaRateDecimal, 6, '.', ''),
                            "Importe" => number_format($iva, 2, '.', ''),
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Construye la sección de impuestos a nivel comprobante.
     */
    private function buildImpuestosGlobales(Venta $venta, array $totals): array
    {
        $retenciones = [];
        if ($venta->retencion_iva > 0) {
            $retenciones[] = [
                "Impuesto" => "002",
                "Importe" => number_format($venta->retencion_iva, 2, '.', '')
            ];
        }
        if ($venta->retencion_isr > 0 || $venta->isr > 0) {
            $retenciones[] = [
                "Impuesto" => "001",
                "Importe" => number_format($venta->retencion_isr ?: $venta->isr, 2, '.', '')
            ];
        }

        $impuestos = [];

        if ($totals['traslado_importe'] > 0) {
            $ivaRateRaw = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje();
            $ivaRateDec = $ivaRateRaw / 100;
            $ivaRateStr = number_format($ivaRateDec, 6, '.', '');

            $impuestos["TotalImpuestosTrasladados"] = number_format($totals['traslado_importe'], 2, '.', '');
            $impuestos["Traslados"] = [
                [
                    "Base" => number_format($totals['traslado_base'], 2, '.', ''),
                    "Impuesto" => "002",
                    "TipoFactor" => "Tasa",
                    "TasaOCuota" => $ivaRateStr,
                    "Importe" => number_format($totals['traslado_importe'], 2, '.', '')
                ]
            ];
        }

        if (!empty($retenciones)) {
            $impuestos["TotalImpuestosRetenidos"] = number_format($venta->retencion_iva + ($venta->retencion_isr ?: $venta->isr), 2, '.', '');
            $impuestos["Retenciones"] = $retenciones;
        }

        return $impuestos;
    }

    /**
     * Construye el array de conceptos para el JSON.
     */
    private function buildConceptos($items, float $descuentoGeneral): array
    {
        $conceptos = [];
        $ivaRateDecimal = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
        $ivaRateStr = number_format($ivaRateDecimal, 6, '.', '');

        $totals = [
            'descuento_total' => 0.0,
            'traslado_base' => 0.0,
            'traslado_importe' => 0.0,
        ];

        $itemBases = [];
        $totalBaseAfterItemDiscount = 0.0;

        foreach ($items as $item) {
            $base = $item->precio * $item->cantidad;
            $descuentoItem = $item->descuento_monto ?: 0;
            $baseConDescuento = $base - $descuentoItem;
            $itemBases[] = $baseConDescuento;
            $totalBaseAfterItemDiscount += $baseConDescuento;
        }

        $globalDiscount = max(0, $descuentoGeneral);
        $globalAllocated = 0.0;

        foreach ($items as $item) {
            $ventable = $item->ventable;
            
            // Determinar claves SAT
            $claveProdServ = '01010101'; // Default: No existe en catálogo
            $claveUnidad = 'H87'; // Default: Pieza
            $objetoImp = '02'; // Default: Sí objeto de impuesto

            if ($ventable instanceof Producto || $ventable instanceof Servicio) {
                $claveProdServ = $ventable->sat_clave_prod_serv ?: $claveProdServ;
                $claveUnidad = $ventable->sat_clave_unidad ?: $claveUnidad;
                $objetoImp = $ventable->sat_objeto_imp ?: $objetoImp;
            }

            // Cálculo de IVA proporcional para este concepto
            $base = $item->precio * $item->cantidad;
            $descuentoItem = $item->descuento_monto ?: 0;
            $baseConDescuento = $base - $descuentoItem;

            $allocGlobal = 0.0;
            if ($globalDiscount > 0 && $totalBaseAfterItemDiscount > 0) {
                $ratio = $baseConDescuento / $totalBaseAfterItemDiscount;
                $allocGlobal = $globalDiscount * $ratio;
            }

            $globalAllocated += $allocGlobal;
            $descuentoConcepto = $descuentoItem + $allocGlobal;
            $baseConDescuento = $base - $descuentoConcepto;
            
            // Si el objetoImp es '01' (No objeto de impuesto), el IVA es 0
            $iva = ($objetoImp === '01') ? 0 : ($baseConDescuento * $ivaRateDecimal);

            $concepto = [
                "ClaveProdServ" => (string)$claveProdServ,
                "Cantidad" => (string)$item->cantidad,
                "ClaveUnidad" => (string)$claveUnidad,
                "Descripcion" => (string)($ventable->nombre ?? $ventable->descripcion ?? 'Concepto sin nombre'),
                "ValorUnitario" => number_format($item->precio, 2, '.', ''),
                "Importe" => number_format($base, 2, '.', ''),
                "ObjetoImp" => (string)$objetoImp,
            ];

            // Solo agregar sección de Impuestos si es objeto de impuesto (02)
            if ($objetoImp === '02') {
                $concepto["Impuestos"] = [
                    "Traslados" => [
                        [
                            "Base" => number_format($baseConDescuento, 2, '.', ''),
                            "Impuesto" => "002",
                            "TipoFactor" => "Tasa",
                            "TasaOCuota" => $ivaRateStr,
                            "Importe" => number_format($iva, 2, '.', '')
                        ]
                    ]
                ];

                $totals['traslado_base'] += $baseConDescuento;
                $totals['traslado_importe'] += $iva;
            } elseif ($objetoImp === '03') {
                $concepto["Impuestos"] = [
                    "Traslados" => [
                        [
                            "Base" => number_format($baseConDescuento, 2, '.', ''),
                            "Impuesto" => "002",
                            "TipoFactor" => "Exento"
                        ]
                    ]
                ];
            }

            if ($descuentoConcepto > 0) {
                $concepto["Descuento"] = number_format($descuentoConcepto, 2, '.', '');
            }

            // Retenciones por concepto si aplican (ej. Fletes, Honorarios)
            // Por ahora el sistema maneja retenciones globales en Venta, 
            // pero si se requiere por concepto se implementaría aquí.

            $totals['descuento_total'] += $descuentoConcepto;

            $conceptos[] = $concepto;
        }

        if ($globalDiscount > 0 && $totalBaseAfterItemDiscount > 0) {
            $ajuste = $globalDiscount - $globalAllocated;
            if (abs($ajuste) >= 0.01 && !empty($conceptos)) {
                $lastIndex = count($conceptos) - 1;
                if (isset($conceptos[$lastIndex]["Descuento"])) {
                    $conceptos[$lastIndex]["Descuento"] = number_format(
                        max(0, (float) $conceptos[$lastIndex]["Descuento"] + $ajuste),
                        2,
                        '.',
                        ''
                    );
                    $totals['descuento_total'] += $ajuste;
                }
            }
        }

        return [
            'conceptos' => $conceptos,
            'totals' => $totals,
        ];
    }

    private function buildReceptor(Cliente $cliente): array
    {
        $receptor = [
            "Rfc" => $cliente->rfc,
            "Nombre" => $cliente->nombre_fiscal,
            "DomicilioFiscalReceptor" => (string)($cliente->domicilio_fiscal_cp ?: $cliente->codigo_postal),
            "RegimenFiscalReceptor" => $cliente->regimen_fiscal ?: '616',
            "UsoCFDI" => $cliente->uso_cfdi ?: 'S01'
        ];

        if ($cliente->rfc === 'XEXX010101000' || $cliente->residencia_fiscal) {
            if ($cliente->residencia_fiscal) {
                $receptor["ResidenciaFiscal"] = $cliente->residencia_fiscal;
            }
            if ($cliente->num_reg_id_trib) {
                $receptor["NumRegIdTrib"] = $cliente->num_reg_id_trib;
            }
        }

        return $receptor;
    }

    /**
     * Mapea el régimen fiscal a su clave SAT de 3 dígitos.
     * Si ya es una clave válida (ej: "626"), la devuelve directamente.
     * Si es un nombre descriptivo, intenta mapearlo.
     */
    private function getRegimenFiscalClave($regimen): string
    {
        if (empty($regimen)) {
            return '601'; // Default: General de Ley Personas Morales
        }

        // Si ya es una clave numérica de 3 dígitos, usarla directamente
        if (preg_match('/^\d{3}$/', $regimen)) {
            return $regimen;
        }

        // Si empieza con un número de 3 dígitos (ej: "626 - Régimen..."), extraer la clave
        if (preg_match('/^(\d{3})/', $regimen, $matches)) {
            return $matches[1];
        }
        
        // Intentar mapear por nombre descriptivo
        $nombre = strtolower($regimen);
        
        if (str_contains($nombre, 'simplificado de confianza') || str_contains($nombre, 'resico')) return '626';
        if (str_contains($nombre, 'personas físicas con actividades empresariales')) return '612';
        if (str_contains($nombre, 'sueldos y salarios')) return '605';
        if (str_contains($nombre, 'general de ley personas morales')) return '601';
        if (str_contains($nombre, 'enajenación o adquisición de bienes')) return '614';
        if (str_contains($nombre, 'incorporación fiscal')) return '621';
        if (str_contains($nombre, 'arrendamiento')) return '606';
        if (str_contains($nombre, 'sin obligaciones')) return '616';
        
        return '601'; // Default
    }

    /**
     * Mapea los valores internos de metodo_pago a claves SAT c_FormaPago.
     * Esto es un fallback cuando forma_pago_sat no está definido.
     */
    private function mapFormaPago(?string $metodoPagoInterno): string
    {
        if (!$metodoPagoInterno) {
            return '99'; // Por definir
        }

        $mapeo = [
            'efectivo'      => '01', // Efectivo
            'transferencia' => '03', // Transferencia electrónica de fondos
            'cheque'        => '02', // Cheque nominativo
            'tarjeta'       => '04', // Tarjeta de crédito (o 28 para débito)
            'otros'         => '99', // Por definir
        ];

        return $mapeo[strtolower($metodoPagoInterno)] ?? '99';
    }
}
