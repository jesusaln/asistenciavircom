<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CuentasPorCobrar;
use App\Models\CuentaBancaria;
use App\Models\Renta;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CobranzaApiController extends Controller
{
    /**
     * Obtener cuentas por cobrar pendientes del mes actual.
     */
    public function proximas(Request $request)
    {
        $incluirVencidas = $request->boolean('incluir_vencidas', true);
        $tipo = $request->get('tipo'); // 'renta' o 'venta' o null para ambos

        $hoy = Carbon::today();
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        $query = CuentasPorCobrar::with(['cobrable.cliente'])
            ->where('estado', '!=', 'pagado');

        // Filtrar por tipo si se especifica
        if ($tipo === 'renta') {
            $query->where('cobrable_type', Renta::class);
        } elseif ($tipo === 'venta') {
            $query->where('cobrable_type', Venta::class);
        }

        // Aplicar filtro de fechas - todo el mes actual
        if ($incluirVencidas) {
            // Vencidas (anteriores a hoy) + pendientes del mes
            $query->where(function ($q) use ($hoy, $finMes) {
                $q->where('fecha_vencimiento', '<', $hoy) // Vencidas
                    ->orWhereBetween('fecha_vencimiento', [$hoy, $finMes]); // Del mes
            });
        } else {
            // Solo las del mes actual (no vencidas)
            $query->whereBetween('fecha_vencimiento', [$hoy, $finMes]);
        }

        $cuentas = $query->orderBy('fecha_vencimiento', 'asc')
            ->get()
            ->map(function ($cuenta) use ($hoy) {
                $fechaVenc = Carbon::parse($cuenta->fecha_vencimiento);
                $diasRestantes = $hoy->diffInDays($fechaVenc, false);

                // Determinar tipo de cobrable
                $tipo = str_contains($cuenta->cobrable_type, 'Renta') ? 'renta' : 'venta';
                $identificador = $tipo === 'renta'
                    ? $cuenta->cobrable->numero_contrato ?? "R-{$cuenta->cobrable_id}"
                    : $cuenta->cobrable->numero_venta ?? "V-{$cuenta->cobrable_id}";

                return [
                    'id' => $cuenta->id,
                    'tipo' => $tipo,
                    'identificador' => $identificador,
                    'cobrable_id' => $cuenta->cobrable_id,
                    'cliente' => [
                        'id' => $cuenta->cobrable->cliente->id ?? null,
                        'nombre' => $cuenta->cobrable->cliente->nombre_razon_social ?? 'Sin cliente',
                        'telefono' => $cuenta->cobrable->cliente->telefono ?? null,
                        'email' => $cuenta->cobrable->cliente->email ?? null,
                    ],
                    'concepto' => $cuenta->notas ?? 'Pago',
                    'monto_total' => (float) $cuenta->monto_total,
                    'monto_pagado' => (float) $cuenta->monto_pagado,
                    'monto_pendiente' => (float) $cuenta->monto_pendiente,
                    'fecha_vencimiento' => $cuenta->fecha_vencimiento,
                    'dias_restantes' => $diasRestantes,
                    'estado' => $cuenta->estado,
                    'vencido' => $diasRestantes < 0,
                    'es_hoy' => $diasRestantes === 0,
                    'urgente' => $diasRestantes <= 1 && $diasRestantes >= 0,
                ];
            });

        // Estadísticas
        $totalPendiente = $cuentas->sum('monto_pendiente');
        $totalVencido = $cuentas->where('vencido', true)->sum('monto_pendiente');
        $countVencidas = $cuentas->where('vencido', true)->count();
        $countProximas = $cuentas->where('vencido', false)->count();

        return response()->json([
            'success' => true,
            'data' => $cuentas->values(),
            'stats' => [
                'total_cuentas' => $cuentas->count(),
                'vencidas' => $countVencidas,
                'proximas' => $countProximas,
                'monto_pendiente' => $totalPendiente,
                'monto_vencido' => $totalVencido,
            ],
        ]);
    }

    /**
     * Obtener cobranzas del día de hoy (para notificaciones).
     */
    public function hoy()
    {
        $hoy = Carbon::today();

        // Cuentas que vencen hoy + vencidas (para recordatorio)
        $cuentasHoy = CuentasPorCobrar::with(['cobrable.cliente'])
            ->where('estado', '!=', 'pagado')
            ->whereDate('fecha_vencimiento', '<=', $hoy)
            ->get();

        $soloHoy = $cuentasHoy->filter(function ($c) use ($hoy) {
            return Carbon::parse($c->fecha_vencimiento)->isSameDay($hoy);
        });

        $vencidas = $cuentasHoy->filter(function ($c) use ($hoy) {
            return Carbon::parse($c->fecha_vencimiento)->lt($hoy);
        });

        return response()->json([
            'success' => true,
            'counts' => [
                'hoy' => $soloHoy->count(),
                'vencidas' => $vencidas->count(),
                'total_urgente' => $cuentasHoy->count(),
            ],
            'montos' => [
                'hoy' => $soloHoy->sum('monto_pendiente'),
                'vencidas' => $vencidas->sum('monto_pendiente'),
                'total_urgente' => $cuentasHoy->sum('monto_pendiente'),
            ],
        ]);
    }

    /**
     * Obtener cuentas bancarias activas para selector.
     */
    public function cuentasBancarias()
    {
        $cuentas = CuentaBancaria::activas()
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'banco', 'numero_cuenta', 'saldo_actual']);

        return response()->json([
            'success' => true,
            'data' => $cuentas,
        ]);
    }

    /**
     * Registrar pago de una cuenta por cobrar.
     */
    public function registrarPago(Request $request, $id)
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'notas' => 'nullable|string|max:1000',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
        ]);

        try {
            DB::beginTransaction();

            $cuenta = CuentasPorCobrar::with('cobrable.cliente')
                ->lockForUpdate()
                ->findOrFail($id);

            // Validar que el monto no exceda el pendiente
            $pendiente = $cuenta->calcularPendiente();
            if ($validated['monto'] > $pendiente) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto excede el pendiente ($pendiente)",
                ], 422);
            }

            // Registrar pago usando el servicio unificado
            $metodoPago = $request->input('metodo_pago', (!empty($validated['cuenta_bancaria_id']) ? 'transferencia' : 'efectivo'));

            app(\App\Services\PaymentService::class)->registrarPago(
                $cuenta,
                $validated['monto'],
                $metodoPago,
                $validated['notas'] ?? null,
                auth()->id(),
                $validated['cuenta_bancaria_id'] ?? null
            );

            $cuenta->refresh();

            // Actualizar relación con el cobrable para metadata específica
            if ($cuenta->cobrable) {
                if ($cuenta->cobrable instanceof Renta && $cuenta->estado === 'pagado') {
                    $cuenta->cobrable->update(['ultimo_pago' => now()]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'data' => [
                    'id' => $cuenta->id,
                    'monto_pagado' => (float) $cuenta->monto_pagado,
                    'monto_pendiente' => (float) $cuenta->monto_pendiente,
                    'estado' => $cuenta->estado,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando pago móvil', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener detalle de una cuenta por cobrar.
     */
    public function show($id)
    {
        $cuenta = CuentasPorCobrar::with(['cobrable.cliente', 'cobrable.equipos'])
            ->findOrFail($id);

        $hoy = Carbon::today();
        $fechaVenc = Carbon::parse($cuenta->fecha_vencimiento);
        $diasRestantes = $hoy->diffInDays($fechaVenc, false);

        $tipo = str_contains($cuenta->cobrable_type, 'Renta') ? 'renta' : 'venta';

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cuenta->id,
                'tipo' => $tipo,
                'cobrable' => $cuenta->cobrable,
                'cliente' => $cuenta->cobrable->cliente,
                'concepto' => $cuenta->notas,
                'monto_total' => (float) $cuenta->monto_total,
                'monto_pagado' => (float) $cuenta->monto_pagado,
                'monto_pendiente' => (float) $cuenta->monto_pendiente,
                'fecha_vencimiento' => $cuenta->fecha_vencimiento,
                'dias_restantes' => $diasRestantes,
                'estado' => $cuenta->estado,
                'vencido' => $diasRestantes < 0,
            ],
        ]);
    }
}
