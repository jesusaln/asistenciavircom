<?php

namespace App\Http\Controllers\Bancos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bancos\BancoCuenta;
use App\Models\Bancos\BancoMovimiento;
use App\Models\Contab\PolizaContable;
use App\Models\Contab\AsientoContable;
use App\Models\Contab\CuentaContable;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BancoController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Usuario no autenticado.');
        }
        $empresaId = $user->empresa_id ?? 1;

        $cuentas = BancoCuenta::where('empresa_id', $empresaId)
            ->with('cuentaContable')
            ->withCount('movimientos')
            ->get();

        // Clientes activos
        $clientes = \App\Models\Cliente::where('empresa_id', $empresaId)
            ->where('activo', 1)
            ->select('id', 'nombre_razon_social', 'rfc')
            ->orderBy('nombre_razon_social')
            ->get();

        // Proveedores activos
        $proveedores = \App\Models\Proveedor::where('empresa_id', $empresaId)
            ->where('activo', 1)
            ->select('id', 'nombre_razon_social', 'rfc')
            ->orderBy('nombre_razon_social')
            ->get();

        // Cuentas por Cobrar pendientes (Optimizado sin bucles N+1 ni recálculo masivo)
        $cuentasCobrar = \App\Models\CuentasPorCobrar::where('empresa_id', $empresaId)
            ->with(['cliente', 'cobrable', 'cfdi'])
            ->pendientes()
            ->get()
            ->map(function ($cxc) {
                return [
                    'id' => $cxc->id,
                    'cfdi_id' => $cxc->cfdi_id,
                    'monto_total' => (float) $cxc->monto_total,
                    'monto_pendiente' => round((float) $cxc->monto_pendiente, 2),
                    'fecha_vencimiento' => $cxc->fecha_vencimiento ? ($cxc->fecha_vencimiento instanceof \Carbon\Carbon ? $cxc->fecha_vencimiento->format('Y-m-d') : $cxc->fecha_vencimiento) : null,
                    'estado' => $cxc->estado,
                    'referencia' => $cxc->cfdi ? ($cxc->cfdi->folio ?? '') : ($cxc->cobrable ? ($cxc->cobrable->folio ?? '') : ''),
                    'cliente' => [
                        'nombre_razon_social' => $cxc->cliente ? $cxc->cliente->nombre_razon_social : 'Público en General',
                        'rfc' => $cxc->cliente ? $cxc->cliente->rfc : '',
                    ],
                    'cobrable' => [
                        'numero_venta' => $cxc->cobrable ? ($cxc->cobrable->folio ?? '') : '',
                    ],
                ];
            });

        // Cuentas por Pagar pendientes (Optimizado)
        $cuentasPagar = \App\Models\CuentasPorPagar::where('empresa_id', $empresaId)
            ->with(['proveedor', 'compra', 'cfdi'])
            ->pendientes()
            ->get()
            ->map(function ($cxp) {
                return [
                    'id' => $cxp->id,
                    'cfdi_id' => $cxp->cfdi_id,
                    'monto_total' => (float) $cxp->monto_total,
                    'monto_pendiente' => round((float) $cxp->monto_pendiente, 2),
                    'fecha_vencimiento' => $cxp->fecha_vencimiento ? ($cxp->fecha_vencimiento instanceof \Carbon\Carbon ? $cxp->fecha_vencimiento->format('Y-m-d') : $cxp->fecha_vencimiento) : null,
                    'estado' => $cxp->estado,
                    'referencia' => $cxp->cfdi ? ($cxp->cfdi->folio ?? '') : ($cxp->compra ? $cxp->compra->numero_compra : ''),
                    'proveedor' => [
                        'nombre_razon_social' => $cxp->proveedor ? $cxp->proveedor->nombre_razon_social : ($cxp->compra && $cxp->compra->proveedor ? $cxp->compra->proveedor->nombre_razon_social : 'Proveedor'),
                        'rfc' => $cxp->proveedor ? $cxp->proveedor->rfc : ($cxp->compra && $cxp->compra->proveedor ? $cxp->compra->proveedor->rfc : ''),
                    ],
                    'compra' => [
                        'numero_compra' => $cxp->compra ? $cxp->compra->numero_compra : '',
                    ],
                ];
            });

        // Cuentas contables de tesorería (102.xx) disponibles para mapeo
        $cuentasContables = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
            ->where('codigo', 'like', '102%')
            ->where('es_detalle', true)
            ->select('id', 'codigo', 'nombre')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('Bancos/Index', [
            'cuentas' => $cuentas,
            'clientes' => $clientes,
            'proveedores' => $proveedores,
            'cuentasCobrar' => $cuentasCobrar->sortBy('fecha_vencimiento')->values()->all(),
            'cuentasPagar' => $cuentasPagar->sortBy('fecha_vencimiento')->values()->all(),
            'cuentasContables' => $cuentasContables
        ]);
    }

    public function storeCuenta(Request $request)
    {
        $request->validate([
            'nombre_banco' => 'required|string|max:100',
            'alias' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|min:5|max:50',
            'clabe' => 'nullable|digits:18',
            'moneda' => 'required|string|max:10',
            'saldo_inicial' => 'required|numeric',
            'es_fiscal' => 'nullable|boolean',
            'tipo' => 'nullable|string|in:cuenta,tarjeta_credito',
            'cuenta_contable_id' => 'nullable|exists:contab_cuentas,id',
        ]);

        $empresaId = auth()->user()->empresa_id ?? 1;
        $esFiscal = $request->boolean('es_fiscal', true);

        $cuentaContableId = $request->cuenta_contable_id;
        if ($esFiscal && !$cuentaContableId) {
            $cuentaContable = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
                ->where('codigo', 'like', '102%')
                ->where('es_detalle', true)
                ->first();
            $cuentaContableId = $cuentaContable ? $cuentaContable->id : null;
        } elseif (!$esFiscal) {
            $cuentaContableId = null;
        }

        $cuenta = BancoCuenta::create([
            'empresa_id' => $empresaId,
            'nombre_banco' => $request->nombre_banco,
            'alias' => $request->alias,
            'numero_cuenta' => $request->numero_cuenta,
            'clabe' => $request->clabe,
            'moneda' => $request->moneda,
            'saldo_inicial' => $request->saldo_inicial,
            'saldo_actual' => $request->saldo_inicial,
            'cuenta_contable_id' => $cuentaContableId,
            'es_fiscal' => $esFiscal,
            'tipo' => $request->tipo ?? 'cuenta',
        ]);

        // Crear la cuenta en la tabla clásica/legacy para sincronía total
        try {
            $legacyExists = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)
                ->where('numero_cuenta', $request->numero_cuenta)
                ->exists();
            if (!$legacyExists) {
                \App\Models\CuentaBancaria::create([
                    'empresa_id' => $empresaId,
                    'nombre' => $request->alias ?: $request->nombre_banco,
                    'banco' => $request->nombre_banco,
                    'numero_cuenta' => $request->numero_cuenta,
                    'saldo_inicial' => $request->saldo_inicial,
                    'saldo_actual' => $request->saldo_inicial,
                    'moneda' => $request->moneda,
                ]);
            }
        } catch (\Exception $e) {
            // Ignorar errores menores de sincronía para no bloquear la operación principal
        }

        // Cargar relación y conteo para devolverla al front
        $cuenta->load('cuentaContable')->loadCount('movimientos');

        return response()->json([
            'success' => true,
            'cuenta' => $cuenta
        ]);
    }

    public function updateCuenta(Request $request, $id)
    {
        $empresaId = auth()->user()->empresa_id ?? 1;
        $cuenta = BancoCuenta::where('empresa_id', $empresaId)->findOrFail($id);

        $request->validate([
            'nombre_banco' => 'required|string|max:100',
            'alias' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|min:5|max:50',
            'clabe' => 'nullable|digits:18',
            'moneda' => 'required|string|max:10',
            'saldo_inicial' => 'required|numeric',
            'es_fiscal' => 'nullable|boolean',
            'tipo' => 'nullable|string|in:cuenta,tarjeta_credito',
            'cuenta_contable_id' => 'nullable|exists:contab_cuentas,id',
        ]);

        $esFiscal = $request->boolean('es_fiscal', true);
        $cuentaContableId = $request->cuenta_contable_id;
        if ($esFiscal && !$cuentaContableId) {
            $cuentaContable = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
                ->where('codigo', 'like', '102%')
                ->where('es_detalle', true)
                ->first();
            $cuentaContableId = $cuentaContable ? $cuentaContable->id : null;
        } elseif (!$esFiscal) {
            $cuentaContableId = null;
        }

        $diff = $request->saldo_inicial - ($cuenta->saldo_actual ?? $cuenta->saldo_inicial);

        $cuenta->update([
            'nombre_banco' => $request->nombre_banco,
            'alias' => $request->alias,
            'numero_cuenta' => $request->numero_cuenta,
            'clabe' => $request->clabe,
            'moneda' => $request->moneda,
            'saldo_actual' => ($cuenta->saldo_actual ?? $cuenta->saldo_inicial) + $diff,
            'saldo_inicial' => $request->saldo_inicial,
            'cuenta_contable_id' => $cuentaContableId,
            'es_fiscal' => $esFiscal,
            'tipo' => $request->tipo ?? 'cuenta',
        ]);

        try {
            $legacy = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)
                ->where('numero_cuenta', $cuenta->numero_cuenta)
                ->first();
            if ($legacy) {
                $legacy->update([
                    'nombre' => $request->alias ?: $request->nombre_banco,
                    'banco' => $request->nombre_banco,
                    'numero_cuenta' => $request->numero_cuenta,
                    'saldo_actual' => $legacy->saldo_actual + $diff,
                    'moneda' => $request->moneda,
                ]);
            }
        } catch (\Exception $e) {
        }

        $cuenta->load('cuentaContable')->loadCount('movimientos');

        return response()->json([
            'success' => true,
            'cuenta' => $cuenta
        ]);
    }

    public function destroyCuenta($id)
    {
        $empresaId = auth()->user()->empresa_id ?? 1;
        $cuenta = BancoCuenta::where('empresa_id', $empresaId)->findOrFail($id);

        if ($cuenta->movimientos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la cuenta porque ya tiene movimientos asociados.'
            ], 422);
        }

        try {
            $legacy = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)
                ->where('numero_cuenta', $cuenta->numero_cuenta)
                ->first();
            if ($legacy) {
                $legacy->delete();
            }
        } catch (\Exception $e) {
        }

        $cuenta->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada correctamente.'
        ]);
    }

    public function indexMovimientos()
    {
        $empresaId = auth()->user()->empresa_id ?? 1;
        $cuentasIds = BancoCuenta::where('empresa_id', $empresaId)->pluck('id');

        $movimientos = BancoMovimiento::whereIn('cuenta_bancaria_id', $cuentasIds)
            ->with(['cuentaBancaria', 'poliza'])
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'movimientos' => $movimientos
        ]);
    }

    public function storeMovimiento(Request $request)
    {
        $empresaId = auth()->user()->empresa_id ?? 1;

        $request->validate([
            'cuenta_bancaria_id' => ['required', Rule::exists('bancos_cuentas', 'id')->where('empresa_id', $empresaId)],
            'cuenta_destino_id' => ['nullable', 'required_if:tipo,traspaso', Rule::exists('bancos_cuentas', 'id')->where('empresa_id', $empresaId), 'different:cuenta_bancaria_id'],
            'fecha' => 'required|date',
            'tipo' => 'required|in:ingreso,egreso,traspaso',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:100',
            'beneficiario_rfc' => 'nullable|string|max:20',
            'beneficiario_nombre' => 'nullable|string|max:255',
            'forma_pago_sat' => 'nullable|string|max:10',
            'cxc_id' => ['nullable', Rule::exists('cuentas_por_cobrar', 'id')->where('empresa_id', $empresaId)],
            'cxp_id' => ['nullable', Rule::exists('cuentas_por_pagar', 'id')->where('empresa_id', $empresaId)],
        ]);

        $cuentaBancaria = BancoCuenta::where('empresa_id', $empresaId)->findOrFail($request->cuenta_bancaria_id);
        $formaPago = $request->forma_pago_sat ?? '03';
        $monto = (float) $request->monto;

        try {
            DB::beginTransaction();

            // Sincronizar o encontrar la cuenta legacy asociada
            $legacyCuenta = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)
                ->where('numero_cuenta', $cuentaBancaria->numero_cuenta)
                ->first();

            if (!$legacyCuenta && $cuentaBancaria->numero_cuenta) {
                $legacyCuenta = \App\Models\CuentaBancaria::create([
                    'empresa_id' => $empresaId,
                    'nombre' => $cuentaBancaria->alias ?: $cuentaBancaria->nombre_banco,
                    'banco' => $cuentaBancaria->nombre_banco,
                    'numero_cuenta' => $cuentaBancaria->numero_cuenta,
                    'saldo_inicial' => $cuentaBancaria->saldo_inicial,
                    'saldo_actual' => $cuentaBancaria->saldo_actual,
                    'moneda' => $cuentaBancaria->moneda,
                ]);
            }
            $legacyCuentaId = $legacyCuenta ? $legacyCuenta->id : null;

            $movimiento = null;
            $conciliableType = null;
            $conciliableId = null;

            if ($request->tipo === 'traspaso') {
                $cuentaDestino = BancoCuenta::where('empresa_id', $empresaId)->findOrFail($request->cuenta_destino_id);

                $legacyDestino = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)
                    ->where('numero_cuenta', $cuentaDestino->numero_cuenta)
                    ->first();

                if (!$legacyDestino && $cuentaDestino->numero_cuenta) {
                    $legacyDestino = \App\Models\CuentaBancaria::create([
                        'empresa_id' => $empresaId,
                        'nombre' => $cuentaDestino->alias ?: $cuentaDestino->nombre_banco,
                        'banco' => $cuentaDestino->nombre_banco,
                        'numero_cuenta' => $cuentaDestino->numero_cuenta,
                        'saldo_inicial' => $cuentaDestino->saldo_inicial,
                        'saldo_actual' => $cuentaDestino->saldo_actual,
                        'moneda' => $cuentaDestino->moneda,
                    ]);
                }

                // 1. Registro maestro en TraspasoBancario
                $traspaso = \App\Models\TraspasoBancario::create([
                    'empresa_id' => $empresaId,
                    'cuenta_origen_id' => $legacyCuentaId ?: 1,
                    'cuenta_destino_id' => ($legacyDestino ? $legacyDestino->id : 1),
                    'monto' => $monto,
                    'fecha' => $request->fecha,
                    'referencia' => $request->referencia,
                    'notas' => $request->concepto,
                    'user_id' => auth()->id(),
                ]);

                // 2. Crear retiro en cuenta origen
                $movimiento = BancoMovimiento::create([
                    'cuenta_bancaria_id' => $cuentaBancaria->id,
                    'fecha' => $request->fecha,
                    'tipo' => 'egreso',
                    'forma_pago_sat' => $formaPago,
                    'monto' => $monto,
                    'concepto' => "Traspaso a {$cuentaDestino->nombre_banco} - " . ($cuentaDestino->alias ?: $cuentaDestino->numero_cuenta) . ($request->referencia ? " // Ref: {$request->referencia}" : ""),
                    'referencia' => $request->referencia,
                    'conciliable_type' => \App\Models\TraspasoBancario::class,
                    'conciliable_id' => $traspaso->id,
                    'estado_conciliacion' => 'conciliado',
                    'created_by' => auth()->id(),
                ]);

                // 3. Crear depósito en cuenta destino
                BancoMovimiento::create([
                    'cuenta_bancaria_id' => $cuentaDestino->id,
                    'fecha' => $request->fecha,
                    'tipo' => 'ingreso',
                    'forma_pago_sat' => $formaPago,
                    'monto' => $monto,
                    'concepto' => "Traspaso recibido de {$cuentaBancaria->nombre_banco} - " . ($cuentaBancaria->alias ?: $cuentaBancaria->numero_cuenta),
                    'referencia' => $request->referencia,
                    'conciliable_type' => \App\Models\TraspasoBancario::class,
                    'conciliable_id' => $traspaso->id,
                    'estado_conciliacion' => 'conciliado',
                    'created_by' => auth()->id(),
                ]);

                // 4. Actualizar saldos
                $cuentaBancaria->decrement('saldo_actual', $monto);
                if ($legacyCuenta)
                    $legacyCuenta->decrement('saldo_actual', $monto);
                $cuentaDestino->increment('saldo_actual', $monto);
                if ($legacyDestino)
                    $legacyDestino->increment('saldo_actual', $monto);

            } elseif ($request->filled('cxc_id') && $request->tipo === 'ingreso') {
                $cxc = \App\Models\CuentasPorCobrar::findOrFail($request->cxc_id);
                $paymentService = app(\App\Services\PaymentService::class);
                $metodoPago = $request->forma_pago_sat === '01' ? 'efectivo' : ($request->forma_pago_sat === '03' ? 'transferencia' : 'otros');

                $paymentService->registrarPago(
                    $cxc,
                    $monto,
                    $metodoPago,
                    $request->concepto,
                    auth()->id(),
                    $legacyCuentaId,
                    $request->fecha
                );

                $conciliableType = get_class($cxc);
                $conciliableId = $cxc->id;

                // Recuperar el movimiento automáticamente generado en BancoMovimiento
                $movimiento = BancoMovimiento::where('cuenta_bancaria_id', $cuentaBancaria->id)
                    ->latest('id')
                    ->first();

            } elseif ($request->filled('cxp_id') && $request->tipo === 'egreso') {
                $cxp = \App\Models\CuentasPorPagar::findOrFail($request->cxp_id);
                $pendiente = $cxp->calcularPendiente();
                $metodoPago = $request->forma_pago_sat === '01' ? 'efectivo' : ($request->forma_pago_sat === '03' ? 'transferencia' : 'otros');

                if ($monto >= $pendiente) {
                    $cxp->marcarPagado(
                        $metodoPago,
                        $legacyCuentaId,
                        $request->concepto
                    );
                } else {
                    $cxp->registrarPago(
                        $monto,
                        $request->concepto,
                        $legacyCuentaId
                    );
                }

                $conciliableType = get_class($cxp);
                $conciliableId = $cxp->id;

                // Recuperar el movimiento automáticamente generado
                $movimiento = BancoMovimiento::where('cuenta_bancaria_id', $cuentaBancaria->id)
                    ->latest('id')
                    ->first();
            } else {
                // Flujo B: Movimiento manual directo sin vinculación
                $movimiento = BancoMovimiento::create([
                    'cuenta_bancaria_id' => $cuentaBancaria->id,
                    'fecha' => $request->fecha,
                    'tipo' => $request->tipo,
                    'forma_pago_sat' => $formaPago,
                    'monto' => $monto,
                    'concepto' => $request->concepto,
                    'referencia' => $request->referencia,
                    'beneficiario_rfc' => $request->beneficiario_rfc,
                    'beneficiario_nombre' => $request->beneficiario_nombre,
                    'estado_conciliacion' => 'conciliado',
                    'created_by' => auth()->id(),
                ]);

                // Afectar saldo directo
                $cuentaBancaria->increment('saldo_actual', $request->tipo === 'ingreso' ? $monto : -$monto);

                if ($legacyCuenta) {
                    $legacyCuenta->increment('saldo_actual', $request->tipo === 'ingreso' ? $monto : -$monto);
                }
            }

            if ($movimiento) {
                if ($conciliableType && $conciliableId) {
                    $movimiento->update([
                        'conciliable_type' => $conciliableType,
                        'conciliable_id' => $conciliableId,
                    ]);
                }

                // Generar contabilidad fiscal si la cuenta es fiscal
                if ($cuentaBancaria->es_fiscal && !$movimiento->poliza_id) {
                    $cuentaBanco = $cuentaBancaria->cuentaContable;
                    if (!$cuentaBanco) {
                        $cuentaBanco = CuentaContable::where('empresa_id', $empresaId)
                            ->where('codigo', 'like', '102%')
                            ->where('es_detalle', true)
                            ->first();
                    }
                    if (!$cuentaBanco) {
                        $cuentaBanco = CuentaContable::where('empresa_id', $empresaId)->where('codigo', 'like', '102%')->first();
                    }
                    if (!$cuentaBanco) {
                        throw new \Exception("No se encontró cuenta contable de Bancos (102) asociada.");
                    }

                    // Determinar cuenta de contrapartida
                    $conceptoUpper = strtoupper($request->concepto);
                    $cuentaContrapartida = null;

                    if (str_contains($conceptoUpper, 'COMISION') || str_contains($conceptoUpper, 'MEMBRESIA') || str_contains($conceptoUpper, 'INTERES')) {
                        $cuentaContrapartida = CuentaContable::where('empresa_id', $empresaId)
                            ->where('codigo', 'like', '603%') // Gastos financieros
                            ->where('es_detalle', true)
                            ->first();
                        if (!$cuentaContrapartida) {
                            $cuentaContrapartida = CuentaContable::where('empresa_id', $empresaId)->where('codigo', 'like', '603%')->first();
                        }
                    } elseif ($request->tipo === 'egreso') {
                        $cuentaContrapartida = CuentaContable::where('empresa_id', $empresaId)
                            ->where('codigo', 'like', '602%') // Gastos admón
                            ->first();
                    } else {
                        $cuentaContrapartida = CuentaContable::where('empresa_id', $empresaId)
                            ->where('codigo', 'like', '401%') // Ingresos
                            ->first();
                    }

                    if (!$cuentaContrapartida) {
                        throw new \Exception("No se encontró cuenta de contrapartida.");
                    }

                    // Crear la póliza contable con lockForUpdate para evitar condiciones de carrera (SAT)
                    $tipoPoliza = ($request->tipo === 'egreso') ? 'egreso' : 'ingreso';
                    $fecha = $request->fecha;

                    $maxNumero = (int) (PolizaContable::where('empresa_id', $empresaId)
                        ->whereYear('fecha', date('Y', strtotime($fecha)))
                        ->where('tipo', $tipoPoliza)
                        ->orderByDesc('numero')
                        ->lockForUpdate()
                        ->value('numero') ?? 0);

                    $poliza = PolizaContable::create([
                        'empresa_id' => $empresaId,
                        'tipo' => $tipoPoliza,
                        'numero' => $maxNumero + 1,
                        'fecha' => $fecha,
                        'concepto' => "Movimiento Bancario ({$cuentaBancaria->nombre_banco}) - FP SAT [{$formaPago}]: {$request->concepto}",
                        'total' => $monto,
                        'estado' => 'asentada',
                        'created_by' => auth()->id(),
                    ]);

                    // Crear los asientos
                    if ($request->tipo === 'egreso') {
                        AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaContrapartida->id, 'debe' => $monto, 'haber' => 0, 'referencia' => substr($request->concepto, 0, 100)]);
                        AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaBanco->id, 'debe' => 0, 'haber' => $monto, 'referencia' => substr($request->concepto, 0, 100)]);
                    } else {
                        AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaBanco->id, 'debe' => $monto, 'haber' => 0, 'referencia' => substr($request->concepto, 0, 100)]);
                        AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaContrapartida->id, 'debe' => 0, 'haber' => $monto, 'referencia' => substr($request->concepto, 0, 100)]);
                    }

                    // Vincular el movimiento con la póliza
                    $movimiento->update(['poliza_id' => $poliza->id]);
                }
            }

            DB::commit();

            if ($movimiento) {
                $movimiento->load(['cuentaBancaria', 'poliza']);
            }

            return response()->json([
                'success' => true,
                'movimiento' => $movimiento,
                'message' => 'Movimiento registrado con éxito y póliza generada'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function indexEntregasPendientes()
    {
        $empresaId = auth()->user()->empresa_id ?? 1;
        $entregas = \App\Models\EntregaDinero::with(['usuario', 'children'])
            ->where('empresa_id', $empresaId)
            ->whereNull('parent_id')
            ->where('estado', 'pendiente')
            ->orderBy('fecha_entrega', 'desc')
            ->get()
            ->map(function ($e) {
                $hijos = $e->children ? $e->children->map(function ($child) {
                    $clienteNombre = '—';
                    $vendedorNombre = '—';
                    $folio = '—';
                    if ($child->tipo_origen === 'venta') {
                        $venta = \App\Models\Venta::with(['cliente', 'vendedor', 'pagadoPor'])->find($child->id_origen);
                        if ($venta) {
                            $clienteNombre = $venta->cliente?->nombre_razon_social ?? 'Sin cliente';
                            $vendedorNombre = $venta->vendedor?->name ?? ($venta->pagadoPor?->name ?? '—');
                            $folio = 'Venta #' . ($venta->numero_venta ?: $venta->id);
                        }
                    } elseif ($child->tipo_origen === 'cobranza') {
                        $cobranza = \App\Models\Cobranza::with(['renta.cliente', 'responsableCobro'])->find($child->id_origen);
                        if ($cobranza) {
                            $clienteNombre = $cobranza->renta?->cliente?->nombre_razon_social ?? 'Sin cliente';
                            $vendedorNombre = $cobranza->responsableCobro?->name ?? '—';
                            $folio = $cobranza->concepto ?: 'Cobranza #' . $cobranza->id;
                        }
                    }
                    return [
                        'id' => $child->id,
                        'tipo' => strtoupper($child->tipo_origen),
                        'folio' => $folio,
                        'monto' => (float) $child->total,
                        'metodo_pago' => $child->metodo_pago ?? 'Efectivo',
                        'cliente' => $clienteNombre,
                        'vendedor' => $vendedorNombre,
                    ];
                }) : [];

                return [
                    'id' => $e->id,
                    'fecha_entrega' => $e->fecha_entrega ? ($e->fecha_entrega instanceof \Carbon\Carbon ? $e->fecha_entrega->format('Y-m-d') : $e->fecha_entrega) : null,
                    'monto_efectivo' => (float) $e->monto_efectivo,
                    'monto_cheques' => (float) $e->monto_cheques,
                    'monto_transferencia' => (float) $e->monto_transferencia,
                    'monto_tarjetas' => (float) $e->monto_tarjetas,
                    'total' => (float) $e->total,
                    'notas' => $e->notas,
                    'usuario' => $e->usuario ? $e->usuario->name : 'Usuario general',
                    'conteo_hijos' => $e->children ? $e->children->count() : 0,
                    'es_lote' => in_array($e->tipo_origen, ['lote', 'declaracion_mi_corte']),
                    'hijos' => $hijos,
                ];
            });

        return response()->json([
            'success' => true,
            'entregas' => $entregas
        ]);
    }

    public function aceptarEntrega(Request $request, $id)
    {
        $empresaId = auth()->user()->empresa_id ?? 1;
        $entrega = \App\Models\EntregaDinero::where('empresa_id', $empresaId)
            ->where('estado', 'pendiente')
            ->findOrFail($id);

        $request->validate([
            'banco_cuenta_id' => 'required|exists:bancos_cuentas,id',
            'notas' => 'nullable|string|max:500',
            'fecha_hora' => 'nullable|string'
        ]);

        $bancoCuenta = BancoCuenta::where('empresa_id', $empresaId)->findOrFail($request->banco_cuenta_id);

        // Encontrar la cuenta bancaria legacy correspondiente
        $legacyCuenta = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)
            ->where(function ($q) use ($bancoCuenta) {
                if (!empty($bancoCuenta->numero_cuenta)) {
                    $q->where('numero_cuenta', $bancoCuenta->numero_cuenta);
                } else {
                    $q->where('nombre', $bancoCuenta->alias ?: $bancoCuenta->nombre_banco);
                }
            })->first();

        if (!$legacyCuenta) {
            $legacyCuenta = \App\Models\CuentaBancaria::create([
                'empresa_id' => $empresaId,
                'nombre' => $bancoCuenta->alias ?: $bancoCuenta->nombre_banco,
                'banco' => $bancoCuenta->nombre_banco,
                'numero_cuenta' => $bancoCuenta->numero_cuenta,
                'saldo_inicial' => $bancoCuenta->saldo_inicial,
                'saldo_actual' => $bancoCuenta->saldo_actual ?? $bancoCuenta->saldo_inicial,
                'moneda' => $bancoCuenta->moneda,
            ]);
        }

        $fechaHora = $request->fecha_hora ?: now()->toDateTimeString();

        // Ejecutar el servicio para marcar recibido en una transacción
        DB::transaction(function () use ($entrega, $legacyCuenta, $request, $fechaHora) {
            \App\Services\EntregaDineroService::marcarComoRecibido(
                $entrega,
                auth()->id(),
                $legacyCuenta->id,
                $request->notas ?? 'Aceptado y depositado desde Tesorería de Bancos.',
                true,
                $fechaHora
            );
        });

        // Recargar la cuenta para tener saldo fresco y conteo de movimientos
        $bancoCuenta->load('cuentaContable')->loadCount('movimientos');

        return response()->json([
            'success' => true,
            'message' => 'Entrega de dinero aceptada y depositada correctamente.',
            'cuenta' => $bancoCuenta
        ]);
    }

    public function indexCobranzaPorFormalizar(Request $request)
    {
        $userId = auth()->id();
        $isAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);
        $esTesoreroRecepcion = auth()->user()->can('confirmar entrega efectivo');

        // Obtener cobranzas pagadas con saldos pendientes
        $cobranzasQuery = \App\Models\Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->where('estado', 'pagado')
            ->whereRaw("monto_pagado > COALESCE((SELECT SUM(total) FROM entregas_dinero WHERE tipo_origen = 'cobranza' AND id_origen = cobranzas.id AND estado = 'recibido' AND deleted_at IS NULL), 0)");

        if (!$isAdmin && !$esTesoreroRecepcion) {
            $cobranzasQuery->where('responsable_cobro', $userId);
        }

        $cobranzasPagadas = $cobranzasQuery->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(function ($cobranza) {
                $montoYaEntregado = \App\Models\EntregaDinero::where('tipo_origen', 'cobranza')
                    ->where('id_origen', $cobranza->id)
                    ->where('estado', 'recibido')
                    ->sum('total');
                $saldoPendiente = $cobranza->monto_pagado - $montoYaEntregado;

                return [
                    'id' => 'cobranza_' . $cobranza->id,
                    'tipo' => 'cobranza',
                    'tipo_origen' => 'cobranza',
                    'id_origen' => $cobranza->id,
                    'fecha_pago' => $cobranza->fecha_pago?->format('Y-m-d') ?? '—',
                    'total' => $cobranza->monto_pagado,
                    'saldo_pendiente' => max(0, $saldoPendiente),
                    'ya_entregado' => $montoYaEntregado,
                    'concepto' => $cobranza->concepto ?: 'Cobranza general',
                    'cliente' => $cobranza->renta->cliente->nombre_razon_social ?? 'Sin cliente',
                    'vendedor' => $cobranza->responsableCobro?->name ?? '—',
                    'metodo_pago' => $cobranza->metodo_pago ?? 'efectivo',
                ];
            });

        // Obtener ventas pagadas con saldos pendientes
        $ventasQuery = \App\Models\Venta::with(['cliente', 'pagadoPor', 'vendedor', 'cuentaPorCobrar.movimientosBancarios'])
            ->where('pagado', true)
            ->whereRaw("total > (
                COALESCE((SELECT SUM(total) FROM entregas_dinero WHERE tipo_origen = 'venta' AND id_origen = ventas.id AND estado IN ('pendiente', 'recibido') AND deleted_at IS NULL), 0) +
                COALESCE((SELECT SUM(monto) FROM movimientos_bancarios WHERE conciliable_type = 'App\\\Models\\\CuentasPorCobrar' AND conciliable_id = (SELECT id FROM cuentas_por_cobrar WHERE venta_id = ventas.id LIMIT 1) AND deleted_at IS NULL), 0)
            )");

        if (!$isAdmin && !$esTesoreroRecepcion) {
            $ventasQuery->where('pagado_por', $userId);
        }

        $ventasPagadas = $ventasQuery->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(function ($venta) {
                $montoYaEntregado = \App\Models\EntregaDinero::where('tipo_origen', 'venta')
                    ->where('id_origen', $venta->id)
                    ->whereIn('estado', ['pendiente', 'recibido'])
                    ->sum('total');

                $montoConciliado = 0;
                if ($venta->cuentaPorCobrar) {
                    $montoConciliado = $venta->cuentaPorCobrar->movimientosBancarios->sum(fn($m) => abs($m->monto));
                }

                $saldoPendiente = max(0, $venta->total - $montoYaEntregado - $montoConciliado);

                return [
                    'id' => 'venta_' . $venta->id,
                    'tipo' => 'venta',
                    'tipo_origen' => 'venta',
                    'id_origen' => $venta->id,
                    'fecha_pago' => $venta->fecha_pago?->format('Y-m-d') ?? '—',
                    'total' => $venta->total,
                    'saldo_pendiente' => $saldoPendiente,
                    'ya_entregado' => $montoYaEntregado,
                    'concepto' => 'Venta #' . $venta->numero_venta . ($montoConciliado > 0 ? ' (Conciliado parcial)' : ''),
                    'cliente' => $venta->cliente->nombre_razon_social ?? 'Sin cliente',
                    'vendedor' => $venta->vendedor?->name ?? ($venta->pagadoPor?->name ?? '—'),
                    'metodo_pago' => $venta->metodo_pago ?? 'efectivo',
                ];
            });

        $registrosAutomaticos = collect([...$cobranzasPagadas, ...$ventasPagadas])
            ->filter(fn($item) => $item['saldo_pendiente'] > 0.01)
            ->sortByDesc('fecha_pago')
            ->values();

        return response()->json($registrosAutomaticos);
    }
}
