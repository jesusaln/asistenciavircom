<?php

namespace App\Http\Controllers;

use App\Models\PagoComision;
use App\Models\CuentaBancaria;
use App\Models\User;
use App\Services\ComisionCalculatorService;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ComisionController extends Controller
{
    protected ComisionCalculatorService $calculatorService;

    public function __construct(ComisionCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    /**
     * Dashboard de comisiones
     */
    public function index(Request $request)
    {
        // Determinar periodo (por defecto: semana actual)
        $periodo = $request->get('periodo', 'semana');

        if ($periodo === 'mes') {
            $fechaInicio = Carbon::now()->startOfMonth();
            $fechaFin = Carbon::now()->endOfMonth();
        } elseif ($periodo === 'custom' && $request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $fechaInicio = Carbon::parse($request->get('fecha_inicio'));
            $fechaFin = Carbon::parse($request->get('fecha_fin'));
        } else {
            // Semana actual (lunes a domingo)
            $fechaInicio = Carbon::now()->startOfWeek();
            $fechaFin = Carbon::now()->endOfWeek();
        }

        // Si el usuario no tiene permiso general para ver todas las comisiones,
        // solo puede ver las suyas propias.
        if (!auth()->user()->can('view comisiones')) {
            $resumen = $this->calculatorService->obtenerResumenPeriodo($fechaInicio, $fechaFin, auth()->id());
        } else {
            $resumen = $this->calculatorService->obtenerResumenPeriodo($fechaInicio, $fechaFin);
        }

        // Historial de pagos recientes
        $pagosRecientes = PagoComision::with('vendedor')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($pago) {
                return [
                    'id' => $pago->id,
                    'vendedor' => $pago->nombre_vendedor,
                    'periodo' => Carbon::parse($pago->periodo_inicio)->format('d M') . ' - ' . Carbon::parse($pago->periodo_fin)->format('d M'),
                    'monto' => $pago->monto_comision,
                    'pagado' => $pago->monto_pagado,
                    'estado' => $pago->estado,
                    'fecha_pago' => $pago->fecha_pago?->format('d/m/Y'),
                ];
            });

        // Cuentas bancarias para el modal de pago
        $cuentasBancarias = CuentaBancaria::where('activa', true)->get(['id', 'nombre', 'banco']);

        return Inertia::render('Comisiones/Index', [
            'resumen' => $resumen,
            'pagosRecientes' => $pagosRecientes,
            'cuentasBancarias' => $cuentasBancarias,
            'filtros' => [
                'periodo' => $periodo,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_fin' => $fechaFin->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Detalle de comisiones de un vendedor
     */
    public function show(Request $request, string $vendedorType, int $vendedorId)
    {
        $periodo = $request->get('periodo', 'semana');

        if ($periodo === 'mes') {
            $fechaInicio = Carbon::now()->startOfMonth();
            $fechaFin = Carbon::now()->endOfMonth();
        } else {
            $fechaInicio = Carbon::now()->startOfWeek();
            $fechaFin = Carbon::now()->endOfWeek();
        }

        // Siempre User — normalizado
        $type = User::class;

        // Seguridad: Si no es admin y quiere ver a otro vendedor, 403
        if (!auth()->user()->can('view comisiones') && auth()->id() != $vendedorId) {
            abort(403, 'No tienes permiso para ver las comisiones de otro vendedor.');
        }

        $detalle = $this->calculatorService->calcularComisionesVendedor(
            $type,
            $vendedorId,
            $fechaInicio,
            $fechaFin
        );

        // Obtener info del vendedor
        $vendedor = User::find($vendedorId);
        $nombreVendedor = $vendedor ? $vendedor->name : 'Desconocido';

        return Inertia::render('Comisiones/Detalle', [
            'vendedor' => [
                'id' => $vendedorId,
                'type' => 'user',
                'type_label' => $vendedor && $vendedor->es_tecnico ? 'Técnico' : 'Vendedor',
                'nombre' => $nombreVendedor,
            ],
            'detalle' => $detalle,
            'filtros' => [
                'periodo' => $periodo,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_fin' => $fechaFin->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Registrar pago de comisión
     */
    public function pagar(Request $request)
    {
        if (!auth()->user()->can('view comisiones')) {
            abort(403);
        }

        $validated = $request->validate([
            'vendedor_type' => 'required|string',
            'vendedor_id' => 'required|integer',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date',
            'monto_pagado' => 'required|numeric|min:0',
            'metodo_pago' => 'required|string',
            'referencia_pago' => 'nullable|string',
            'cuenta_bancaria_id' => 'nullable|integer|exists:cuentas_bancarias,id',
            'notas' => 'nullable|string',
            'venta_ids' => 'nullable|array',
            'venta_ids.*' => 'integer|exists:ventas,id',
        ]);

        // Normalizar el tipo de vendedor para el servicio
        $validated['vendedor_type'] = \App\Models\User::class;
        $validated['empresa_id'] = EmpresaResolver::resolveId();

        // Crear nuevo pago selectivo
        $pago = $this->calculatorService->crearPagoComision($validated);

        return redirect()->back()->with('success', 'Pago de comisión registrado correctamente.');
    }

    /**
     * Historial de pagos
     */
    public function historial(Request $request)
    {
        $pagos = PagoComision::with('vendedor', 'pagadoPorUser')
            ->when(!auth()->user()->can('view comisiones'), function ($query) {
                $query->where('vendedor_id', auth()->id());
            })
            ->when($request->get('estado'), function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Comisiones/Historial', [
            'pagos' => $pagos,
            'filtros' => [
                'estado' => $request->get('estado'),
            ],
        ]);
    }

    /**
     * Generar recibo PDF
     */
    public function recibo(PagoComision $pago)
    {
        $pago->load('vendedor', 'pagadoPorUser', 'cuentaBancaria');

        // Seguridad: Solo admin o el propio vendedor pueden ver el recibo
        if (!auth()->user()->can('view comisiones') && auth()->id() != $pago->vendedor_id) {
            abort(403);
        }

        $empresa = \App\Models\EmpresaConfiguracion::getInfoEmpresa();

        $pdf = \PDF::loadView('pdf.recibo-comision', [
            'pago' => $pago,
            'empresa' => $empresa,
        ]);

        return $pdf->stream("recibo-comision-{$pago->id}.pdf");
    }
}
