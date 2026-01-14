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
        // El middleware role:super-admin debe estar en la ruta, pero validamos aquí también por seguridad
        if (!$request->user() || !$request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
}
