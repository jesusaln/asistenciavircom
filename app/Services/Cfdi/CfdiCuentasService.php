<?php

namespace App\Services\Cfdi;

use App\Models\Cfdi;
use App\Models\Cliente;
use App\Models\CuentasPorCobrar;
use App\Models\CuentasPorPagar;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para crear Cuentas por Pagar/Cobrar desde CFDIs
 */
class CfdiCuentasService
{
    /**
     * Crear cuenta por pagar desde CFDI recibido
     *
     * @param Cfdi $cfdi CFDI recibido (de proveedor)
     * @param Proveedor $proveedor Proveedor asociado
     * @param array $datos Datos adicionales [fecha_vencimiento, notas]
     * @return CuentasPorPagar
     * @throws \InvalidArgumentException
     */
    public function crearCuentaPorPagar(Cfdi $cfdi, Proveedor $proveedor, array $datos = []): CuentasPorPagar
    {
        // Validar que el CFDI sea recibido
        if ($cfdi->direccion !== Cfdi::DIRECCION_RECIBIDO) {
            throw new \InvalidArgumentException('Solo se pueden crear cuentas por pagar desde CFDIs recibidos.');
        }

        // Validar que no tenga ya una cuenta vinculada
        if ($cfdi->cuentaPorPagar()->exists()) {
            throw new \InvalidArgumentException('Este CFDI ya tiene una cuenta por pagar vinculada.');
        }

        // Calcular fecha de vencimiento (default: 30 días desde fecha CFDI)
        $fechaBase = $cfdi->fecha_emision ?? now();
        $fechaVencimiento = isset($datos['fecha_vencimiento']) 
            ? Carbon::parse($datos['fecha_vencimiento'])
            : Carbon::parse($fechaBase)->addDays(30);

        $cuenta = CuentasPorPagar::create([
            'cfdi_id' => $cfdi->id,
            'proveedor_id' => $proveedor->id,
            'compra_id' => null, // Sin compra asociada
            'monto_total' => $cfdi->total,
            'monto_pagado' => 0,
            'monto_pendiente' => $cfdi->total,
            'fecha_vencimiento' => $fechaVencimiento,
            'estado' => 'pendiente',
            'notas' => $datos['notas'] ?? "Cuenta creada desde CFDI {$cfdi->uuid}",
        ]);

        Log::info('Cuenta por pagar creada desde CFDI', [
            'cuenta_id' => $cuenta->id,
            'cfdi_uuid' => $cfdi->uuid,
            'proveedor_id' => $proveedor->id,
            'monto' => $cfdi->total,
        ]);

        return $cuenta;
    }

    /**
     * Crear cuenta por cobrar desde CFDI emitido
     *
     * @param Cfdi $cfdi CFDI emitido (a cliente)
     * @param Cliente $cliente Cliente asociado
     * @param array $datos Datos adicionales [fecha_vencimiento, notas]
     * @return CuentasPorCobrar
     * @throws \InvalidArgumentException
     */
    public function crearCuentaPorCobrar(Cfdi $cfdi, Cliente $cliente, array $datos = []): CuentasPorCobrar
    {
        // Validar que el CFDI sea emitido
        if ($cfdi->direccion !== Cfdi::DIRECCION_EMITIDO) {
            throw new \InvalidArgumentException('Solo se pueden crear cuentas por cobrar desde CFDIs emitidos.');
        }

        // Validar que no tenga ya una cuenta vinculada
        if ($cfdi->cuentaPorCobrar()->exists()) {
            throw new \InvalidArgumentException('Este CFDI ya tiene una cuenta por cobrar vinculada.');
        }

        // Calcular fecha de vencimiento (default: 30 días desde fecha CFDI)
        $fechaBase = $cfdi->fecha_emision ?? now();
        $fechaVencimiento = isset($datos['fecha_vencimiento']) 
            ? Carbon::parse($datos['fecha_vencimiento'])
            : Carbon::parse($fechaBase)->addDays(30);

        $cuenta = CuentasPorCobrar::create([
            'cfdi_id' => $cfdi->id,
            'cliente_id' => $cliente->id,
            'cobrable_id' => null, // Sin venta asociada
            'cobrable_type' => null,
            'monto_total' => $cfdi->total,
            'monto_pagado' => 0,
            'monto_pendiente' => $cfdi->total,
            'fecha_vencimiento' => $fechaVencimiento,
            'estado' => 'pendiente',
            'notas' => $datos['notas'] ?? "Cuenta creada desde CFDI {$cfdi->uuid}",
        ]);

        Log::info('Cuenta por cobrar creada desde CFDI', [
            'cuenta_id' => $cuenta->id,
            'cfdi_uuid' => $cfdi->uuid,
            'cliente_id' => $cliente->id,
            'monto' => $cfdi->total,
        ]);

        return $cuenta;
    }

    /**
     * Buscar proveedor por RFC del emisor del CFDI
     *
     * @param string $rfc RFC del emisor
     * @param string|null $nombre Nombre del emisor (para crear si no existe)
     * @return array ['proveedor' => Proveedor|null, 'encontrado' => bool]
     */
    public function buscarProveedorPorRfc(string $rfc, ?string $nombre = null, ?string $regimenFiscal = null, ?string $codigoPostal = null): array
    {
        $proveedor = Proveedor::where('rfc', $rfc)->first();

        return [
            'proveedor' => $proveedor,
            'encontrado' => $proveedor !== null,
            'datos_sugeridos' => $proveedor ? null : [
                'rfc' => $rfc,
                'nombre_razon_social' => $nombre ?? '',
                'regimen_fiscal' => $regimenFiscal,
                'codigo_postal' => $codigoPostal,
            ],
        ];
    }

    /**
     * Crear proveedor desde datos del CFDI
     *
     * @param array $datos Datos del proveedor
     * @return Proveedor
     */
    public function crearProveedorDesdeCfdi(array $datos): Proveedor
    {
        $rfc = $datos['rfc'];
        $tipoPersona = strlen($rfc) === 12 ? 'moral' : 'fisica';

        $proveedor = Proveedor::create([
            'rfc' => $rfc,
            'nombre_razon_social' => $datos['nombre_razon_social'] ?? $datos['nombre'] ?? 'Proveedor sin nombre',
            'tipo_persona' => $tipoPersona,
            'nombre_comercial' => $datos['nombre_comercial'] ?? null,
            'email' => $datos['email'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'regimen_fiscal' => $datos['regimen_fiscal'] ?? null,
            'codigo_postal' => $datos['codigo_postal'] ?? null,
            'uso_cfdi' => $datos['uso_cfdi'] ?? 'G03',
            'activo' => true,
            'notas' => 'Creado automáticamente desde importación de CFDI',
        ]);

        Log::info('Proveedor creado desde CFDI', [
            'proveedor_id' => $proveedor->id,
            'rfc' => $proveedor->rfc,
            'tipo_persona' => $tipoPersona,
        ]);

        return $proveedor;
    }

    /**
     * Buscar cliente por RFC del receptor del CFDI
     *
     * @param string $rfc RFC del receptor
     * @param string|null $nombre Nombre del receptor (para crear si no existe)
     * @return array ['cliente' => Cliente|null, 'encontrado' => bool]
     */
    public function buscarClientePorRfc(string $rfc, ?string $nombre = null, ?string $regimenFiscal = null, ?string $codigoPostal = null): array
    {
        $cliente = Cliente::where('rfc', $rfc)->first();

        return [
            'cliente' => $cliente,
            'encontrado' => $cliente !== null,
            'datos_sugeridos' => $cliente ? null : [
                'rfc' => $rfc,
                'nombre' => $nombre ?? '',
                'regimen_fiscal' => $regimenFiscal,
                'codigo_postal' => $codigoPostal,
            ],
        ];
    }

    /**
     * Crear cliente desde datos del CFDI
     *
     * @param array $datos Datos del cliente
     * @return Cliente
     */
    public function crearClienteDesdeCfdi(array $datos): Cliente
    {
        $rfc = $datos['rfc'];
        $tipoPersona = strlen($rfc) === 12 ? 'moral' : 'fisica';

        $cliente = Cliente::create([
            'rfc' => $rfc,
            'nombre' => $datos['nombre'] ?? 'Cliente sin nombre',
            'razon_social' => $datos['razon_social'] ?? $datos['nombre'] ?? null,
            'tipo_persona' => $tipoPersona,
            'email' => $datos['email'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'codigo_postal' => $datos['codigo_postal'] ?? null,
            'domicilio_fiscal_cp' => $datos['codigo_postal'] ?? null,
            'regimen_fiscal' => $datos['regimen_fiscal'] ?? null,
            'uso_cfdi' => $datos['uso_cfdi'] ?? 'G03',
            'notas' => 'Creado automáticamente desde importación de CFDI',
        ]);

        Log::info('Cliente creado desde CFDI', [
            'cliente_id' => $cliente->id,
            'rfc' => $cliente->rfc,
            'tipo_persona' => $tipoPersona,
        ]);

        return $cliente;
    }

    /**
     * Preparar datos del CFDI para el modal de creación de cuenta
     *
     * @param Cfdi $cfdi
     * @return array
     */
    public function prepararDatosCuenta(Cfdi $cfdi): array
    {
        $esRecibido = $cfdi->direccion === Cfdi::DIRECCION_RECIBIDO;

        // Obtener datos del emisor/receptor
        $datosAdicionales = $cfdi->datos_adicionales ?? [];

        if ($esRecibido) {
            // CFDI recibido: el emisor es el proveedor
            $emisor = $datosAdicionales['emisor'] ?? [];
            $rfc = $cfdi->rfc_emisor;
            $nombre = $cfdi->nombre_emisor;
            $regimenFiscal = $emisor['regimen_fiscal'] ?? null;
            $codigoPostal = $datosAdicionales['lugar_expedicion'] ?? null;

            $resultado = $this->buscarProveedorPorRfc($rfc, $nombre, $regimenFiscal, $codigoPostal);

            return [
                'cfdi' => [
                    'id' => $cfdi->id,
                    'uuid' => $cfdi->uuid,
                    'folio' => $cfdi->serie_folio,
                    'fecha_emision' => $cfdi->fecha_emision?->format('Y-m-d'),
                    'total' => $cfdi->total,
                    'direccion' => $cfdi->direccion,
                    'tipo_comprobante' => $cfdi->tipo_comprobante,
                ],
                'tipo_cuenta' => 'pagar',
                'entidad' => [
                    'tipo' => 'proveedor',
                    'encontrado' => $resultado['encontrado'],
                    'datos' => $resultado['proveedor'] ? [
                        'id' => $resultado['proveedor']->id,
                        'nombre' => $resultado['proveedor']->nombre_razon_social,
                        'rfc' => $resultado['proveedor']->rfc,
                        'regimen_fiscal' => $resultado['proveedor']->regimen_fiscal,
                        'codigo_postal' => $resultado['proveedor']->codigo_postal,
                    ] : null,
                    'datos_sugeridos' => $resultado['datos_sugeridos'],
                ],
                'fecha_vencimiento_sugerida' => Carbon::parse($cfdi->fecha_emision ?? now())->addDays(30)->format('Y-m-d'),
                'tiene_cuenta' => $cfdi->cuentaPorPagar()->exists(),
            ];
        } else {
            // CFDI emitido: el receptor es el cliente
            $receptor = $datosAdicionales['receptor'] ?? [];
            $rfc = $receptor['Rfc'] ?? $receptor['rfc'] ?? '';
            $nombre = $receptor['Nombre'] ?? $receptor['nombre'] ?? '';
            $regimenFiscal = $receptor['regimen_fiscal'] ?? $receptor['RegimenFiscalReceptor'] ?? null;
            $codigoPostal = $receptor['domicilio_fiscal'] ?? $receptor['DomicilioFiscalReceptor'] ?? null;
            
            // Si hay cliente_id en el CFDI, usarlo directamente
            if ($cfdi->cliente_id) {
                $cliente = Cliente::find($cfdi->cliente_id);
                $resultado = [
                    'cliente' => $cliente,
                    'encontrado' => $cliente !== null,
                    'datos_sugeridos' => null,
                ];
            } else {
                $resultado = $this->buscarClientePorRfc($rfc, $nombre, $regimenFiscal, $codigoPostal);
            }

            return [
                'cfdi' => [
                    'id' => $cfdi->id,
                    'uuid' => $cfdi->uuid,
                    'folio' => $cfdi->serie_folio,
                    'fecha_emision' => $cfdi->fecha_emision?->format('Y-m-d'),
                    'total' => $cfdi->total,
                    'direccion' => $cfdi->direccion,
                    'tipo_comprobante' => $cfdi->tipo_comprobante,
                ],
                'tipo_cuenta' => 'cobrar',
                'entidad' => [
                    'tipo' => 'cliente',
                    'encontrado' => $resultado['encontrado'],
                    'datos' => $resultado['cliente'] ? [
                        'id' => $resultado['cliente']->id,
                        'nombre' => $resultado['cliente']->nombre ?? $resultado['cliente']->razon_social,
                        'rfc' => $resultado['cliente']->rfc,
                        'regimen_fiscal' => $resultado['cliente']->regimen_fiscal,
                        'codigo_postal' => $resultado['cliente']->domicilio_fiscal_cp ?? $resultado['cliente']->codigo_postal,
                    ] : null,
                    'datos_sugeridos' => $resultado['datos_sugeridos'] ?? [
                        'rfc' => $rfc,
                        'nombre' => $nombre,
                        'regimen_fiscal' => $regimenFiscal,
                        'codigo_postal' => $codigoPostal,
                    ],
                ],
                'fecha_vencimiento_sugerida' => Carbon::parse($cfdi->fecha_emision ?? now())->addDays(30)->format('Y-m-d'),
                'tiene_cuenta' => $cfdi->cuentaPorCobrar()->exists(),
            ];
        }
    }
}
