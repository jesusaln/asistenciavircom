<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CajaChica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ImageOptimizerTrait;

class CajaChicaApiController extends Controller
{
    use ImageOptimizerTrait;

    public function index(Request $request)
    {
        $user = Auth::user();

        // Obtener historial del usuario logueado
        $movimientos = CajaChica::where('user_id', $user->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $totalIngresos = CajaChica::where('user_id', $user->id)
            ->where('tipo', 'ingreso')
            ->sum('monto');

        $totalEgresos = CajaChica::where('user_id', $user->id)
            ->where('tipo', 'egreso')
            ->sum('monto');

        $balance = $totalIngresos - $totalEgresos;

        return response()->json([
            'movimientos' => $movimientos,
            'balance' => $balance,
            'limite_gasto' => 1900 // Enviamos el límite a la app para validación visual
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'concepto' => 'required|string|max:255',
            'monto' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->metodo_pago === 'caja_chica' && $value > 1900) {
                        $fail('Por políticas de la empresa, los gastos mayores a $1,900 deben pagarse con Tarjeta o Transferencia, no con Caja Chica.');
                    }
                },
            ],
            'tipo' => 'required|in:ingreso,egreso',
            'fecha' => 'required|date',
            'nota' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'comprobante' => 'nullable|file|image|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('comprobante')) {
            $path = $this->saveImageAsWebP($request->file('comprobante'), 'caja-chica');
        }

        $caja = \App\Models\CajaChica::create([
            'concepto' => $validated['concepto'],
            'categoria' => $validated['categoria'] ?? null,
            'monto' => $validated['monto'],
            'tipo' => $validated['tipo'],
            'fecha' => $validated['fecha'],
            'nota' => $validated['nota'] ?? null,
            'comprobante_path' => $path,
            'user_id' => ($request->user()->hasAnyRole(['admin', 'super-admin']) && !empty($validated['user_id'])) 
                ? $validated['user_id'] 
                : $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $caja->load('user'),
            'message' => 'Movimiento registrado correctamente'
        ]);
    }

    /**
     * Listar usuarios para el administrador
     */
    public function users(Request $request)
    {
        if (!$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $users = User::activos()
            ->where(function($q) {
                $q->where('es_tecnico', true)
                  ->orWhere('es_empleado', true);
            })
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($users);
    }
}
