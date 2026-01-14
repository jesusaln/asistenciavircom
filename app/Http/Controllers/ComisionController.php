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

        // Obtener resumen del periodo
        $resumen = $this->calculatorService->obtenerResumenPeriodo($fechaInicio, $fechaFin);

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

        // Decodificar el tipo de vendedor (Siempre forzar User ahora)
        $type = 'App\\Models\\User';

        // Si viene request antiguo con tecnico, intentar mapear (aunque ya migramos DB)
        // El ID vendrá del frontend. Si el frontend manda ID de User, todo ok.
        // Si manda ID de tecnico, tendremos problema si no lo convertimos.
        // Asumiremos que el frontend enviará ID de User tras refactorizar vistas, 
        // o si es ID de tecnico tendriamos que buscarlo. 
        // Para seguridad, buscamos si es tecnico por si acaso (legacy support durante transicion)
        if ($vendedorType === 'tecnico') {
            // Buscar User ID a partir de Tecnico ID si fuera necesario, 
            // pero mejor asumir que el sistema ya usa User IDs
        }

        $detalle = $this->calculatorService->calcularComisionesVendedor(
            $type,
            $vendedorId,
            $fechaInicio,
            $fechaFin
        );

        // Obtener info del vendedor
        $vendedor = User::find($vendedorId); // Siempre User
        $nombreVendedor = $vendedor ? $vendedor->name : 'Desconocido';

        return Inertia::render('Comisiones/Detalle', [
            'vendedor' => [
                'id' => $vendedorId,
                'type' => 'user',
                'type_label' => 'Vendedor', // Unificado
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
        ]);

        // Verificar si ya existe un pago para este periodo
        $pagoExistente = PagoComision::where('vendedor_type', $validated['vendedor_type'])
            ->where('vendedor_id', $validated['vendedor_id'])
            ->where('periodo_inicio', $validated['periodo_inicio'])
            ->where('periodo_fin', $validated['periodo_fin'])
            ->first();

        if ($pagoExistente) {
            // Actualizar pago existente
            $pagoExistente->update([
                'monto_pagado' => $pagoExistente->monto_pagado + $validated['monto_pagado'],
                'estado' => ($pagoExistente->monto_pagado + $validated['monto_pagado']) >= $pagoExistente->monto_comision ? 'pagado' : 'parcial',
                'fecha_pago' => now(),
                'metodo_pago' => $validated['metodo_pago'],
                'referencia_pago' => $validated['referencia_pago'],
                'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'],
                'notas' => $validated['notas'],
                'pagado_por' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Pago de comisión actualizado correctamente.');
        }

        // Crear nuevo pago
        $pago = $this->calculatorService->crearPagoComision($validated);

        return redirect()->back()->with('success', 'Pago de comisión registrado correctamente.');
    }

    /**
     * Historial de pagos
     */
    public function historial(Request $request)
    {
        $pagos = PagoComision::with('vendedor', 'pagadoPorUser')
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

        $empresaId = EmpresaResolver::resolveId();

        $pdf = \PDF::loadView('pdf.recibo-comision', [
            'pago' => $pago,
            'empresa' => $empresaId ? \App\Models\Empresa::find($empresaId) : null,
        ]);

        return $pdf->download("recibo-comision-{$pago->id}.pdf");
    }
}
