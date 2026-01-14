<?php

namespace App\Http\Controllers;

use App\Models\RegistroVacaciones;
use App\Models\AjusteVacaciones;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RegistroVacacionesController extends Controller
{
    // Nota: El middleware de autorización está definido en routes/web.php
    // - 'role:admin' en las rutas export, ajustar e index (via resource)

    /**
     * Listado general de registro de vacaciones por empleado/año
     */
    public function index(Request $request)
    {
        $anio = (int) ($request->get('anio', now()->year));
        $search = trim((string) $request->get('search', ''));
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $validSort = ['updated_at', 'anio', 'dias_correspondientes', 'dias_disponibles', 'dias_utilizados', 'dias_pendientes'];
        if (!in_array($sortBy, $validSort)) {
            $sortBy = 'updated_at';
        }

        $query = RegistroVacaciones::with(['empleado:id,name,departamento,puesto'])
            ->where('anio', $anio);

        if ($search !== '') {
            $query->whereHas('empleado', function ($q) use ($search) {
                // Cambiado 'ilike' (PostgreSQL) a 'like' (MySQL compatible)
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('departamento', 'like', "%{$search}%")
                    ->orWhere('puesto', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        $registros = $query->paginate(20)->appends($request->query());

        return Inertia::render('Vacaciones/RegistroIndex', [
            'anio' => $anio,
            'search' => $search,
            'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            'registros' => $registros,
        ]);
    }

    /**
     * Exportar CSV del listado filtrado
     */
    public function export(Request $request)
    {
        $anio = (int) ($request->get('anio', now()->year));
        $search = trim((string) $request->get('search', ''));

        $query = RegistroVacaciones::with(['empleado:id,name,departamento,puesto'])
            ->where('anio', $anio);

        if ($search !== '') {
            $query->whereHas('empleado', function ($q) use ($search) {
                // Cambiado 'ilike' (PostgreSQL) a 'like' (MySQL compatible)
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('departamento', 'like', "%{$search}%")
                    ->orWhere('puesto', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderBy('updated_at', 'desc')->get();

        $filename = "registro_vacaciones_{$anio}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Empleado', 'Departamento', 'Puesto', 'Año', 'Correspondientes', 'Disponibles', 'Utilizados', 'Pendientes', 'Actualizado']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    optional($r->empleado)->name,
                    optional($r->empleado)->departamento,
                    optional($r->empleado)->puesto,
                    $r->anio,
                    $r->dias_correspondientes,
                    $r->dias_disponibles,
                    $r->dias_utilizados,
                    $r->dias_pendientes,
                    optional($r->updated_at)->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
    public function ajustar(Request $request, int $empleadoId)
    {
        $validated = $request->validate([
            'dias' => 'required|integer|between:-365,365',
            'anio' => 'nullable|integer|min:2000|max:2100',
            'motivo' => 'nullable|string|max:500',
        ]);

        $empleado = User::findOrFail($empleadoId);
        if (!$empleado->es_empleado) {
            return response()->json(['success' => false, 'message' => 'El usuario no es empleado'], 422);
        }

        $anio = $validated['anio'] ?? now()->year;

        $registro = RegistroVacaciones::actualizarRegistroAnual($empleado->id, $anio);
        if (!$registro) {
            return response()->json(['success' => false, 'message' => 'No se pudo obtener/crear el registro anual de vacaciones'], 422);
        }

        $dias = (int) $validated['dias'];
        // Aplicar ajuste (positivo o negativo). Si es negativo, detiene en cero.
        $ajustado = $registro->aplicarAjuste($dias, $validated['motivo'] ?? null, Auth::id());

        Log::info('Ajuste de vacaciones aplicado', [
            'empleado_id' => $empleado->id,
            'anio' => $anio,
            'dias' => $dias,
            'admin_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajuste aplicado correctamente',
            'registro' => $ajustado,
        ]);
    }

    public function porEmpleado(Request $request, User $empleado)
    {
        // Error #4 Fix: Verificar autorización - empleados solo ven sus propios registros
        $user = Auth::user();
        if (!$user->hasAnyRole(['admin', 'super-admin']) && $user->id !== $empleado->id) {
            abort(403, 'No tienes permiso para ver el registro de este empleado.');
        }

        if (!$empleado->es_empleado) {
            return redirect()->back()->with('error', 'El usuario no es empleado');
        }

        $anio = (int) ($request->get('anio', now()->year));
        $registro = RegistroVacaciones::actualizarRegistroAnual($empleado->id, $anio);

        // Obtener las vacaciones del empleado para el año seleccionado
        $vacaciones = \App\Models\Vacacion::where('user_id', $empleado->id)
            ->where(function ($query) use ($anio) {
                $query->whereYear('fecha_inicio', $anio)
                    ->orWhereYear('fecha_fin', $anio);
            })
            ->orderBy('fecha_inicio', 'desc')
            ->get(['id', 'fecha_inicio', 'fecha_fin', 'dias_solicitados', 'estado', 'motivo', 'created_at']);

        // Ajustes del año seleccionado y del año anterior (vista compacta)
        $ajustes = AjusteVacaciones::with(['creador:id,name'])
            ->where('user_id', $empleado->id)
            ->whereIn('anio', [$anio, $anio - 1])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'user_id', 'anio', 'dias', 'motivo', 'creado_por', 'created_at']);

        return Inertia::render('Vacaciones/RegistroEmpleado', [
            'empleado' => $empleado->only(['id', 'name', 'puesto', 'departamento', 'fecha_contratacion']),
            'anio' => $anio,
            'registro' => $registro,
            'ajustes' => $ajustes,
            'vacaciones' => $vacaciones,
        ]);
    }
}
