<?php

namespace App\Services\Cfdi;

use App\Models\Venta;
use App\Models\CuentasPorCobrar;
use App\Models\Cfdi;
use App\Models\EmpresaConfiguracion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CfdiPagoJsonBuilder
{
    protected $certService;

    public function __construct(CertService $certService)
    {
        $this->certService = $certService;
    }

    /**
     * Construye el JSON para un Complemento de Pago (REP 2.0)
     */
    public function build(CuentasPorCobrar $cxc, float $montoPago, string $formaPago, Carbon $fechaPago): array
    {
        $configuracion = EmpresaConfiguracion::getConfig();
        $venta = $cxc->cobrable;
        $cliente = $venta->cliente;
        
        // El CFDI Original (PPD) que estamos pagando
        $cfdiRelacionado = $venta->cfdis()
            ->timbrados()
            ->where('metodo_pago', 'PPD') // Solo se puede hacer REP de PPD
            ->latest() // Por si hay varios, tomamos el ultimo vigente
            ->first();

        if (!$cfdiRelacionado) {
            throw new \Exception("No se encontró una factura PPD timbrada para asociar este pago.");
        }

        // Datos del pago
        $moneda = 'MXN';
        $tipoCambioP = '1'; // Si moneda es MXN, tipo cambio es 1
        $formaPagoSat = $this->mapFormaPago($formaPago);
        
        // Cálculos de saldo e impuestos
        $saldoAnterior = $cxc->monto_total - $cxc->monto_pagado + $montoPago; // El saldo ANTES de este pago
        $impSaldoInsoluto = $saldoAnterior - $montoPago;
        $numParcialidad = $this->calcularParcialidad($venta);

        // Desglose proporcional de impuestos para el pago (REQUERIDO EN REP 2.0)
        // Se asume que TODO el pago va a capital e impuestos proporcionalmente (flujo estándar)
        $factor = $montoPago / $venta->total;
        
        // Base e Impuestos proporcionales pagados
        $baseDR = $venta->subtotal * $factor;
        $ivaDR = $venta->iva * $factor;
        
        $impuestosDR = [
             "TrasladosDR" => [
                 [
                     "BaseDR" => number_format($baseDR, 6, '.', ''),
                     "ImpuestoDR" => "002",
                     "TipoFactorDR" => "Tasa",
                     "TasaOCuotaDR" => number_format((\App\Services\EmpresaConfiguracionService::getIvaPorcentaje()/100), 6, '.', ''),
                     "ImporteDR" => number_format($ivaDR, 6, '.', '')
                 ]
             ]
        ];

        // Totales generales del complemento
        $totales = [
            "MontoTotalPagos" => number_format($montoPago, 2, '.', ''),
            "TotalRetencionesIVA" => "0", // Ajustar si hay retenciones
            "TotalRetencionesISR" => "0",
            "TotalTrasladosBaseIVA16" => number_format($baseDR, 2, '.', ''),
            "TotalTrasladosImpuestoIVA16" => number_format($ivaDR, 2, '.', ''),
        ];

        return [
            "Comprobante" => [
                "Version" => "4.0",
                "Serie" => "P", // Serie Pagos
                "Folio" => (string)time(), // Temporal, idealmente usar FolioGenerator
                "Fecha" => Carbon::now()->format('Y-m-d\TH:i:s'),
                "Sello" => "", // Firmado por PAC
                "NoCertificado" => $this->certService->getNoCertificado(),
                "Certificado" => "", // Puesto por PAC
                "SubTotal" => "0", // En REP siempre es 0
                "Moneda" => "XXX", // En REP nivel comprobante es XXX
                "Total" => "0",    // En REP siempre es 0
                "TipoDeComprobante" => "P",
                "Exportacion" => "01",
                "LugarExpedicion" => (string)$configuracion->codigo_postal,
                
                "Emisor" => [
                    "Rfc" => $configuracion->rfc,
                    "Nombre" => $configuracion->razon_social ?: $configuracion->nombre_empresa,
                    "RegimenFiscal" => $this->getRegimenFiscalClave($configuracion->regimen_fiscal)
                ],
                
                "Receptor" => [
                    "Rfc" => $cliente->rfc,
                    "Nombre" => $cliente->nombre_fiscal,
                    "DomicilioFiscalReceptor" => (string)($cliente->domicilio_fiscal_cp ?: $cliente->codigo_postal),
                    "RegimenFiscalReceptor" => $cliente->regimen_fiscal, // Mismo que la factura original
                    "UsoCFDI" => "CP01" // Obligatorio para Pagos
                ],
                
                "Conceptos" => [
                    [
                        "ClaveProdServ" => "84111506",
                        "Cantidad" => "1",
                        "ClaveUnidad" => "ACT",
                        "Descripcion" => "Pago",
                        "ValorUnitario" => "0",
                        "Importe" => "0",
                        "ObjetoImp" => "01"
                    ]
                ],

                "Complemento" => [
                    "Pagos" => [
                        "Version" => "2.0",
                        "Totales" => $totales,
                        "Pago" => [
                            [
                                "FechaPago" => $fechaPago->format('Y-m-d\TH:i:s'),
                                "FormaDePagoP" => $formaPagoSat,
                                "MonedaP" => $moneda,
                                "TipoCambioP" => $tipoCambioP,
                                "Monto" => number_format($montoPago, 2, '.', ''),
                                "DoctoRelacionado" => [
                                    [
                                        "IdDocumento" => $cfdiRelacionado->uuid,
                                        "Serie" => $cfdiRelacionado->serie,
                                        "Folio" => $cfdiRelacionado->folio,
                                        "MonedaDR" => $cfdiRelacionado->moneda ?: 'MXN',
                                        "EquivalenciaDR" => "1", // Asumimos misma moneda
                                        "NumParcialidad" => (string)$numParcialidad,
                                        "ImpSaldoAnt" => number_format($saldoAnterior, 2, '.', ''),
                                        "ImpPagado" => number_format($montoPago, 2, '.', ''),
                                        "ImpSaldoInsoluto" => number_format($impSaldoInsoluto, 2, '.', ''),
                                        "ObjetoImpDR" => "02", // Si fue objeto, asumimos si aqui
                                        "ImpuestosDR" => $impuestosDR
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function calcularParcialidad(Venta $venta): int
    {
        // Contar cuantos CFDI de tipo 'P' estan asociados a esta venta
        // O contar pagos previos. Simplificacion: 1 + historial pagos reportados
        // Lo ideal sería una tabla 'pago_cfdi_relacion', pero usaremos una query aproximada
        $pagosPrevios = Cfdi::where('venta_id', $venta->id)
            ->where('tipo_comprobante', 'P')
            ->where('estatus', '!=', 'cancelado')
            ->count();
        
        return $pagosPrevios + 1;
    }

    // Helper duplicado por consistencia rapida, idealmente trait o helper global
    private function mapFormaPago(?string $metodoPagoInterno): string
    {
        $mapeo = [
            'efectivo'      => '01',
            'transferencia' => '03',
            'cheque'        => '02',
            'tarjeta'       => '04',
            'otros'         => '99',
        ];

        return $mapeo[strtolower($metodoPagoInterno)] ?? '99';
    }

    private function getRegimenFiscalClave($regimen): string
    {
        if (empty($regimen)) return '601';
        if (preg_match('/^\d{3}$/', $regimen)) return $regimen;
        if (preg_match('/^(\d{3})/', $regimen, $matches)) return $matches[1];
        return '601';
    }
}
