<?php

namespace App\Http\Controllers;

use App\Models\DisponibilidadTecnico;
use App\Models\DiaBloqueado;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * Controlador para gestionar la disponibilidad de los técnicos
 */
class DisponibilidadTecnicoController extends Controller
{
    /**
     * Mostrar configuración de disponibilidad
     */
    public function index(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        // Obtener técnicos
        $tecnicos = User::where('empresa_id', $empresaId)
            ->whereHas('rol', function ($q) {
                $q->where('nombre', 'like', '%tecnico%')
                    ->orWhere('nombre', 'like', '%técnico%');
            })
            ->orWhere(function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId)
                    ->where('es_tecnico', true);
            })
            ->get(['id', 'name', 'email', 'telefono']);

        // Obtener disponibilidades configuradas
        $disponibilidades = DisponibilidadTecnico::where('empresa_id', $empresaId)
            ->with('tecnico:id,name')
            ->get()
            ->groupBy('tecnico_id');

        // Días bloqueados próximos
        $diasBloqueados = DiaBloqueado::where('empresa_id', $empresaId)
            ->where('fecha', '>=', today())
            ->orderBy('fecha')
            ->with('tecnico:id,name')
            ->take(50)
            ->get();

        return Inertia::render('Admin/DisponibilidadTecnicos/Index', [
            'tecnicos' => $tecnicos,
            'disponibilidades' => $disponibilidades,
            'diasBloqueados' => $diasBloqueados,
            'diasSemana' => DisponibilidadTecnico::DIAS_SEMANA,
        ]);
    }

    /**
     * Guardar/Actualizar disponibilidad de un técnico
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tecnico_id' => 'required|exists:users,id',
            'disponibilidad' => 'required|array',
            'disponibilidad.*.dia_semana' => 'required|integer|between:0,6',
            'disponibilidad.*.activo' => 'required|boolean',
            'disponibilidad.*.hora_inicio' => 'required_if:disponibilidad.*.activo,true|nullable|date_format:H:i',
            'disponibilidad.*.hora_fin' => 'required_if:disponibilidad.*.activo,true|nullable|date_format:H:i',
            'disponibilidad.*.max_citas_dia' => 'nullable|integer|min:1|max:20',
        ]);

        $empresaId = auth()->user()->empresa_id;

        foreach ($validated['disponibilidad'] as $disp) {
            DisponibilidadTecnico::updateOrCreate(
                [
                    'empresa_id' => $empresaId,
                    'tecnico_id' => $validated['tecnico_id'],
                    'dia_semana' => $disp['dia_semana'],
                ],
                [
                    'activo' => $disp['activo'],
                    'hora_inicio' => $disp['hora_inicio'] ?? '08:00',
                    'hora_fin' => $disp['hora_fin'] ?? '18:00',
                    'max_citas_dia' => $disp['max_citas_dia'] ?? 5,
                ]
            );
        }

        return back()->with('success', 'Disponibilidad actualizada correctamente');
    }

    /**
     * Bloquear un día específico
     */
    public function bloquearDia(Request $request)
    {
        $validated = $request->validate([
            'tecnico_id' => 'nullable|exists:users,id',
            'fecha' => 'required|date|after_or_equal:today',
            'motivo' => 'nullable|string|max:255',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
        ]);

        $empresaId = auth()->user()->empresa_id;

        DiaBloqueado::create([
            'empresa_id' => $empresaId,
            'tecnico_id' => $validated['tecnico_id'],
            'fecha' => $validated['fecha'],
            'motivo' => $validated['motivo'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
        ]);

        return back()->with('success', 'Día bloqueado correctamente');
    }

    /**
     * Desbloquear un día
     */
    public function desbloquearDia(DiaBloqueado $diaBloqueado)
    {
        $diaBloqueado->delete();
        return back()->with('success', 'Día desbloqueado');
    }

    /**
     * API: Obtener disponibilidad de un técnico
     */
    public function getTecnicoDisponibilidad(int $tecnicoId)
    {
        $empresaId = auth()->user()->empresa_id;

        $disponibilidad = DisponibilidadTecnico::where('empresa_id', $empresaId)
            ->where('tecnico_id', $tecnicoId)
            ->get()
            ->keyBy('dia_semana');

        // Crear array completo de 7 días
        $semana = [];
        for ($i = 0; $i < 7; $i++) {
            if (isset($disponibilidad[$i])) {
                $semana[$i] = $disponibilidad[$i];
            } else {
                $semana[$i] = [
                    'dia_semana' => $i,
                    'activo' => false,
                    'hora_inicio' => '08:00',
                    'hora_fin' => '18:00',
                    'max_citas_dia' => 5,
                ];
            }
        }

        return response()->json($semana);
    }

    /**
     * Configuración rápida: copiar disponibilidad de otro técnico
     */
    public function copiarDisponibilidad(Request $request)
    {
        $validated = $request->validate([
            'tecnico_origen' => 'required|exists:users,id',
            'tecnico_destino' => 'required|exists:users,id|different:tecnico_origen',
        ]);

        $empresaId = auth()->user()->empresa_id;

        // Obtener disponibilidad del origen
        $disponibilidadOrigen = DisponibilidadTecnico::where('empresa_id', $empresaId)
            ->where('tecnico_id', $validated['tecnico_origen'])
            ->get();

        // Eliminar disponibilidad actual del destino
        DisponibilidadTecnico::where('empresa_id', $empresaId)
            ->where('tecnico_id', $validated['tecnico_destino'])
            ->delete();

        // Copiar al destino
        foreach ($disponibilidadOrigen as $disp) {
            DisponibilidadTecnico::create([
                'empresa_id' => $empresaId,
                'tecnico_id' => $validated['tecnico_destino'],
                'dia_semana' => $disp->dia_semana,
                'hora_inicio' => $disp->hora_inicio,
                'hora_fin' => $disp->hora_fin,
                'max_citas_dia' => $disp->max_citas_dia,
                'activo' => $disp->activo,
            ]);
        }

        return back()->with('success', 'Disponibilidad copiada correctamente');
    }
}
