<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CategoriaGasto;
use App\Models\CuentasPorPagar;
use App\Models\Proveedor;
use App\Models\Proyecto;
use App\Enums\EstadoCompra;
use App\Services\Compras\CompraCuentasPagarService;
use App\Services\Compras\CompraValidacionService;
use App\Models\Venta;
use App\Models\Cobranza;
use App\Models\CajaChica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GastoController extends Controller
{
    public function __construct(
        private readonly CompraCuentasPagarService $cuentasPagarService
    ) {
    }

    /**
     * Obtener balances de un usuario (Caja Chica y Efectivo de Cobros)
     */
    public function userBalances($userId)
    {
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        
        // Solo permitir ver balances de otros si es admin o si el usuario solicitado es el mismo
        if ($currentUser->id != $userId && !$currentUser->is_admin && !$currentUser->hasRole('super-admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Calcular balances usando el helper centralizado
        $balances = $this->calculateBalances($userId);

        // Buscar si el usuario tiene una tarjeta o cuenta asignada (ej. Banco Opata para Luis)
        $cuentaAsignada = \App\Models\CuentaBancaria::where('responsable_id', $userId)
            ->where('activa', true)
            ->first();

        // Si es Admin, incluir todos los fondos tipo "Otro" (Cajas Fuertes)
        $fondosEspeciales = [];
        if ($currentUser->is_admin || $currentUser->hasRole('super-admin')) {
            $fondosEspeciales = \App\Models\CuentaBancaria::where('banco', 'Otro')
                ->where('activa', true)
                ->get()
                ->map(fn($f) => [
                    'id' => $f->id,
                    'nombre' => $f->nombre,
                    'saldo' => (float) $f->saldo_actual
                ]);
        }

        return response()->json([
            'caja_chica' => $balances['caja_chica'],
            'efectivo_cobrado' => $balances['efectivo_cobrado'],
            'cuenta_bancaria_id' => $cuentaAsignada ? $cuentaAsignada->id : null,
            'cuenta_asignada_nombre' => $cuentaAsignada ? $cuentaAsignada->nombre : null,
            'cuenta_asignada_saldo' => $cuentaAsignada ? (float) $cuentaAsignada->saldo_actual : 0,
            'fondos_especiales' => $fondosEspeciales
        ]);
    }

    /**
     * Helper centralizado para calcular balances de un usuario
     */
    private function calculateBalances($userId): array
    {
        $balancesCC = CajaChica::where('user_id', $userId)
            ->selectRaw("SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos")
            ->selectRaw("SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as egresos")
            ->first();
        $cajaChicaBalance = ($balancesCC->ingresos ?? 0) - ($balancesCC->egresos ?? 0);

        $ventasEfectivo = Venta::where('pagado_por', $userId)
            ->where('pagado', true)
            ->where('metodo_pago', 'Efectivo')
            ->whereDoesntHave('entregas', fn($q) => $q->whereIn('estado', ['pendiente', 'recibido']))
            ->sum('total');

        $cobranzasEfectivo = Cobranza::where('responsable_cobro', $userId)
            ->where('estado', 'pagado')
            ->where('metodo_pago', 'efectivo')
            ->whereDoesntHave('entregas', fn($q) => $q->whereIn('estado', ['pendiente', 'recibido']))
            ->sum('monto_pagado');
            
        // Gastos pagados en efectivo que no han sido liquidados (entregados)
        $gastosEfectivo = Compra::where('user_id', $userId)
            ->where('tipo', 'gasto')
            ->where('metodo_pago', 'efectivo')
            ->whereNull('cuenta_bancaria_id')
            ->where('estado', EstadoCompra::Procesada->value)
            ->whereDoesntHave('entregas', fn($q) => $q->whereIn('estado', ['pendiente', 'recibido']))
            ->sum('total');

        return [
            'caja_chica' => (float) $cajaChicaBalance,
            'efectivo_cobrado' => (float) ($ventasEfectivo + $cobranzasEfectivo - $gastosEfectivo)
        ];
    }

    /**
     * Listar gastos operativos
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);
        $user = auth()->user();
        $isSuperAdmin = $user->is_admin || $user->hasRole('super-admin');
        $misGastos = $request->boolean('mis_gastos') || !$isSuperAdmin;

        $query = $this->getFilteredQuery($request)
            ->orderBy('fecha_compra', 'desc')
            ->orderBy('created_at', 'desc');

        // Estadísticas para Gráficos (Respetando privacidad)
        $statsBaseQuery = Compra::where('tipo', 'gasto')
            ->where('estado', EstadoCompra::Procesada->value);

        if (!$isSuperAdmin) {
            $statsBaseQuery->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_id', $user->id);
            });
        }

        $statsData = [
            'por_categoria' => (clone $statsBaseQuery)
                ->join('categoria_gastos', 'compras.categoria_gasto_id', '=', 'categoria_gastos.id')
                ->select('categoria_gastos.nombre', DB::raw('SUM(total) as total'))
                ->groupBy('categoria_gastos.nombre')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'por_proyecto' => (clone $statsBaseQuery)
                ->whereNotNull('proyecto_id')
                ->join('proyectos', 'compras.proyecto_id', '=', 'proyectos.id')
                ->select('proyectos.nombre', DB::raw('SUM(total) as total'))
                ->groupBy('proyectos.nombre')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'por_mes' => (clone $statsBaseQuery)
                ->select(
                    DB::raw("to_char(fecha_compra, 'Mon') as mes"), 
                    DB::raw("to_char(fecha_compra, 'YYYY-MM') as mes_sort"),
                    DB::raw('SUM(total) as total')
                )
                ->groupBy('mes', 'mes_sort')
                ->orderBy('mes_sort')
                ->take(6)
                ->get(),
            'por_tecnico' => (clone $statsBaseQuery)
                ->join('users', 'compras.user_id', '=', 'users.id')
                ->select('users.name', DB::raw('SUM(total) as total'))
                ->groupBy('users.name')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
        ];

        $totalMonto = $query->clone()
            ->where('estado', EstadoCompra::Procesada->value)
            ->sum('total');

        $gastos = $query->paginate($perPage)->withQueryString();
        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();
        
        $proyectos = $isSuperAdmin
            ? Proyecto::orderBy('nombre')->get(['id', 'nombre'])
            : $user->joinedProjects()->orderBy('nombre')->get(['proyectos.id', 'nombre']);

        // Resumen de Caja Chica (Todos los operativos para Admin, solo él mismo para Técnico)
        $resumenQuery = \App\Models\User::activos();
        if (!$isSuperAdmin) {
            $resumenQuery->where('id', $user->id);
        } else {
            $resumenQuery->where(function($q) use ($user) {
                $q->where('es_tecnico', true)->orWhere('es_empleado', true)->orWhere('id', $user->id);
            });
        }

        $cajaChicaResumen = $resumenQuery
            ->withSum(['cajaChica as ingresos' => fn($q) => $q->where('tipo', 'ingreso')], 'monto')
            ->withSum(['cajaChica as egresos' => fn($q) => $q->where('tipo', 'egreso')], 'monto')
            ->get()
            ->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'balance' => (float) (($u->ingresos ?? 0) - ($u->egresos ?? 0))
                ];
            })->sortByDesc('balance')->values();

        $tecnicos = \App\Models\User::activos()
            ->where(function($q) {
                $q->where('es_tecnico', true)->orWhere('es_empleado', true);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Gastos/Index', [
            'gastos' => $gastos,
            'categorias' => $categorias,
            'proyectos' => $proyectos,
            'filters' => $request->all(),
            'totalMonto' => (float) $totalMonto,
            'cajaChicaResumen' => $cajaChicaResumen,
            'tecnicos' => $tecnicos,
            'statsData' => $statsData,
            'misGastos' => $misGastos
        ]);
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->is_admin || $user->hasRole('super-admin');

        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();
        $proveedores = Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get();
        
        $cuentasBancarias = \App\Models\CuentaBancaria::activas()
            ->with('responsable:id,name')
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'banco' => $c->banco,
                    'saldo_actual' => $c->saldo_actual,
                    'responsable_id' => $c->responsable_id,
                    'responsable_nombre' => $c->responsable?->name ?? 'Sin asignar',
                    'label' => "{$c->banco} - {$c->nombre} (" . ($c->responsable?->name ?? 'Global') . ")"
                ];
            });

        // Filtrar proyectos: Donde el usuario participe (si no es admin)
        $proyectos = Proyecto::when(!$isSuperAdmin, function($q) use ($user) {
                $q->whereHas('members', fn($u) => $u->where('users.id', $user->id));
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $tecnicos = \App\Models\User::activos()
            ->where(function($q) {
                $q->where('es_tecnico', true)
                  ->orWhere('es_empleado', true);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        // Calcular balances usando el helper
        $balances = $this->calculateBalances($user->id);

        return Inertia::render('Gastos/Create', [
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'cuentasBancarias' => $cuentasBancarias,
            'proyectos' => $proyectos,
            'tecnicos' => $tecnicos,
            'balances' => $balances,
        ]);
    }

    /**
     * Guardar nuevo gasto
     */
    public function store(Request $request)
    {
        // ✅ FIX: Eliminadas reglas duplicadas
        $validated = $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'categoria_gasto_id' => 'required|exists:categoria_gastos,id,activo,1',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:500',
            'fecha' => 'nullable|date',
            'metodo_pago' => 'required|string',
            'notas' => 'nullable|string',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'proyecto_id' => 'nullable|exists:proyectos,id',
            // Campos CFDI opcionales (cuando se importa desde XML)
            'cfdi_uuid' => 'nullable|string|max:36',
            'cfdi_folio' => 'nullable|string|max:50',
            'cfdi_serie' => 'nullable|string|max:25',
            'cfdi_tipo_comprobante' => 'nullable|string|max:5',
            'cfdi_forma_pago' => 'nullable|string|max:5',
            'cfdi_metodo_pago' => 'nullable|string|max:5',
            'cfdi_uso' => 'nullable|string|max:10',
            'cfdi_fecha' => 'nullable|date',
            'cfdi_emisor_rfc' => 'nullable|string|max:20',
            'cfdi_emisor_nombre' => 'nullable|string|max:255',
            // Si viene del XML, podemos recibir subtotal e IVA directamente
            'subtotal_cfdi' => 'nullable|numeric|min:0',
            'iva_cfdi' => 'nullable|numeric|min:0',
            'descuento_cfdi' => 'nullable|numeric|min:0',
            // Quien realiza el gasto (para que el admin pueda registrar a nombre de otros)
            'user_id' => 'nullable|exists:users,id',
            'comprobante' => 'nullable|image|max:10240', // 10MB max
        ]);

        try {
            \DB::transaction(function () use ($validated, $request) {
                // Si viene del CFDI, usar subtotal e IVA del XML
                if (!empty($validated['subtotal_cfdi']) && !empty($validated['iva_cfdi'])) {
                    $subtotal = $validated['subtotal_cfdi'];
                    $iva = $validated['iva_cfdi'];
                    $monto = $validated['monto'];
                    $descuento = $validated['descuento_cfdi'] ?? 0;
                } else {
                    // Calcular IVA normalmente
                    $monto = $validated['monto'];
                    $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                    $subtotal = $monto / (1 + $ivaRate);
                    $iva = $monto - $subtotal;
                    $descuento = 0;
                }

                // ✅ FIX: Generar número de gasto
                $numeroGasto = $this->generarNumeroGasto();

                // Lógica de Usuario: Permitir que el Admin asigne el gasto a otra persona (Luis, etc.)
                $isSuperAdmin = auth()->user()->is_admin || auth()->user()->hasRole('super-admin');
                $userId = ($isSuperAdmin && $request->filled('user_id')) 
                    ? $request->user_id 
                    : auth()->id();

                // Crear gasto (es una compra con tipo='gasto')
                $gasto = Compra::create([
                    'numero_compra' => $numeroGasto, // ✅ FIX: Agregar número
                    'tipo' => 'gasto',
                    'categoria_gasto_id' => $validated['categoria_gasto_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'almacen_id' => null, // Los gastos no tienen almacén
                    'metodo_pago' => $validated['metodo_pago'],
                    'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? null,
                    'fecha_compra' => $validated['fecha'] ?? now(),
                    'subtotal' => $subtotal,
                    'descuento_general' => $descuento,
                    'descuento_items' => 0,
                    'iva' => $iva,
                    'total' => $monto,
                    'notas' => $validated['descripcion'] . ($validated['notas'] ? "\n\n" . $validated['notas'] : ''),
                    'estado' => EstadoCompra::Procesada->value,
                    'inventario_procesado' => false, // Gastos no afectan inventario
                    'user_id' => $userId, // ✅ FIX: Usar el usuario asignado (técnico o admin)
                    'created_by' => auth()->id(),
                    'proyecto_id' => $validated['proyecto_id'] ?? null,
                    // Campos CFDI
                    'cfdi_uuid' => $validated['cfdi_uuid'] ?? null,
                    'cfdi_folio' => $validated['cfdi_folio'] ?? null,
                    'cfdi_serie' => $validated['cfdi_serie'] ?? null,
                    'cfdi_tipo_comprobante' => $validated['cfdi_tipo_comprobante'] ?? null,
                    'cfdi_forma_pago' => $validated['cfdi_forma_pago'] ?? null,
                    'cfdi_metodo_pago' => $validated['cfdi_metodo_pago'] ?? null,
                    'cfdi_uso' => $validated['cfdi_uso'] ?? null,
                    'cfdi_fecha' => $validated['cfdi_fecha'] ?? null,
                    'cfdi_emisor_rfc' => $validated['cfdi_emisor_rfc'] ?? null,
                    'cfdi_emisor_nombre' => $validated['cfdi_emisor_nombre'] ?? null,
                ]);

                // ✅ Guardar comprobante si se subió uno
                if ($request->hasFile('comprobante')) {
                    $path = $request->file('comprobante')->store('comprobantes_gastos', 'public');
                    $gasto->update(['comprobante_path' => $path]);
                }

                // Crear cuenta por pagar
                // Usar fecha del CFDI si existe, si no usar fecha del gasto
                $fechaBaseCuenta = $validated['cfdi_fecha'] ?? $validated['fecha'] ?? null;
                $cuentaPorPagar = $this->cuentasPagarService->crearCuentaPorPagar($gasto, $monto, $fechaBaseCuenta);

                // ✅ Si hay cuenta bancaria seleccionada, descontar del banco y marcar como pagado
                if (!empty($validated['cuenta_bancaria_id'])) {
                    $cuentaBancaria = \App\Models\CuentaBancaria::find($validated['cuenta_bancaria_id']);
                    if ($cuentaBancaria) {
                        // Descontar del banco
                        $concepto = "Gasto: {$validated['descripcion']} ({$gasto->numero_compra})";
                        $cuentaBancaria->registrarMovimiento('retiro', $monto, $concepto, 'pago', $cuentaPorPagar);

                        // Marcar la cuenta por pagar como pagada
                        $cuentaPorPagar->update([
                            'monto_pagado' => $monto,
                            'monto_pendiente' => 0,
                            'estado' => 'pagado',
                            'notas' => 'Pagado de contado al registrar el gasto',
                        ]);

                        \Log::info('Gasto pagado de contado - Banco actualizado', [
                            'gasto_id' => $gasto->id,
                            'cuenta_bancaria_id' => $cuentaBancaria->id,
                            'monto' => $monto
                        ]);
                    }
                }

                // ✅ Si el método de pago es Caja Chica, registrar el movimiento en la caja del usuario responsable
                if ($validated['metodo_pago'] === 'caja_chica') {
                    \App\Models\CajaChica::create([
                        'empresa_id' => $gasto->empresa_id,
                        'concepto' => "Gasto: " . ($validated['descripcion'] ?? 'Sin descripción') . " ({$gasto->numero_compra})",
                        'monto' => $monto,
                        'tipo' => 'egreso',
                        'fecha' => $validated['fecha'] ?? now(),
                        'user_id' => $userId,
                        'categoria' => 'Gasto Operativo',
                        'nota' => $validated['notas'] ?? null,
                        'compra_id' => $gasto->id,
                    ]);
                }

                \Log::info('Gasto creado exitosamente', [
                    'gasto_id' => $gasto->id,
                    'numero' => $gasto->numero_compra,
                    'monto' => $monto
                ]);
            });

            return redirect()->route('gastos.index')->with('success', 'Gasto registrado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error al crear gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Ver detalle de gasto
     */
    public function show($id)
    {
        $gasto = Compra::with(['proveedor', 'categoriaGasto', 'cuentasPorPagar', 'cuentaBancaria'])
            ->where('tipo', 'gasto')
            ->findOrFail($id);

        return Inertia::render('Gastos/Show', [
            'gasto' => $gasto,
        ]);
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $gasto = Compra::where('tipo', 'gasto')->findOrFail($id);
        $user = auth()->user();
        $isSuperAdmin = $user->is_admin || $user->hasRole('super-admin');

        // ✅ FIX: No permitir editar gastos cancelados
        if ($gasto->estado === EstadoCompra::Cancelada->value) {
            return redirect()->back()->with('error', 'No se pueden editar gastos cancelados.');
        }

        if ($gasto->estado !== EstadoCompra::Procesada->value) {
            return redirect()->back()->with('error', 'Solo se pueden editar gastos procesados.');
        }

        $categorias = CategoriaGasto::activas()->orderBy('nombre')->get();
        $proveedores = Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get();
        
        $cuentasBancarias = \App\Models\CuentaBancaria::activas()
            ->with('responsable:id,name')
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get()
            ->map(function($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'banco' => $c->banco,
                    'saldo_actual' => $c->saldo_actual,
                    'responsable_id' => $c->responsable_id,
                    'responsable_nombre' => $c->responsable?->name ?? 'Sin asignar',
                    'label' => "{$c->banco} - {$c->nombre} (" . ($c->responsable?->name ?? 'Global') . ")"
                ];
            });

        // Filtrar proyectos: Donde el usuario participe
        $proyectos = Proyecto::when(!$isSuperAdmin, function($q) use ($user) {
                $q->whereHas('members', fn($u) => $u->where('users.id', $user->id));
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $tecnicos = \App\Models\User::activos()
            ->where(function($q) {
                $q->where('es_tecnico', true)
                  ->orWhere('es_empleado', true);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        // Calcular balances para el usuario actual
        $cajaChicaBalance = CajaChica::where('user_id', $user->id)->where('tipo', 'ingreso')->sum('monto') 
                          - CajaChica::where('user_id', $user->id)->where('tipo', 'egreso')->sum('monto');

        // Calcular balances usando el helper
        $balances = $this->calculateBalances($user->id);

        return Inertia::render('Gastos/Edit', [
            'gasto' => $gasto,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'proyectos' => $proyectos,
            'cuentasBancarias' => $cuentasBancarias,
            'tecnicos' => $tecnicos,
            'balances' => $balances
        ]);
    }

    /**
     * Actualizar gasto
     */
    public function update(Request $request, $id)
    {
        $gasto = Compra::with('cuentasPorPagar')->where('tipo', 'gasto')->findOrFail($id);

        if ($gasto->estado !== EstadoCompra::Procesada->value) {
            return redirect()->back()->with('error', 'Solo se pueden editar gastos procesados.');
        }

        // Validación de integridad si hay pagos
        if ($gasto->cuentasPorPagar && $gasto->cuentasPorPagar->monto_pagado > 0) {
            if (
                $request->filled('monto') && abs($request->monto - $gasto->total) > 0.01 ||
                $request->filled('proveedor_id') && $request->proveedor_id != $gasto->proveedor_id
            ) {
                return redirect()->back()->with('error', 'No se puede modificar el monto ni el proveedor de un gasto que ya tiene pagos registrados.');
            }
        }

        // ✅ FIX: Eliminadas reglas duplicadas
        $validated = $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'categoria_gasto_id' => 'required|exists:categoria_gastos,id',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:500',
            'fecha' => 'nullable|date',
            'metodo_pago' => 'required|string',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'notas' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'comprobante' => 'nullable|image|max:10240', // 10MB max
        ]);

        try {
            DB::transaction(function () use ($gasto, $validated, $request) {
                // Calcular nuevos montos
                $monto = $validated['monto'];
                $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                $subtotal = $monto / (1 + $ivaRate);
                $iva = $monto - $subtotal;

                // Lógica de Usuario: Permitir que el Admin cambie quien hizo el gasto
                $isSuperAdmin = auth()->user()->is_admin || auth()->user()->hasRole('super-admin');
                
                // Actualizar gasto
                $updateData = [
                    'categoria_gasto_id' => $validated['categoria_gasto_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'metodo_pago' => $validated['metodo_pago'],
                    'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? null,
                    'fecha_compra' => $validated['fecha'] ?? now(),
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $monto,
                    'notas' => $validated['descripcion'] . ($validated['notas'] ? "\n\n" . $validated['notas'] : ''),
                ];

                if ($isSuperAdmin && $request->filled('user_id')) {
                    $updateData['user_id'] = $validated['user_id'];
                }

                // ✅ Actualizar comprobante si se subió uno nuevo
                if ($request->hasFile('comprobante')) {
                    // Eliminar anterior si existe
                    if ($gasto->comprobante_path) {
                        \Storage::disk('public')->delete($gasto->comprobante_path);
                    }
                    $path = $request->file('comprobante')->store('comprobantes_gastos', 'public');
                    $updateData['comprobante_path'] = $path;
                }

                $gasto->update($updateData);

                // Actualizar cuenta por pagar de forma segura
                $this->cuentasPagarService->actualizarCuentaPorPagar($gasto, $monto);

                // LOGICA DE BANCO: Sincronizar movimientos
                if (!empty($validated['cuenta_bancaria_id'])) {
                    $cuentaBancaria = \App\Models\CuentaBancaria::find($validated['cuenta_bancaria_id']);
                    if ($cuentaBancaria) {
                        $movimientoExistente = \App\Models\MovimientoBancario::where('conciliable_type', \App\Models\CuentasPorPagar::class)
                            ->where('conciliable_id', $gasto->cuentasPorPagar->id)
                            ->first();

                        // Si el movimiento no existe, o cambió la cuenta, o cambió el monto
                        if (!$movimientoExistente || $movimientoExistente->cuenta_bancaria_id != $validated['cuenta_bancaria_id'] || abs($movimientoExistente->monto - $monto) > 0.01) {
                            if ($movimientoExistente) {
                                $movimientoExistente->delete();
                            }
                            
                            $concepto = "Gasto Editado: {$validated['descripcion']} ({$gasto->numero_compra})";
                            $cuentaBancaria->registrarMovimiento('retiro', $monto, $concepto, 'pago', $gasto->cuentasPorPagar);
                        }

                        // Asegurar que la cuenta por pagar esté pagada
                        $gasto->cuentasPorPagar->update([
                            'monto_pagado' => $monto,
                            'monto_pendiente' => 0,
                            'estado' => 'pagado',
                        ]);
                    }
                } else {
                    // Si ya no tiene cuenta bancaria, eliminar movimientos previos
                    \App\Models\MovimientoBancario::where('conciliable_type', \App\Models\CuentasPorPagar::class)
                        ->where('conciliable_id', $gasto->cuentasPorPagar->id)
                        ->delete();
                    
                    // Si no es banco, resetear estado de pago (a menos que sea Caja Chica o Efectivo que se marcan como pagado)
                    if ($validated['metodo_pago'] !== 'caja_chica' && $validated['metodo_pago'] !== 'efectivo') {
                        $gasto->cuentasPorPagar->update([
                            'monto_pagado' => 0,
                            'monto_pendiente' => $monto,
                            'estado' => 'pendiente',
                        ]);
                    }
                }

                // LOGICA DE CAJA CHICA: Sincronizar movimientos
                if ($validated['metodo_pago'] === 'caja_chica') {
                    \App\Models\CajaChica::updateOrCreate(
                        ['compra_id' => $gasto->id],
                        [
                            'empresa_id' => $gasto->empresa_id,
                            'concepto' => "Gasto: " . ($validated['descripcion'] ?? 'Sin descripción') . " ({$gasto->numero_compra})",
                            'monto' => $monto,
                            'tipo' => 'egreso',
                            'fecha' => $validated['fecha'] ?? now(),
                            'user_id' => $updateData['user_id'] ?? $gasto->user_id,
                            'categoria' => 'Gasto Operativo',
                            'nota' => $validated['notas'] ?? null,
                        ]
                    );
                } else {
                    \App\Models\CajaChica::where('compra_id', $gasto->id)->delete();
                }

                \Log::info('Gasto actualizado exitosamente', ['gasto_id' => $gasto->id]);
            });

            return redirect()->route('gastos.index')->with('success', 'Gasto actualizado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Cancelar gasto
     */
    public function cancel($id)
    {
        try {
            $gasto = Compra::where('tipo', 'gasto')->findOrFail($id);

            if ($gasto->estado !== EstadoCompra::Procesada->value) {
                return redirect()->back()->with('error', 'Solo se pueden cancelar gastos procesados.');
            }

            DB::transaction(function () use ($gasto) {
                // Cancelar cuenta por pagar
                $this->cuentasPagarService->cancelarCuentaPorPagar($gasto);

                // Cambiar estado
                $gasto->update(['estado' => EstadoCompra::Cancelada->value]);
            });

            return redirect()->route('gastos.index')->with('success', 'Gasto cancelado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error al cancelar gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Eliminar gasto
     */
    public function destroy($id)
    {
        try {
            $gasto = Compra::with('cuentasPorPagar')->where('tipo', 'gasto')->findOrFail($id);

            // ✅ FIX: Validar que no tenga pagos registrados
            if ($gasto->cuentasPorPagar && $gasto->cuentasPorPagar->monto_pagado > 0) {
                return redirect()->back()->with('error', 'No se puede eliminar un gasto que ya tiene pagos registrados.');
            }

            if ($gasto->estado === EstadoCompra::Procesada->value) {
                DB::transaction(function () use ($gasto) {
                    // Cancelar primero
                    $this->cuentasPagarService->cancelarCuentaPorPagar($gasto);
                    $gasto->update(['estado' => EstadoCompra::Cancelada->value]);
                    $gasto->delete();
                });
            } else {
                $gasto->delete();
            }

            return redirect()->route('gastos.index')->with('success', 'Gasto eliminado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al eliminar gasto: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Genera un número de gasto único secuencial.
     */
    private function generarNumeroGasto(): string
    {
        $ultimoGasto = Compra::where('tipo', 'gasto')
            ->where('numero_compra', 'LIKE', 'G%')
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        if (!$ultimoGasto || !$ultimoGasto->numero_compra) {
            return 'G0001';
        }

        $matches = [];
        if (preg_match('/G(\d+)$/', $ultimoGasto->numero_compra, $matches)) {
            $ultimoNumero = (int) $matches[1];
            $siguienteNumero = $ultimoNumero + 1;
            $nuevoNumero = 'G' . str_pad((string) $siguienteNumero, 4, '0', STR_PAD_LEFT);

            // Verificar que no exista ya
            while (Compra::where('numero_compra', $nuevoNumero)->exists()) {
                $siguienteNumero++;
                $nuevoNumero = 'G' . str_pad((string) $siguienteNumero, 4, '0', STR_PAD_LEFT);
            }

            return $nuevoNumero;
        }

        return 'G0001';
    }

    /**
     * Parsear archivo XML de CFDI y retornar datos para crear gasto
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseXmlCfdi(Request $request)
    {
        $request->validate([
            'xml_file' => 'required|file|max:5120', // Máximo 5MB
        ]);

        try {
            $file = $request->file('xml_file');

            // Validar que sea XML
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();

            if ($extension !== 'xml' && !str_contains($mimeType, 'xml')) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo debe ser un XML válido.',
                ], 422);
            }

            $xmlContent = file_get_contents($file->path());

            // Parsear XML usando el servicio existente
            $parserService = app(\App\Services\CfdiXmlParserService::class);
            $data = $parserService->parseCfdiXml($xmlContent);

            // ✅ Poblar automáticamente catálogos SAT desde los conceptos del XML
            $satStats = $parserService->poblarCatalogosSatDesdeConceptos($data['conceptos']);
            $data['sat_catalogs_stats'] = $satStats;

            // Buscar proveedor por RFC del emisor
            $proveedor = null;
            if (!empty($data['emisor']['rfc'])) {
                $proveedor = $parserService->findProveedorByRfc($data['emisor']['rfc']);
            }

            $data['proveedor_encontrado'] = $proveedor ? [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre_razon_social,
                'rfc' => $proveedor->rfc,
            ] : null;

            // Para gastos, simplificamos: no mapeamos a productos, solo extraemos conceptos como descripción
            $data['descripcion_sugerida'] = $this->generarDescripcionDesdeConceptos($data['conceptos']);

            \Log::info('XML CFDI parseado para gasto', [
                'folio' => $data['serie'] . $data['folio'],
                'emisor_rfc' => $data['emisor']['rfc'] ?? 'N/A',
                'total' => $data['total'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al parsear XML CFDI para gasto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el XML: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Genera una descripción combinada desde los conceptos del CFDI
     */
    private function generarDescripcionDesdeConceptos(array $conceptos): string
    {
        if (empty($conceptos)) {
            return '';
        }

        // Tomar las descripciones de los conceptos y combinarlas
        $descripciones = array_map(function ($concepto) {
            $desc = $concepto['descripcion'] ?? '';
            // Limitar longitud de cada descripción
            if (strlen($desc) > 100) {
                $desc = substr($desc, 0, 97) . '...';
            }
            return $desc;
        }, $conceptos);

        // Combinar con separador
        $descripcionCombinada = implode('; ', array_filter($descripciones));

        // Limitar longitud total
        if (strlen($descripcionCombinada) > 500) {
            $descripcionCombinada = substr($descripcionCombinada, 0, 497) . '...';
        }

        return $descripcionCombinada;
    }

    /**
     * Exportar gastos a Excel
     */
    public function exportExcel(Request $request)
    {
        $query = $this->getFilteredQuery($request)
            ->orderBy('fecha_compra', 'desc');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\GastosExport($query), 
            'reporte_gastos_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Helper para obtener la query filtrada de gastos
     */
    private function getFilteredQuery(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->is_admin || $user->hasRole('super-admin');
        $misGastos = $request->boolean('mis_gastos') || !$isSuperAdmin;

        $query = Compra::with(['proveedor', 'categoriaGasto', 'proyecto', 'createdBy', 'cuentaBancaria', 'user'])
            ->where('tipo', 'gasto');

        // Filtros
        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_compra', 'like', "%{$search}%")
                    ->orWhere('notas', 'like', "%{$search}%")
                    ->orWhereHas('proveedor', fn($q) => $q->where('nombre', 'like', "%{$search}%"));
            });
        }

        if ($categoriaId = $request->get('categoria_id')) {
            $query->where('categoria_gasto_id', $categoriaId);
        }

        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }

        if ($proyectoId = $request->get('proyecto_id')) {
            $query->where('proyecto_id', $proyectoId);
        }

        if ($fechaDesde = $request->get('fecha_desde')) {
            $query->whereDate('fecha_compra', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->get('fecha_hasta')) {
            $query->whereDate('fecha_compra', '<=', $fechaHasta);
        }

        if ($misGastos) {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_id', $user->id);
            });
        }

        return $query;
    }
}
