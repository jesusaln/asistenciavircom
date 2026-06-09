<?php

namespace App\Http\Controllers;

use App\Models\CajaSesion;
use App\Models\Venta;
use App\Models\CajaChica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CajaSesionController extends Controller
{


    /**
     * Check if the current user has an open session for the given warehouse
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        $almacenId = $request->input('almacen_id') ?? $user->almacen_venta_id;

        if (!$almacenId) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no definido para el usuario'], 400);
        }

        $session = CajaSesion::where('user_id', $user->id)
            ->where('almacen_id', $almacenId)
            ->where('estado', 'abierta')
            ->latest()
            ->first();

        return response()->json([
            'status' => $session ? 'abierta' : 'cerrada',
            'session' => $session
        ]);
    }

    /**
     * Open a new cash register session
     */
    public function open(Request $request)
    {
        $request->validate([
            'monto_inicial' => 'required|numeric|min:0',
            'almacen_id' => 'required|integer'
        ]);

        $user = Auth::user();

        // Check if already open
        $existing = CajaSesion::where('user_id', $user->id)
            ->where('almacen_id', $request->almacen_id)
            ->where('estado', 'abierta')
            ->first();

        if ($existing) {
            return response()->json(['status' => 'error', 'message' => 'Ya existe una caja abierta'], 400);
        }

        $session = CajaSesion::create([
            'user_id' => $user->id,
            'empresa_id' => $user->empresa_id, // BelongsToEmpresa trait handles scope but we set explicit
            'almacen_id' => $request->almacen_id,
            'monto_inicial' => $request->monto_inicial,
            'fecha_apertura' => now(),
            'estado' => 'abierta'
        ]);

        return response()->json(['status' => 'success', 'session' => $session]);
    }

    /**
     * Get details for closing (calculated totals)
     */
    public function closingDetails(Request $request)
    {
        $user = Auth::user();
        $almacenId = $request->input('almacen_id');

        $session = CajaSesion::where('user_id', $user->id)
            ->where('almacen_id', $almacenId)
            ->where('estado', 'abierta')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No hay caja abierta'], 404);
        }

        // Calculate totals since opening
        // 1. Sales in Cash (Efectivo or efectivo)
        $ventasEfectivo = Venta::where('created_by', $user->id)
            ->where('almacen_id', $almacenId)
            ->where('created_at', '>=', $session->fecha_apertura)
            ->where(function ($q) {
                $q->where('metodo_pago', 'Efectivo')
                    ->orWhere('metodo_pago', 'efectivo');
            })
            ->where('estado', '!=', 'cancelada')
            ->sum('total');

        // 2. Caja Chica (Ingresos/Egresos)
        // Linking by user and time
        $ingresos = CajaChica::where('user_id', $user->id)
            ->where('created_at', '>=', $session->fecha_apertura)
            ->where('tipo', 'ingreso')
            ->sum('monto');

        $egresos = CajaChica::where('user_id', $user->id)
            ->where('created_at', '>=', $session->fecha_apertura)
            ->where('tipo', 'egreso')
            ->sum('monto');

        $totalSistema = $session->monto_inicial + $ventasEfectivo + $ingresos - $egresos;

        // PERMISSION CHECK: Only authorized users can see the system totals
        // Assuming 'ver_corte_caja_completo' permission or 'admin' role
        $canSeeDetails = $user->hasRole('admin') || $user->hasRole('super-admin') || $user->can('ver_corte_caja_completo');

        if (!$canSeeDetails) {
            return response()->json([
                'monto_inicial' => (float) $session->monto_inicial, // Knowing the starting fund is usually fine/necessary
                'ventas_efectivo' => null,
                'ingresos' => null,
                'egresos' => null,
                'total_sistema' => null // BLIND COUNT MODE
            ]);
        }

        return response()->json([
            'monto_inicial' => (float) $session->monto_inicial,
            'ventas_efectivo' => (float) $ventasEfectivo,
            'ingresos' => (float) $ingresos,
            'egresos' => (float) $egresos,
            'total_sistema' => (float) $totalSistema
        ]);
    }

    /**
     * Close the session
     */
    public function close(Request $request)
    {
        $request->validate([
            'almacen_id' => 'required|integer',
            'detalles_cierre' => 'required|array', // { "500": 2, ... }
            'monto_declarado' => 'required|numeric',
        ]);

        $user = Auth::user();

        $session = CajaSesion::where('user_id', $user->id)
            ->where('almacen_id', $request->almacen_id)
            ->where('estado', 'abierta')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No hay caja abierta'], 404);
        }

        // Recalculate totals to be sure
        $ventasEfectivo = Venta::where('created_by', $user->id)
            ->where('almacen_id', $request->almacen_id)
            ->where('created_at', '>=', $session->fecha_apertura)
            ->where(function ($q) {
                $q->where('metodo_pago', 'Efectivo')
                    ->orWhere('metodo_pago', 'efectivo');
            })
            ->where('estado', '!=', 'cancelada')
            ->sum('total');

        $ingresos = CajaChica::where('user_id', $user->id)
            ->where('created_at', '>=', $session->fecha_apertura)
            ->where('tipo', 'ingreso')
            ->sum('monto');

        $egresos = CajaChica::where('user_id', $user->id)
            ->where('created_at', '>=', $session->fecha_apertura)
            ->where('tipo', 'egreso')
            ->sum('monto');

        $totalSistema = $session->monto_inicial + $ventasEfectivo + $ingresos - $egresos;
        $diferencia = $request->monto_declarado - $totalSistema;

        // CHECK: If Blind Count (No Permission) & Shortage & Not Forced -> Warn User
        $canSeeDetails = $user->hasRole('admin') || $user->hasRole('super-admin') || $user->can('ver_corte_caja_completo');

        // If user cannot see details (Blind Count) AND has a shortage AND hasn't confirmed yet
        if (!$canSeeDetails && $diferencia < -0.01 && !$request->boolean('force')) {
            return response()->json([
                'status' => 'confirmation_required',
                'message' => '⚠️ Advertencia: Se detectó un FALTANTE de efectivo en el corte.',
                'shortage_amount' => abs($diferencia) // Reveal amount so they can recount
            ]);
        }

        // Surplus (>= 0) is ignored/allowed silently for blind counts as requested ("si le sobra que no le diga")

        $session->update([
            'total_ventas_efectivo' => $ventasEfectivo,
            'total_entradas' => $ingresos,
            'total_salidas' => $egresos,
            'monto_final_sistema' => $totalSistema,
            'monto_declarado' => $request->monto_declarado,
            'diferencia' => $diferencia,
            'fecha_cierre' => now(),
            'estado' => 'cerrada',
            'detalles_cierre' => $request->detalles_cierre,
            'notas' => $request->notas
        ]);

        return response()->json(['status' => 'success', 'session' => $session]);
    }
}
