<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmProspecto;
use App\Models\CrmActividad;
use App\Models\CrmTarea;
use App\Models\CrmScript;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    /**
     * Dashboard y Lista de Prospectos
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);
        
        $query = CrmProspecto::with(['vendedor:id,name', 'cliente:id,nombre_razon_social'])
            ->when(!$isAdmin, fn($q) => $q->where('vendedor_id', $user->id))
            ->when($request->etapa, fn($q, $e) => $q->where('etapa', $e))
            ->when($request->search, fn($q, $s) => $q->where(function($qq) use ($s) {
                $qq->where('nombre', 'ilike', "%{$s}%")
                   ->orWhere('telefono', 'ilike', "%{$s}%")
                   ->orWhere('empresa', 'ilike', "%{$s}%");
            }))
            ->orderByDesc('updated_at');

        return response()->json($query->paginate(20));
    }

    /**
     * Detalle de Prospecto
     */
    public function show(CrmProspecto $prospecto)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['admin', 'super-admin']);

        if (!$isAdmin && $prospecto->vendedor_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $prospecto->load(['vendedor:id,name', 'cliente', 'actividades.usuario:id,name', 'tareas' => fn($q) => $q->pendientes()]);
        
        return response()->json($prospecto);
    }

    /**
     * Crear Prospecto
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'empresa' => 'nullable|string|max:255',
            'valor_estimado' => 'nullable|numeric|min:0',
            'prioridad' => 'nullable|in:alta,media,baja',
            'vendedor_id' => 'nullable|exists:users,id',
            'origen' => 'nullable|string',
            'notas' => 'nullable|string',
        ]);

        $validated['vendedor_id'] = $validated['vendedor_id'] ?? Auth::id();
        $validated['created_by'] = Auth::id();
        $validated['etapa'] = 'prospecto';

        $prospecto = CrmProspecto::create($validated);

        return response()->json($prospecto, 201);
    }

    /**
     * Actualizar Prospecto
     */
    public function update(Request $request, CrmProspecto $prospecto)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'etapa' => 'sometimes|in:' . implode(',', array_keys(CrmProspecto::ETAPAS)),
            'prioridad' => 'sometimes|in:alta,media,baja',
            'valor_estimado' => 'sometimes|numeric',
            'notas' => 'nullable|string',
        ]);

        $prospecto->update($validated);

        return response()->json($prospecto);
    }

    /**
     * Registrar Actividad
     */
    public function registrarActividad(Request $request, CrmProspecto $prospecto)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(CrmActividad::TIPOS)),
            'resultado' => 'nullable|in:' . implode(',', array_keys(CrmActividad::RESULTADOS)),
            'notas' => 'nullable|string|max:2000',
            'proxima_actividad_at' => 'nullable|date',
        ]);

        $validated['prospecto_id'] = $prospecto->id;
        $validated['user_id'] = Auth::id();

        $actividad = CrmActividad::create($validated);

        $prospecto->update([
            'ultima_actividad_at' => now(),
            'proxima_actividad_at' => $validated['proxima_actividad_at'] ?? null,
        ]);

        return response()->json($actividad, 201);
    }

    /**
     * Tareas del Usuario
     */
    public function tareas(Request $request)
    {
        $user = Auth::user();
        $tareas = CrmTarea::with('prospecto:id,nombre')
            ->where('user_id', $user->id)
            ->pendientes()
            ->orderBy('fecha_limite')
            ->get();

        return response()->json($tareas);
    }

    /**
     * Convertir a Cliente
     */
    public function convertir(CrmProspecto $prospecto)
    {
        $cliente = $prospecto->convertirACliente();
        return response()->json($cliente);
    }
}
