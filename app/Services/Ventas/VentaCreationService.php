<?php

namespace App\Services\Ventas;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use App\Models\CuentasPorCobrar;
use App\Support\EmpresaResolver;
use App\Services\StockValidationService;
use App\Services\Folio\FolioService;
use App\Services\PaymentService;
use App\Services\FinancialService;
use App\Services\Ventas\VentaItemsProcessor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaCreationService
{
    public function __construct(
        private readonly StockValidationService $stockValidationService,
        private readonly FolioService $folioService,
        private readonly PaymentService $paymentService,
        private readonly FinancialService $financialService,
        private readonly VentaItemsProcessor $ventaItemsProcessor
    ) {
    }

    /**
     * Calculate totals for a document (used in tests)
     * @param array $data
     * @return array
     */
    protected function calculateTotals(array $data): array
    {
        $items = array_merge($data['productos'] ?? [], $data['servicios'] ?? []);
        return $this->financialService->calculateDocumentTotals(
            $items,
            $data['descuento_general'] ?? 0,
            $data['cliente_id'] ?? null
        );
    }

    /**
     * Create a new venta with all its items and relationships
     *
     * @param array $data Validated data from request
     * @param bool $usarPreciosFijos Whether to use fixed prices from data instead of recalculating
     * @return Venta
     * @throws \Exception
     */
    public function createVenta(array $data, bool $usarPreciosFijos = false): Venta
    {
        try {
            $data['cliente_id'] = $this->resolveClienteId($data);

            $authUser = Auth::user();
            if (empty($data['almacen_id']) && $authUser && ! empty($authUser->almacen_venta_id)) {
                $data['almacen_id'] = (int) $authUser->almacen_venta_id;
            }
            if (empty($data['almacen_id'])) {
                throw ValidationException::withMessages([
                    'almacen_id' => 'Se requiere almacén de venta. Asigna un almacén al usuario o envía almacen_id.',
                ]);
            }

            // 0. Pre-calculate totals for validation using FinancialService
            // Combine products and services for calculation
            $itemsForCalc = array_merge($data['productos'] ?? [], $data['servicios'] ?? []);

            $totals = $this->financialService->calculateDocumentTotals(
                $itemsForCalc,
                (float) ($data['descuento_general'] ?? 0),
                $data['cliente_id'] ?? null,
                [
                    'aplicar_retencion_iva' => !empty($data['retencion_iva']),
                    'aplicar_retencion_isr' => !empty($data['retencion_isr']),
                    'mode' => 'sales'
                ]
            );

            if (isset($data['metodo_pago']) && !in_array($data['metodo_pago'], ['credito', 'efectivo'])) {
                if (empty($data['cuenta_bancaria_id'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cuenta_bancaria_id' => 'Debe seleccionar una cuenta bancaria para pagos con ' . $data['metodo_pago']
                    ]);
                }
            }

            return DB::transaction(function () use ($data, $usarPreciosFijos, $totals) {
                if (! empty($data['cita_id'])) {
                    $cid = (int) $data['cita_id'];
                    if (Venta::query()->where('cita_id', $cid)->exists()) {
                        throw ValidationException::withMessages([
                            'cita_id' => 'Esta cita ya tiene una venta vinculada. Usa «Vincular venta existente» desde la venta o el reporte de citas, o abre la venta ya asociada.',
                        ]);
                    }
                }

                // 1. Validate Credit Limit INSIDE Transaction (Prevent Race Conditions)
                if (isset($data['metodo_pago']) && $data['metodo_pago'] === 'credito') {
                    $this->validateCreditLimit($data['cliente_id'] ?? null, $totals['total']);
                }

                \Log::debug("VentaCreationService: Validating stock", ['productos' => $data['productos'] ?? [], 'almacen_id' => $data['almacen_id']]);
                // 2. Validate and lock stock
                $stockValidation = $this->stockValidationService->validateAndLockStock(
                    $data['productos'] ?? [],
                    $data['almacen_id']
                );

                if (!$stockValidation['valid']) {
                    throw new \App\Exceptions\StockInsuficienteException(
                        'Stock insuficiente: ' . implode(', ', $stockValidation['errors']),
                        $stockValidation['detailed_errors'] ?? []
                    );
                }

                // 3. Generate folio
                $numeroVenta = $this->folioService->getNextFolio('venta');

                // 4. Create venta record
                $user = \Illuminate\Support\Facades\Auth::user();

                $metodoPagoInput = $data['metodo_pago'] ?? 'efectivo';
                $metodoPagoEnum = \App\Enums\MetodoPago::tryFrom($metodoPagoInput);
                if (!$metodoPagoEnum) {
                    foreach (\App\Enums\MetodoPago::cases() as $case) {
                        if (strcasecmp($case->value, $metodoPagoInput) === 0) {
                            $metodoPagoEnum = $case;
                            break;
                        }
                    }
                }
                $metodoPagoNormalized = $metodoPagoEnum ? $metodoPagoEnum->value : $metodoPagoInput;

                $vendedorAttr = $this->resolveVendedorAttribution($data, $user);

                $cobradorId = isset($data['pagado_por_user_id']) ? (int) $data['pagado_por_user_id'] : (int) ($user?->id ?? 0);
                if ($metodoPagoNormalized !== 'credito' && $cobradorId > 0) {
                    $empresaId = (int) (EmpresaResolver::resolveId() ?? 0);
                    $cobrador = User::withoutGlobalScopes()->find($cobradorId);
                    if (! $cobrador || (int) ($cobrador->empresa_id ?? 0) !== $empresaId) {
                        throw ValidationException::withMessages([
                            'pagado_por_user_id' => ['El usuario seleccionado no puede registrar este cobro para esta empresa.'],
                        ]);
                    }
                }

                $venta = Venta::create([
                    'cliente_id' => $data['cliente_id'] ?? null,
                    'cotizacion_id' => $data['cotizacion_id'] ?? null, // Para conversiones de cotización
                    'numero_venta' => $numeroVenta,
                    'fecha' => now(),
                    'estado' => \App\Enums\EstadoVenta::Aprobada,
                    'subtotal' => $totals['subtotal'],
                    'descuento_general' => $totals['descuento_general'],
                    'iva' => $totals['iva'],
                    'isr' => $totals['isr'] ?? 0, // Legacy cleaning
                    'retencion_iva' => $totals['retencion_iva'] ?? 0,
                    'retencion_isr' => $totals['retencion_isr'] ?? 0,
                    'total' => $totals['total'],
                    'notas' => $data['notas'] ?? null,
                    'pagado' => false,
                    'metodo_pago' => $metodoPagoNormalized,
                    'forma_pago_sat' => $data['forma_pago_sat'] ?? ($metodoPagoNormalized === 'credito' ? '99' : null),
                    'metodo_pago_sat' => $data['metodo_pago_sat'] ?? ($metodoPagoNormalized === 'credito' ? 'PPD' : 'PUE'),
                    'almacen_id' => $data['almacen_id'],
                    'cita_id' => $data['cita_id'] ?? null,
                    // Vendedor/técnico asignado a la venta (puede ser distinto de quien registra el cobro)
                    'vendedor_id' => $vendedorAttr['vendedor_id'],
                    'vendedor_type' => $vendedorAttr['vendedor_type'],
                    // Contado: quién recibió el dinero (pagado_por_user_id o quien crea la venta)
                    'pagado_por' => $metodoPagoNormalized !== 'credito' ? $cobradorId : null,
                ]);

                // 5. Create CuentasPorCobrar
                $cuentaPorCobrar = CuentasPorCobrar::create([
                    'empresa_id' => $venta->empresa_id,
                    'cobrable_id' => $venta->id,
                    'cobrable_type' => 'venta', // Use morph map alias
                    'cliente_id' => $data['cliente_id'] ?? null,
                    'monto_total' => $totals['total'],
                    'monto_pagado' => 0,
                    'monto_pendiente' => $totals['total'],
                    'fecha_vencimiento' => now()->addDays(30), // Vence 30 días después
                    'estado' => 'pendiente',
                    'notas' => 'Cuenta generada automáticamente por venta',
                ]);

                // 6. Process products
                $this->ventaItemsProcessor->processProducts($venta, $data['productos'] ?? [], $data['almacen_id'], $data['price_list_id'] ?? null, $usarPreciosFijos);

                // 7. Process services
                $this->ventaItemsProcessor->processServices($venta, $data['servicios'] ?? []);

                // 8. Process automatic payment for non-credit sales
                if ($metodoPagoNormalized !== 'credito') {
                    // ✅ FIX: Set relation manually to avoid loading issues in transaction
                    $venta->setRelation('cuentaPorCobrar', $cuentaPorCobrar);

                    $this->paymentService->registrarPagoContado(
                        $venta,
                        $metodoPagoNormalized,
                        $data['notas'] ?? 'Pago automático al crear venta',
                        $data['cuenta_bancaria_id'] ?? null // âœ… Pass bank account if provided (for card/transfer)
                    );

                    // Refresh to get updated pagado status
                    $venta->refresh();
                }

                \Log::info("VentaCreationService: Venta created successfully", ['id' => $venta->id, 'numero' => $venta->numero_venta]);

                return $venta->fresh();
            });
        } catch (\Exception $e) {
            \Log::error("VentaCreationService: Error creating venta", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Vendedor/técnico al que se atribuye la venta (comisiones, reportes).
     * Si no se envía vendedor_id, se usa el usuario autenticado (quien crea el registro).
     * Solo se admite {@see User} (incluye técnicos como usuarios del mismo modelo).
     *
     * @param  array<string, mixed>  $data
     * @return array{vendedor_id: int|null, vendedor_type: class-string|null}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resolveVendedorAttribution(array $data, $authUser): array
    {
        $rawId = $data['vendedor_id'] ?? null;
        if ($rawId === '' || $rawId === null) {
            return [
                'vendedor_id' => $authUser?->id,
                'vendedor_type' => $authUser ? get_class($authUser) : null,
            ];
        }

        $vendedorId = (int) $rawId;
        $vendedorType = $data['vendedor_type'] ?? User::class;

        if ($vendedorType !== User::class) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vendedor_type' => 'Tipo de vendedor no soportado. Use un usuario de la empresa.',
            ]);
        }

        $candidato = User::withoutGlobalScopes()->find($vendedorId);
        $empresaId = EmpresaResolver::resolveId();

        if (!$candidato || $empresaId === null || (int) $candidato->empresa_id !== (int) $empresaId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vendedor_id' => 'El vendedor seleccionado no pertenece a su empresa.',
            ]);
        }

        if (isset($candidato->activo) && $candidato->activo === false) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vendedor_id' => 'El usuario seleccionado está inactivo.',
            ]);
        }

        return [
            'vendedor_id' => $candidato->id,
            'vendedor_type' => User::class,
        ];
    }

    /**
     * Validate if client has enough credit
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateCreditLimit(?int $clienteId, float $totalVenta): void
    {
        if (!$clienteId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cliente_id' => 'Se requiere un cliente registrado para ventas a crédito.'
            ]);
        }

        // Lock client record for update to prevent concurrent credit usage
        $cliente = \App\Models\Cliente::where('id', $clienteId)->lockForUpdate()->first();

        if (!$cliente) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cliente_id' => 'Cliente no encontrado.'
            ]);
        }

        if (!$cliente->credito_activo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'metodo_pago' => "El cliente {$cliente->nombre_razon_social} no tiene el crédito habilitado."
            ]);
        }

        // Calcular saldo pendiente actual
        $saldoPendiente = $cliente->saldo_pendiente; // Uses the accessor we added
        $nuevoSaldo = $saldoPendiente + $totalVenta;

        if ($nuevoSaldo > $cliente->limite_credito) {
            $disponible = max(0, $cliente->limite_credito - $saldoPendiente);
            $exceso = $nuevoSaldo - $cliente->limite_credito;

            throw \Illuminate\Validation\ValidationException::withMessages([
                'metodo_pago' => [
                    "Límite de crédito excedido.",
                    "Límite: $" . number_format((float) $cliente->limite_credito, 2),
                    "Saldo pendiente: $" . number_format((float) $saldoPendiente, 2),
                    "Disponible: $" . number_format((float) $disponible, 2),
                    "Intentando cargar: $" . number_format((float) $totalVenta, 2),
                ]
            ]);
        }
    }

    protected function resolveClienteId(array $data): ?int
    {
        if (!empty($data['cliente_id'])) {
            return (int) $data['cliente_id'];
        }

        if (($data['metodo_pago'] ?? null) === 'credito') {
            return null;
        }

        $empresaId = EmpresaResolver::resolveId();
        if (!$empresaId) {
            return null;
        }

        $cliente = Cliente::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where(function ($query) {
                $query->where('rfc', 'XAXX010101000')
                    ->orWhereRaw('UPPER(nombre_razon_social) = ?', ['PÚBLICO EN GENERAL'])
                    ->orWhereRaw('UPPER(nombre_razon_social) = ?', ['PUBLICO EN GENERAL']);
            })
            ->first();

        if ($cliente) {
            return $cliente->id;
        }

        $cliente = Cliente::withoutGlobalScopes()->create([
            'empresa_id' => $empresaId,
            'nombre_razon_social' => 'Público en General',
            'tipo_persona' => 'fisica',
            'rfc' => 'XAXX010101000',
            'regimen_fiscal' => '616',
            'uso_cfdi' => 'S01',
            'codigo_postal' => '00000',
            'pais' => 'México',
            'requiere_factura' => false,
            'activo' => true,
        ]);

        return $cliente->id;
    }
}
