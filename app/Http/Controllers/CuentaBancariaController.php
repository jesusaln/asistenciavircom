<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\CuentasPorCobrar;
use App\Models\Cobranza;

class CuentaBancariaController extends Controller
{
    /**
     * Lista de cuentas bancarias
     */
    public function index()
    {
        $cuentas = CuentaBancaria::orderBy('activa', 'desc')
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get()
            ->map(function ($cuenta) {
                return [
                    'id' => $cuenta->id,
                    'nombre' => $cuenta->nombre,
                    'banco' => $cuenta->banco,
                    'numero_cuenta' => $cuenta->numero_cuenta,
                    'numero_cuenta_mascarado' => $cuenta->numero_cuenta_mascarado,
                    'clabe' => $cuenta->clabe,
                    'saldo_inicial' => $cuenta->saldo_inicial,
                    'saldo_actual' => $cuenta->saldo_actual,
                    'moneda' => $cuenta->moneda,
                    'tipo' => $cuenta->tipo,
                    'activa' => $cuenta->activa,
                    'notas' => $cuenta->notas,
                    'color' => $cuenta->color ?? CuentaBancaria::getColorPorBanco($cuenta->banco),
                    'movimientos_count' => $cuenta->movimientos()->count(),
                ];
            });

        $totales = [
            'saldo_total' => CuentaBancaria::activas()->sum('saldo_actual'),
            'cuentas_activas' => CuentaBancaria::activas()->count(),
        ];

        return Inertia::render('CuentasBancarias/Index', [
            'cuentas' => $cuentas,
            'totales' => $totales,
        ]);
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        return Inertia::render('CuentasBancarias/Create', [
            'bancos' => $this->getBancosDisponibles(),
            'tipos' => ['corriente', 'ahorro', 'credito', 'inversion'],
        ]);
    }

    /**
     * Guardar nueva cuenta
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'banco' => 'required|string|max:50',
            'numero_cuenta' => 'nullable|string|max:20',
            'clabe' => 'nullable|string|max:18',
            'saldo_inicial' => 'required|numeric|min:0',
            'moneda' => 'nullable|string|max:3',
            'tipo' => 'nullable|string|in:corriente,ahorro,credito,inversion',
            'notas' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['saldo_actual'] = $validated['saldo_inicial'];
        $validated['color'] = $validated['color'] ?? CuentaBancaria::getColorPorBanco($validated['banco']);

        $cuenta = CuentaBancaria::create($validated);

        Log::info('Cuenta bancaria creada', [
            'cuenta_id' => $cuenta->id,
            'banco' => $cuenta->banco,
            'usuario_id' => auth()->id(),
        ]);

        return redirect()->route('cuentas-bancarias.index')
            ->with('success', "Cuenta '{$cuenta->nombre}' creada exitosamente");
    }

    /**
     * Ver detalle de cuenta
     */

    public function show(CuentaBancaria $cuentas_bancaria)
    {
        $cuenta = $cuentas_bancaria;
        
        $movimientos = $cuenta->movimientos()
            ->with(['conciliable']) // Solo cargar conciliable, cobrable se carga manualmente
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($mov) {
                // Extraer número de venta si el conciliable es una CuentasPorCobrar con venta
                $folio_venta = null;
                if ($mov->conciliable) {
                    // Si conciliable es CuentasPorCobrar
                    if ($mov->conciliable_type === \App\Models\CuentasPorCobrar::class) {
                        // Cargar cobrable manualmente
                        $mov->conciliable->load('cobrable');
                        if ($mov->conciliable->cobrable && get_class($mov->conciliable->cobrable) === \App\Models\Venta::class) {
                            $folio_venta = $mov->conciliable->cobrable->numero_venta;
                        }
                    }
                    // Si conciliable es EntregaDinero con tipo_origen = venta
                    if ($mov->conciliable_type === \App\Models\EntregaDinero::class) {
                        if ($mov->conciliable->tipo_origen === 'venta' && $mov->conciliable->id_origen) {
                            $venta = \App\Models\Venta::find($mov->conciliable->id_origen);
                            $folio_venta = $venta?->numero_venta;
                        }
                    }
                }
                
                $mov->folio_venta = $folio_venta;
                return $mov;
            });

        return Inertia::render('CuentasBancarias/Show', [
            'cuenta' => [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
                'banco' => $cuenta->banco,
                'numero_cuenta' => $cuenta->numero_cuenta,
                'clabe' => $cuenta->clabe,
                'saldo_inicial' => $cuenta->saldo_inicial,
                'saldo_actual' => $cuenta->saldo_actual,
                'moneda' => $cuenta->moneda,
                'tipo' => $cuenta->tipo,
                'activa' => $cuenta->activa,
                'notas' => $cuenta->notas,
                'color' => $cuenta->color ?? CuentaBancaria::getColorPorBanco($cuenta->banco),
            ],
            'movimientos' => $movimientos,
        ]);
    }

    /**
     * Movimientos de una cuenta bancaria con filtros y paginación
     */
    public function movimientos(Request $request, CuentaBancaria $cuentas_bancaria)
    {
        $cuenta = $cuentas_bancaria;
        
        // Filtros con defaults al mes actual
        $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
        $fechaHasta = $request->get('fecha_hasta', now()->endOfMonth()->toDateString());
        $tipo = $request->get('tipo'); // deposito/retiro
        $origenTipo = $request->get('origen_tipo'); // venta/renta/prestamo/traspaso/cobro/pago/otro
        
        $query = $cuenta->movimientos()
            ->with(['conciliable', 'usuario'])
            ->whereDate('fecha', '>=', $fechaDesde)
            ->whereDate('fecha', '<=', $fechaHasta)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');
        
        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        
        // Filtrar por tipo de origen
        if ($origenTipo) {
            $query->where('origen_tipo', $origenTipo);
        }
        
        $movimientos = $query->paginate(20)->withQueryString();
        
        // Estadísticas del período
        $statsQuery = $cuenta->movimientos()
            ->whereDate('fecha', '>=', $fechaDesde)
            ->whereDate('fecha', '<=', $fechaHasta);
        
        $stats = [
            'total_depositos' => (clone $statsQuery)->where('tipo', 'deposito')->sum('monto'),
            'total_retiros' => abs((clone $statsQuery)->where('tipo', 'retiro')->sum('monto')),
            'cantidad_movimientos' => (clone $statsQuery)->count(),
        ];
        
        // Tipos de origen disponibles para este rango
        $origenesDisponibles = $cuenta->movimientos()
            ->whereDate('fecha', '>=', $fechaDesde)
            ->whereDate('fecha', '<=', $fechaHasta)
            ->whereNotNull('origen_tipo')
            ->distinct()
            ->pluck('origen_tipo')
            ->toArray();
        
        return Inertia::render('CuentasBancarias/Movimientos', [
            'cuenta' => [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
                'banco' => $cuenta->banco,
                'saldo_actual' => $cuenta->saldo_actual,
                'color' => $cuenta->color ?? CuentaBancaria::getColorPorBanco($cuenta->banco),
            ],
            'movimientos' => $movimientos,
            'filtros' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'tipo' => $tipo,
                'origen_tipo' => $origenTipo,
            ],
            'stats' => $stats,
            'origenes_disponibles' => $origenesDisponibles,
        ]);
    }

    /**
     * Formulario de edición
     */
    public function edit(CuentaBancaria $cuentas_bancaria)
    {
        return Inertia::render('CuentasBancarias/Edit', [
            'cuenta' => $cuentas_bancaria,
            'bancos' => $this->getBancosDisponibles(),
            'tipos' => ['corriente', 'ahorro', 'credito', 'inversion'],
        ]);
    }

    /**
     * Actualizar cuenta
     */
    public function update(Request $request, CuentaBancaria $cuentas_bancaria)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'banco' => 'required|string|max:50',
            'numero_cuenta' => 'nullable|string|max:20',
            'clabe' => 'nullable|string|max:18',
            'saldo_inicial' => 'required|numeric|min:0',
            'moneda' => 'nullable|string|max:3',
            'tipo' => 'nullable|string|in:corriente,ahorro,credito,inversion',
            'activa' => 'nullable|boolean',
            'notas' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
        ]);

        $cuentas_bancaria->update($validated);

        // Recalcular saldo si cambió el saldo inicial
        if ($cuentas_bancaria->wasChanged('saldo_inicial')) {
            $cuentas_bancaria->recalcularSaldo();
        }

        return redirect()->route('cuentas-bancarias.index')
            ->with('success', 'Cuenta actualizada exitosamente');
    }

    /**
     * Eliminar cuenta
     */
    public function destroy(CuentaBancaria $cuentas_bancaria)
    {
        if ($cuentas_bancaria->movimientos()->exists()) {
            return back()->with('error', 'No se puede eliminar una cuenta con movimientos. Desactívela en su lugar.');
        }

        $cuentas_bancaria->delete();

        return redirect()->route('cuentas-bancarias.index')
            ->with('success', 'Cuenta eliminada');
    }

    /**
     * Recalcular saldo de una cuenta
     */
    public function recalcularSaldo(CuentaBancaria $cuenta_bancaria)
    {
        $nuevoSaldo = $cuenta_bancaria->recalcularSaldo();

        return back()->with('success', "Saldo recalculado: $" . number_format($nuevoSaldo, 2));
    }

    /**
     * API: Obtener cuentas activas para selects
     */
    public function apiCuentasActivas()
    {
        $cuentas = CuentaBancaria::activas()
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'banco', 'numero_cuenta', 'saldo_actual']);

        return response()->json($cuentas);
    }

    /**
     * Bancos disponibles
     */
    protected function getBancosDisponibles(): array
    {
        return [
            'BBVA',
            'Banorte',
            'Santander',
            'HSBC',
            'Citibanamex',
            'Scotiabank',
            'Banco Azteca',
            'Banregio',
            'Inbursa',
            'Otro',
        ];
    }

    /**
     * Registrar un movimiento manual (depósito o retiro)
     * Para ingresos/egresos que no provienen de operaciones regulares
     */
    public function registrarMovimientoManual(Request $request, CuentaBancaria $cuentas_bancaria)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:deposito,retiro',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'required|string|max:255',
            'categoria' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:100',
            'fecha' => 'nullable|date',
        ]);

        $origenTipo = $validated['categoria'] ?? 'otro';
        
        $movimiento = $cuentas_bancaria->registrarMovimiento(
            $validated['tipo'],
            $validated['monto'],
            $validated['concepto'],
            $origenTipo
        );

        // Actualizar referencia si se proporcionó
        if (!empty($validated['referencia'])) {
            $movimiento->update(['referencia' => $validated['referencia']]);
        }

        // Actualizar fecha si se proporcionó
        if (!empty($validated['fecha'])) {
            $movimiento->update(['fecha' => $validated['fecha']]);
        }

        $tipoTexto = $validated['tipo'] === 'deposito' ? 'Depósito' : 'Retiro';
        
        return back()->with('success', "{$tipoTexto} de \${$validated['monto']} registrado correctamente.");
    }
}
