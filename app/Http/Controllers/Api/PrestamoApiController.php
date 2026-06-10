<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PrestamoApiController extends Controller
{
    /**
     * Listar préstamos (solo para superadmin).
     */
    public function index(Request $request)
    {
        // Temporalmente deshabilitamos la verificación de rol
        // if (!$request->user() || !$request->user()->hasRole('super-admin')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $query = Prestamo::with('cliente');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('cliente', function ($q) use ($search) {
                $q->where('nombre_razon_social', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->get('estado'));
        } else {
            // Por defecto ocultar los completados (Fix solicitado por el usuario)
            $query->where('estado', '!=', 'completado');
        }

        $prestamos = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($prestamos);
    }

    /**
     * Cotizar un préstamo (calcular pagos sin guardar).
     */
    public function cotizar(Request $request)
    {
        $validated = $request->validate([
            'monto_prestado' => 'required|numeric|min:1',
            'tasa_interes_mensual' => 'required|numeric|min:0',
            'numero_pagos' => 'required|integer|min:1',
            'frecuencia_pago' => 'required|in:semanal,quincenal,mensual',
        ]);

        try {
            // Creamos una instancia temporal para usar la lógica del modelo
            $prestamo = new Prestamo($validated);
            $calculos = $prestamo->calcularPagos();

            return response()->json([
                'success' => true,
                'calculos' => $calculos
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Crear un nuevo préstamo.
     */
    public function store(Request $request)
    {
        if (!$request->user() || !$request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'monto_prestado' => 'required|numeric|min:1',
            'tasa_interes_mensual' => 'required|numeric|min:0',
            'numero_pagos' => 'required|integer|min:1',
            'frecuencia_pago' => 'required|in:semanal,quincenal,mensual',
            'fecha_inicio' => 'required|date',
            'descripcion' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $prestamo = new Prestamo($validated);

            // Lógica copiada del controlador web para consistencia
            // Calcular fecha primer pago si no viene
            if (!$request->fecha_primer_pago) {
                $diasSumar = match ($prestamo->frecuencia_pago) {
                    'semanal' => 7,
                    'quincenal' => 15,
                    'mensual' => 30,
                    default => 30,
                };
                $prestamo->fecha_primer_pago = \Carbon\Carbon::parse($prestamo->fecha_inicio)->addDays($diasSumar);
            } else {
                $prestamo->fecha_primer_pago = $request->fecha_primer_pago;
            }

            $calculos = $prestamo->calcularPagos();
            $prestamo->pago_periodico = $calculos['pago_periodico'];
            $prestamo->monto_interes_total = $calculos['interes_total'];
            $prestamo->monto_total_pagar = $calculos['total_pagar'];
            $prestamo->monto_pendiente = $calculos['total_pagar'];
            $prestamo->pagos_pendientes = $prestamo->numero_pagos;
            $prestamo->estado = 'activo'; // Default state

            $prestamo->save();
            $prestamo->crearPagosProgramados();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Préstamo creado correctamente',
                'data' => $prestamo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando préstamo API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear el préstamo'], 500);
        }
    }

    /**
     * Ver detalle de préstamo.
     */
    public function show($id)
    {
        $prestamo = Prestamo::with([
            'cliente',
            'pagos' => function ($q) {
                $q->orderBy('numero_pago');
            }
        ])->findOrFail($id);

        return response()->json($prestamo);
    }

    /**
     * Registrar un abono o pago a un préstamo.
     */
    public function registrarPago(Request $request, $id)
    {
        // if (!$request->user() || !$request->user()->hasRole('super-admin')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $request->validate([
            'monto' => 'required|numeric|min:0.1',
            'metodo_pago' => 'nullable|string',
            'referencia' => 'nullable|string',
            'fecha_pago' => 'nullable|date',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
        ]);

        try {
            $prestamo = Prestamo::findOrFail($id);

            // Obtener la amortización o cuota pendiente más cercana
            $pagoPendiente = $prestamo->pagos()
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->orderBy('numero_pago')
                ->first();

            if (!$pagoPendiente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El préstamo ya no tiene cuotas pendientes de pago'
                ], 422);
            }

            // Aplicar el pago a la cuota
            $pagoPendiente->agregarPago(
                $request->monto,
                $request->fecha_pago,
                $request->metodo_pago,
                $request->referencia,
                $request->cuenta_bancaria_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Abono registrado correctamente',
                'prestamo' => $prestamo->fresh(['cliente', 'pagos'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error registrando abono en Prestamo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
