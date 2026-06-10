<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GastoController;
use App\Models\Compra;
use App\Models\CuentaBancaria;
use App\Models\CategoriaGasto;
use App\Models\Proyecto;
use App\Models\User;
use App\Enums\EstadoCompra;
use App\Http\Controllers\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GastoApiController extends Controller
{
    use ApiResponse;

    const CAJA_CHICA_OFFSET = 1000000;

    /**
     * Historial unificado de gastos y movimientos de caja chica
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);
            $perPage = $request->integer('per_page', 15);
            $sortDir = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

            // Query base para Compras (Gastos formalizados)
            $gastosQuery = DB::table('compras')
                ->select([
                    'id',
                    'total as monto',
                    'notas as descripcion',
                    'fecha_compra as fecha',
                    'created_at',
                    'metodo_pago',
                    DB::raw("'gasto' as tipo_registro")
                ])
                ->where('tipo', 'gasto')
                ->whereNull('deleted_at');

            // Query base para Caja Chica (Movimientos de efectivo directos)
            $cajaQuery = DB::table('caja_chica')
                ->select([
                    DB::raw('id + ' . self::CAJA_CHICA_OFFSET . ' as id'),
                    'monto',
                    'concepto as descripcion',
                    'fecha',
                    'created_at',
                    DB::raw("'efectivo' as metodo_pago"),
                    DB::raw("'caja' as tipo_registro")
                ])
                ->whereNull('compra_id');

            // Aplicar filtros de visibilidad
            if (!$isAdmin || ($request->filled('mis_gastos') && $request->boolean('mis_gastos'))) {
                $gastosQuery->where(function($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('user_id', $user->id);
                });
                $cajaQuery->where('user_id', $user->id);
            }

            // Filtros de fecha
            if ($request->filled('fecha_desde')) {
                $gastosQuery->whereDate('fecha_compra', '>=', $request->fecha_desde);
                $cajaQuery->whereDate('fecha', '>=', $request->fecha_desde);
            }
            if ($request->filled('fecha_hasta')) {
                $gastosQuery->whereDate('fecha_compra', '<=', $request->fecha_hasta);
                $cajaQuery->whereDate('fecha', '<=', $request->fecha_hasta);
            }

            // Filtro por búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $gastosQuery->where('notas', 'ILIKE', "%{$search}%");
                $cajaQuery->where('concepto', 'ILIKE', "%{$search}%");
            }

            // Unificar, ordenar y paginar
            $unified = $gastosQuery->union($cajaQuery)
                ->orderBy('fecha', $sortDir)
                ->orderBy('created_at', $sortDir)
                ->paginate($perPage);

            // Transformar para incluir relaciones
            $items = $unified->getCollection()->map(function($item) {
                if ($item->tipo_registro === 'gasto') {
                    $gasto = Compra::with(['categoriaGasto', 'proyecto', 'proveedor', 'user'])->find($item->id);
                    if (!$gasto) return null;
                    return [
                        'id' => $gasto->id,
                        'total' => (float) $gasto->total,
                        'notas' => $gasto->notas,
                        'fecha_compra' => $gasto->fecha_compra ? $gasto->fecha_compra->format('Y-m-d') : null,
                        'created_at' => $gasto->created_at->format('Y-m-d H:i:s'),
                        'metodo_pago' => $gasto->metodo_pago === 'tarjeta_debito' ? 'tarjeta' : $gasto->metodo_pago,
                        'categoria_gasto' => ['nombre' => $gasto->categoriaGasto->nombre ?? 'General'],
                        'proyecto' => $gasto->proyecto ? ['nombre' => $gasto->proyecto->nombre] : null,
                        'user' => $gasto->user ? ['id' => $gasto->user->id, 'name' => $gasto->user->name] : null,
                        'tipo_movimiento' => 'egreso',
                        'is_caja_chica' => false
                    ];
                } else {
                    $mov = \App\Models\CajaChica::find($item->id - self::CAJA_CHICA_OFFSET);
                    if (!$mov) return null;
                    return [
                        'id' => $item->id,
                        'total' => (float) $mov->monto,
                        'notas' => ($mov->tipo === 'ingreso' ? "📥 INGRESO: " : "📤 EGRESO: ") . $mov->concepto . ($mov->nota ? "\n\n" . $mov->nota : ""),
                        'fecha_compra' => $mov->fecha ? $mov->fecha->format('Y-m-d') : null,
                        'created_at' => $mov->created_at->format('Y-m-d H:i:s'),
                        'metodo_pago' => 'caja_chica',
                        'categoria_gasto' => ['nombre' => $mov->categoria ?? 'Caja Chica'],
                        'tipo_movimiento' => $mov->tipo,
                        'is_caja_chica' => true
                    ];
                }
            })->filter()->values();

            $unified->setCollection($items);

            return $this->success($unified);
        } catch (\Exception $e) {
            Log::error('GastoApiController@index: ' . $e->getMessage());
            return $this->serverError('Error al obtener historial', $e);
        }
    }

    /**
     * Registrar un nuevo gasto desde la App
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_gasto_id' => 'required|exists:categoria_gastos,id,activo,1',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:500',
            'fecha' => 'nullable|date',
            'metodo_pago' => 'required|string|in:efectivo,tarjeta,caja_chica,transferencia',
            'cuenta_bancaria_id' => 'required_if:metodo_pago,tarjeta|required_if:metodo_pago,transferencia|nullable|exists:cuentas_bancarias,id',
            'proyecto_id' => 'nullable|exists:proyectos,id',
            'user_id' => 'nullable|exists:users,id',
            'notas' => 'nullable|string',
            'comprobante' => 'nullable|file|image|max:10240'
        ]);

        try {
            return DB::transaction(function () use ($validated, $request) {
                $authUser = Auth::user();
                $isAdmin = $authUser->hasAnyRole(['admin', 'super-admin']);
                
                // Si es admin puede asignar a otro, si no es el mismo
                $userId = ($isAdmin && !empty($validated['user_id'])) ? $validated['user_id'] : $authUser->id;
                
                $monto = (float) $validated['monto'];
                $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                $subtotal = $monto / (1 + $ivaRate);
                $iva = $monto - $subtotal;

                // Generar número de gasto usando la lógica centralizada
                $gastoController = new GastoController(app(\App\Services\Compras\CompraCuentasPagarService::class));
                $method = new \ReflectionMethod(GastoController::class, 'generarNumeroGasto');
                $method->setAccessible(true);
                $numeroGasto = $method->invoke($gastoController);

                $gasto = Compra::create([
                    'numero_compra' => $numeroGasto,
                    'tipo' => 'gasto',
                    'categoria_gasto_id' => $validated['categoria_gasto_id'],
                    'proveedor_id' => null,
                    'metodo_pago' => in_array($validated['metodo_pago'], ['tarjeta', 'transferencia']) ? 'tarjeta_debito' : 'efectivo',
                    'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? null,
                    'fecha_compra' => $validated['fecha'] ?? now(),
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $monto,
                    'notas' => $validated['descripcion'] . ($validated['notas'] ? "\n\n" . $validated['notas'] : ''),
                    'estado' => EstadoCompra::Procesada->value,
                    'user_id' => $userId,
                    'created_by' => $authUser->id,
                    'proyecto_id' => $validated['proyecto_id'] ?? null,
                ]);

                // Manejar comprobante
                if ($request->hasFile('comprobante')) {
                    $path = $request->file('comprobante')->store('comprobantes/gastos', 'public');
                    $gasto->update(['comprobante' => $path]);
                }

                // Registrar en Banco si aplica
                if (in_array($validated['metodo_pago'], ['tarjeta', 'transferencia']) && !empty($validated['cuenta_bancaria_id'])) {
                    $cuenta = CuentaBancaria::find($validated['cuenta_bancaria_id']);
                    if ($cuenta) {
                        $cuenta->registrarMovimiento('retiro', $monto, "Gasto móvil: " . $validated['descripcion'] . " (" . $numeroGasto . ")", 'pago');
                    }
                }

                // Registrar en Caja Chica si aplica
                if ($validated['metodo_pago'] === 'caja_chica') {
                    \App\Models\CajaChica::create([
                        'concepto' => "Gasto móvil: " . $validated['descripcion'] . " (" . $numeroGasto . ")",
                        'monto' => $monto,
                        'tipo' => 'egreso',
                        'fecha' => $validated['fecha'] ?? now(),
                        'user_id' => $userId,
                        'categoria' => 'Gasto Operativo (App)',
                        'nota' => $validated['notas'] ?? null,
                        'compra_id' => $gasto->id,
                    ]);
                }

                // Crear Cuenta por Pagar marcada como pagada
                $cpService = app(\App\Services\Compras\CompraCuentasPagarService::class);
                $cp = $cpService->crearCuentaPorPagar($gasto, $monto, $gasto->fecha_compra);
                $cp->update([
                    'monto_pagado' => $monto,
                    'monto_pendiente' => 0,
                    'estado' => 'pagado',
                    'notas' => 'Pagado desde app móvil',
                ]);

                return $this->created($gasto->load('categoriaGasto'), 'Gasto registrado');
            });
        } catch (\Exception $e) {
            Log::error('GastoApiController@store: ' . $e->getMessage());
            return $this->serverError('Error al registrar el gasto', $e);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);
            
            if ($id >= self::CAJA_CHICA_OFFSET) {
                $mov = \App\Models\CajaChica::with('user')->findOrFail($id - self::CAJA_CHICA_OFFSET);
                if ($mov->user_id !== $user->id && !$isAdmin) {
                    return $this->forbidden();
                }
                return $this->success([
                    'id' => $id,
                    'total' => (float) $mov->monto,
                    'notas' => $mov->concepto . ($mov->nota ? "\n\n" . $mov->nota : ""),
                    'fecha_compra' => $mov->fecha ? $mov->fecha->format('Y-m-d') : null,
                    'metodo_pago' => 'caja_chica',
                    'categoria_gasto' => ['nombre' => $mov->categoria ?? 'Caja Chica'],
                    'tipo_movimiento' => $mov->tipo,
                    'is_caja_chica' => true,
                    'comprobante_url' => $mov->comprobante_url
                ]);
            }

            $gasto = Compra::with(['proveedor', 'categoriaGasto', 'proyecto', 'cuentaBancaria', 'user'])
                ->findOrFail($id);

            if ($gasto->created_by !== $user->id && $gasto->user_id !== $user->id && !$isAdmin) {
                return $this->forbidden();
            }

            $gasto->comprobante_url = $gasto->comprobante ? Storage::url($gasto->comprobante) : null;

            return $this->success($gasto);
        } catch (\Exception $e) {
            return $this->serverError('Error al obtener detalle', $e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);

            if ($id >= self::CAJA_CHICA_OFFSET) {
                $mov = \App\Models\CajaChica::findOrFail($id - self::CAJA_CHICA_OFFSET);
                if ($mov->user_id !== $user->id && !$isAdmin) return $this->forbidden();
                $mov->update($request->only(['concepto', 'nota', 'fecha']));
                return $this->success($mov, 'Movimiento actualizado');
            }

            $gasto = Compra::findOrFail($id);
            if ($gasto->created_by !== $user->id && $gasto->user_id !== $user->id && !$isAdmin) return $this->forbidden();

            $validated = $request->validate([
                'descripcion' => 'nullable|string|max:500',
                'notas' => 'nullable|string',
                'categoria_gasto_id' => 'nullable|exists:categoria_gastos,id',
                'proyecto_id' => 'nullable|exists:proyectos,id',
                'user_id' => 'nullable|exists:users,id',
                'comprobante' => 'nullable|file|image|max:10240'
            ]);

            DB::transaction(function () use ($gasto, $validated, $request, $isAdmin) {
                $updates = [];
                if (isset($validated['descripcion']) || isset($validated['notas'])) {
                    $desc = $validated['descripcion'] ?? explode("\n\n", $gasto->notas)[0];
                    $note = $validated['notas'] ?? (count(explode("\n\n", $gasto->notas)) > 1 ? explode("\n\n", $gasto->notas)[1] : '');
                    $updates['notas'] = $desc . ($note ? "\n\n" . $note : '');
                }
                
                if (isset($validated['categoria_gasto_id'])) $updates['categoria_gasto_id'] = $validated['categoria_gasto_id'];
                if (isset($validated['proyecto_id'])) $updates['proyecto_id'] = $validated['proyecto_id'];
                if ($isAdmin && isset($validated['user_id'])) $updates['user_id'] = $validated['user_id'];

                if ($request->hasFile('comprobante')) {
                    if ($gasto->comprobante) Storage::disk('public')->delete($gasto->comprobante);
                    $updates['comprobante'] = $request->file('comprobante')->store('comprobantes/gastos', 'public');
                }

                $gasto->update($updates);
            });

            return $this->success($gasto, 'Gasto actualizado');
        } catch (\Exception $e) {
            Log::error('GastoApiController@update: ' . $e->getMessage());
            return $this->serverError('Error al actualizar', $e);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);

            if ($id >= self::CAJA_CHICA_OFFSET) {
                $mov = \App\Models\CajaChica::findOrFail($id - self::CAJA_CHICA_OFFSET);
                if ($mov->user_id !== $user->id && !$isAdmin) return $this->forbidden();
                $mov->delete();
                return $this->success(null, 'Movimiento eliminado');
            }

            $gasto = Compra::findOrFail($id);
            if ($gasto->created_by !== $user->id && $gasto->user_id !== $user->id && !$isAdmin) return $this->forbidden();

            DB::transaction(function () use ($gasto) {
                \App\Models\CuentasPorPagar::where('compra_id', $gasto->id)->delete();
                \App\Models\CajaChica::where('compra_id', $gasto->id)->delete();
                
                // Si tiene comprobante, no lo borramos por auditoría (soft delete), pero si quisiéramos:
                // if ($gasto->comprobante) Storage::disk('public')->delete($gasto->comprobante);
                
                $gasto->update(['estado' => EstadoCompra::Cancelada->value]);
                $gasto->delete();
            });

            return $this->success(null, 'Gasto eliminado');
        } catch (\Exception $e) {
            return $this->serverError('Error al eliminar', $e);
        }
    }

    public function categories()
    {
        return $this->success(CategoriaGasto::activas()->orderBy('nombre')->get());
    }

    public function bankAccounts()
    {
        $user = Auth::user();
        $query = CuentaBancaria::activas();
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            $query->where('responsable_id', $user->id);
        }
        return $this->success($query->get());
    }

    public function proyectos()
    {
        $user = Auth::user();
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return $this->success(Proyecto::orderBy('nombre')->get(['id', 'nombre']));
        }
        return $this->success($user->joinedProjects()->orderBy('nombre')->get(['proyectos.id', 'nombre']));
    }

    public function tecnicos()
    {
        if (!Auth::user()->hasAnyRole(['admin', 'super-admin'])) {
            return $this->forbidden();
        }
        return $this->success(User::activos()->orderBy('name')->get(['id', 'name']));
    }

    /**
     * Permite a un administrador cambiar el responsable/usuario de un gasto.
     */
    public function changeUser(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user->hasAnyRole(['super-admin', 'admin'])) {
                return $this->error('No tienes permiso para realizar esta acción.', 403);
            }

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            if ($id >= self::CAJA_CHICA_OFFSET) {
                $mov = \App\Models\CajaChica::findOrFail($id - self::CAJA_CHICA_OFFSET);
                $mov->user_id = $validated['user_id'];
                $mov->save();
                return $this->success($mov->load('user'), 'Usuario responsable actualizado correctamente.');
            }

            $gasto = Compra::findOrFail($id);
            $gasto->user_id = $validated['user_id'];
            $gasto->save();

            // Sincronizar en Caja Chica si existe el movimiento asociado
            \App\Models\CajaChica::where('compra_id', $gasto->id)
                ->update(['user_id' => $validated['user_id']]);

            return $this->success($gasto->load('user'), 'Usuario responsable actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error en GastoApiController@changeUser: ' . $e->getMessage());
            return $this->serverError('Error al cambiar el usuario del gasto', $e);
        }
    }
}
