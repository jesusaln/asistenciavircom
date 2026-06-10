<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ComisionCalculatorService;
use App\Models\PagoComision;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ComisionApiController extends Controller
{
    protected ComisionCalculatorService $calculatorService;

    public function __construct(ComisionCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    /**
     * Resumen de comisiones para el técnico/vendedor actual
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $periodo = $request->get('periodo', 'semana');

        if ($periodo === 'mes') {
            $fechaInicio = Carbon::now()->startOfMonth();
            $fechaFin = Carbon::now()->endOfMonth();
        } else {
            // Semana actual
            $fechaInicio = Carbon::now()->startOfWeek();
            $fechaFin = Carbon::now()->endOfWeek();
        }

        $targetUserId = $user->id;
        if ($request->has('vendedor_id') && $user->hasAnyRole(['admin', 'super-admin'])) {
            $targetUserId = $request->get('vendedor_id');
        }

        $resumen = $this->calculatorService->calcularComisionesVendedor(
            \App\Models\User::class,
            $targetUserId,
            $fechaInicio,
            $fechaFin
        );

        // Historial de pagos para este usuario
        $pagos = PagoComision::where('vendedor_id', $targetUserId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'monto' => (float)$p->monto_pagado,
                    'fecha' => $p->fecha_pago->format('Y-m-d'),
                    'estado' => $p->estado,
                    'periodo' => Carbon::parse($p->periodo_inicio)->format('d/m') . ' - ' . Carbon::parse($p->periodo_fin)->format('d/m'),
                ];
            });

        return response()->json([
            'resumen' => [
                'total_ganado' => (float)$resumen['total_comision_bruto'],
                'total_pendiente' => (float)$resumen['total_comision'],
                'total_pagado' => (float)($resumen['total_comision_bruto'] - $resumen['total_comision']),
                'num_ventas' => $resumen['num_ventas'],
            ],
            'detalles' => $resumen['detalles'],
            'pagos' => $pagos,
            'filtros' => [
                'periodo' => $periodo,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_fin' => $fechaFin->format('Y-m-d'),
            ]
        ]);
    }

    /**
     * Detalle de una venta específica y su desglose de comisión
     */
    public function showVenta(Request $request, $ventaId)
    {
        $user = $request->user();
        $venta = \App\Models\Venta::findOrFail($ventaId);

        $targetUserId = $user->id;
        if ($request->has('vendedor_id') && $user->hasAnyRole(['admin', 'super-admin'])) {
            $targetUserId = $request->get('vendedor_id');
        }

        // Validar que la venta le pertenezca (como vendedor o técnico) o sea admin
        $esSuVenta = ($venta->vendedor_id == $targetUserId) || ($venta->cita && $venta->cita->tecnico_id == $targetUserId);
        
        if (!$esSuVenta && !$user->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'No tienes permiso para ver esta comisión'], 403);
        }

        $comision = $this->calculatorService->calcularComisionVentaParaUsuario($venta, $targetUserId);
        
        return response()->json([
            'venta' => [
                'id' => $venta->id,
                'numero' => $venta->numero_venta,
                'fecha' => $venta->fecha->format('Y-m-d'),
                'total' => (float)$venta->total,
            ],
            'comision' => $comision
        ]);
    }

    /**
     * Lista de vendedores con ventas para filtro de admin
     */
    public function vendedores(Request $request)
    {
        if (!$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $vendedores = $this->calculatorService->obtenerVendedoresConVentas();

        return response()->json($vendedores);
    }

    /**
     * Registrar un pago de comisión
     */
    public function registrarPago(Request $request)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

         $validated = $request->validate([
            'vendedor_id' => 'required|exists:users,id',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date',
            'monto_pagado' => 'required|numeric|min:0',
            'metodo_pago' => 'nullable|string',
            'referencia_pago' => 'nullable|string',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'notas' => 'nullable|string',
            'venta_ids' => 'nullable|array',
        ]);

        $data = array_merge($validated, [
            'vendedor_type' => \App\Models\User::class,
        ]);

        try {
            $pago = $this->calculatorService->crearPagoComision($data);
            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'pago' => $pago
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ], 500);
        }
    }
}
