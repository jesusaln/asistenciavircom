<?php

namespace App\Http\Controllers;

use App\Models\Herramienta;
use App\Models\User;
use App\Models\HistorialHerramienta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Exception;

class GestionHerramientasController extends Controller
{
    public function index()
    {
        $tecnicos = User::tecnicosActivos()->with([
            'herramientas' => function ($query) {
                $query->select('id', 'nombre', 'numero_serie', 'estado', 'foto', 'categoria_id', 'user_id', 'fecha_ultimo_mantenimiento', 'requiere_mantenimiento')
                    ->with('categoriaHerramienta:id,nombre');
            }
        ])
            ->select('id', 'name', 'telefono', 'email')
            ->orderBy('name')
            ->get()
            ->map(function ($tecnico) {
                return [
                    'id' => $tecnico->id,
                    'nombre' => $tecnico->name,
                    'apellido' => '',
                    'nombre_completo' => $tecnico->name,
                    'telefono' => $tecnico->telefono,
                    'email' => $tecnico->email,
                    'herramientas' => $tecnico->herramientas->map(function ($herramienta) {
                        return [
                            'id' => $herramienta->id,
                            'nombre' => $herramienta->nombre,
                            'numero_serie' => $herramienta->numero_serie,
                            'estado' => $herramienta->estado,
                            'foto' => $herramienta->foto,
                            'categoria_herramienta' => $herramienta->categoriaHerramienta,
                            'fecha_ultimo_mantenimiento' => $herramienta->fecha_ultimo_mantenimiento,
                            'requiere_mantenimiento' => $herramienta->requiere_mantenimiento,
                            'necesita_mantenimiento' => $herramienta->necesitaMantenimiento(),
                        ];
                    }),
                ];
            });

        return Inertia::render('Herramientas/Gestion/Index', [
            'tecnicos' => $tecnicos,
        ]);
    }

    public function create()
    {
        return Inertia::render('Herramientas/Gestion/Create', [
            'tecnicos' => User::tecnicosActivos()->select('id', 'name', 'telefono')
                ->orderBy('name')
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'nombre' => $t->name,
                        'apellido' => '',
                        'nombre_completo' => $t->name,
                        'telefono' => $t->telefono,
                    ];
                }),
            'herramientas' => Herramienta::disponibles()
                ->with(['categoriaHerramienta:id,nombre'])
                ->select('id', 'nombre', 'numero_serie', 'estado', 'foto', 'categoria_id')
                ->orderBy('nombre')
                ->get()
                ->map(function ($herramienta) {
                    return [
                        'id' => $herramienta->id,
                        'nombre' => $herramienta->nombre,
                        'numero_serie' => $herramienta->numero_serie,
                        'estado' => $herramienta->estado,
                        'foto' => $herramienta->foto,
                        'categoria_herramienta' => $herramienta->categoriaHerramienta,
                    ];
                }),
        ]);
    }

    public function edit(User $tecnico)
    {
        $asignadas = Herramienta::where('user_id', $tecnico->id)
            ->with(['categoriaHerramienta:id,nombre'])
            ->select('id', 'nombre', 'numero_serie', 'estado', 'foto', 'categoria_id', 'fecha_ultimo_mantenimiento', 'requiere_mantenimiento')
            ->orderBy('nombre')
            ->get()
            ->map(function ($herramienta) {
                return [
                    'id' => $herramienta->id,
                    'nombre' => $herramienta->nombre,
                    'numero_serie' => $herramienta->numero_serie,
                    'estado' => $herramienta->estado,
                    'foto' => $herramienta->foto,
                    'categoria_herramienta' => $herramienta->categoriaHerramienta,
                    'fecha_ultimo_mantenimiento' => $herramienta->fecha_ultimo_mantenimiento,
                    'requiere_mantenimiento' => $herramienta->requiere_mantenimiento,
                    'necesita_mantenimiento' => $herramienta->necesitaMantenimiento(),
                ];
            });

        $disponibles = Herramienta::disponibles()
            ->with(['categoriaHerramienta:id,nombre'])
            ->select('id', 'nombre', 'numero_serie', 'estado', 'foto', 'categoria_id')
            ->orderBy('nombre')
            ->get()
            ->map(function ($herramienta) {
                return [
                    'id' => $herramienta->id,
                    'nombre' => $herramienta->nombre,
                    'numero_serie' => $herramienta->numero_serie,
                    'estado' => $herramienta->estado,
                    'foto' => $herramienta->foto,
                    'categoria_herramienta' => $herramienta->categoriaHerramienta,
                ];
            });

        return Inertia::render('Herramientas/Gestion/Edit', [
            'tecnico' => [
                'id' => $tecnico->id,
                'nombre' => $tecnico->name,
                'apellido' => '',
                'nombre_completo' => $tecnico->name,
                'telefono' => $tecnico->telefono,
                'email' => $tecnico->email,
            ],
            'asignadas' => $asignadas,
            'disponibles' => $disponibles,
            'tecnicos' => User::tecnicosActivos()->select('id', 'name', 'telefono')
                ->orderBy('name')
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'nombre' => $t->name,
                        'apellido' => '',
                        'nombre_completo' => $t->name,
                        'telefono' => $t->telefono,
                    ];
                }),
        ]);
    }

    public function asignar(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'herramientas' => 'array',
            'herramientas.*' => 'integer|exists:herramientas,id',
        ]);

        $ids = $data['herramientas'] ?? [];

        if (empty($ids)) {
            return redirect()->route('herramientas.gestion.index')->with('info', 'No se seleccionaron herramientas');
        }

        try {
            DB::beginTransaction();

            // Bloquear y validar herramientas
            $herramientas = Herramienta::whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            // VALIDACIÓN POST-LOCK: Verificar que todas están disponibles
            // Esto previene race conditions si dos usuarios intentan asignar la misma herramienta
            $noDisponibles = $herramientas->filter(function ($h) {
                return $h->estado !== Herramienta::ESTADO_DISPONIBLE;
            });

            if ($noDisponibles->isNotEmpty()) {
                DB::rollBack();
                $nombres = $noDisponibles->pluck('nombre')->join(', ');
                return redirect()->back()->with(
                    'error',
                    "Las siguientes herramientas no están disponibles: {$nombres}"
                );
            }

            // Asignar herramientas INDIVIDUALMENTE para que el Observer se ejecute
            foreach ($herramientas as $herramienta) {
                $herramienta->tecnico_id = $data['tecnico_id'];
                $herramienta->save(); // Dispara Observer -> crea historial automáticamente
            }

            DB::commit();

            return redirect()->route('herramientas.gestion.index')
                ->with('success', 'Herramientas asignadas correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al asignar herramientas: ' . $e->getMessage(), [
                'tecnico_id' => $data['tecnico_id'],
                'herramientas' => $ids,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error al asignar las herramientas: ' . $e->getMessage());
        }
    }

    public function update(Request $request, User $tecnico)
    {
        $data = $request->validate([
            'asignadas' => 'array',
            'asignadas.*' => 'integer|exists:herramientas,id',
        ]);

        try {
            DB::beginTransaction();

            $deseadas = collect($data['asignadas'] ?? []);
            $actuales = Herramienta::where('user_id', $tecnico->id)->pluck('id');

            $aRemover = $actuales->diff($deseadas);
            $aAgregar = $deseadas->diff($actuales);

            // VALIDACIÓN POST-LOCK: Validar que las herramientas a agregar están disponibles
            if ($aAgregar->isNotEmpty()) {
                $herramientasAAgregar = Herramienta::whereIn('id', $aAgregar)
                    ->lockForUpdate()
                    ->get();

                $herramientasNoDisponibles = $herramientasAAgregar
                    ->filter(fn($h) => $h->estado !== Herramienta::ESTADO_DISPONIBLE)
                    ->pluck('nombre');

                if ($herramientasNoDisponibles->isNotEmpty()) {
                    DB::rollBack();
                    return redirect()->back()->with(
                        'error',
                        'Las siguientes herramientas no están disponibles: ' . $herramientasNoDisponibles->join(', ')
                    );
                }
            }

            // Liberar herramientas INDIVIDUALMENTE
            if ($aRemover->isNotEmpty()) {
                $herramientasARemover = Herramienta::whereIn('id', $aRemover)->get();
                foreach ($herramientasARemover as $herramienta) {
                    $herramienta->user_id = null;
                    $herramienta->save(); // Observer se ejecuta -> crea historial
                }
            }

            // Asignar herramientas INDIVIDUALMENTE
            if ($aAgregar->isNotEmpty()) {
                $herramientasAAgregar = Herramienta::whereIn('id', $aAgregar)->get();
                foreach ($herramientasAAgregar as $herramienta) {
                    $herramienta->user_id = $tecnico->id;
                    $herramienta->save(); // Observer se ejecuta -> crea historial
                }
            }

            DB::commit();

            return redirect()->route('herramientas.gestion.edit', $tecnico->id)
                ->with('success', 'Asignaciones actualizadas correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error al actualizar asignaciones: ' . $e->getMessage(), [
                'tecnico_id' => $tecnico->id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error al actualizar las asignaciones: ' . $e->getMessage());
        }
    }

    public function reasignar(Request $request)
    {
        $data = $request->validate([
            'herramienta_id' => 'required|exists:herramientas,id',
            'tecnico_anterior_id' => 'required|exists:users,id',
            'tecnico_nuevo_id' => 'required|exists:users,id',
            'observaciones' => 'nullable|string',
        ]);

        // VALIDACIÓN: Técnicos deben ser diferentes
        if ($data['tecnico_anterior_id'] === $data['tecnico_nuevo_id']) {
            return redirect()->back()->with('error', 'El técnico nuevo debe ser diferente al técnico anterior');
        }

        try {
            DB::beginTransaction();

            $herramienta = Herramienta::lockForUpdate()->findOrFail($data['herramienta_id']);

            // Verificar que la herramienta pertenece al técnico anterior
            if ($herramienta->tecnico_id !== $data['tecnico_anterior_id']) {
                DB::rollBack();
                return redirect()->back()->with('error', 'La herramienta no pertenece al técnico especificado');
            }

            // Verificar que está asignada
            if ($herramienta->estado !== Herramienta::ESTADO_ASIGNADA) {
                DB::rollBack();
                return redirect()->back()->with('error', 'La herramienta no está en estado asignada');
            }

            // Actualizar la herramienta
            // El observer se encargará de:
            // 1. Crear registro de devolución del técnico anterior
            // 2. Crear registro de asignación al técnico nuevo
            // 3. Mantener el estado como "asignada"
            $herramienta->update([
                'user_id' => $data['tecnico_nuevo_id'],
                // estado se mantiene como 'asignada' automáticamente por el observer
            ]);

            // Si hay observaciones, agregarlas al último registro de historial creado
            // con formato estructurado y timestamp
            if (!empty($data['observaciones'])) {
                $ultimoHistorial = \App\Models\HistorialHerramienta::where('herramienta_id', $herramienta->id)
                    ->latest('created_at')
                    ->first();

                if ($ultimoHistorial) {
                    $timestamp = now()->format('Y-m-d H:i:s');
                    $nuevaObservacion = "[{$timestamp}] " . $data['observaciones'];

                    $observacionesActuales = $ultimoHistorial->observaciones_asignacion;
                    $observacionesActualizadas = $observacionesActuales
                        ? $observacionesActuales . "\n" . $nuevaObservacion
                        : $nuevaObservacion;

                    $ultimoHistorial->update([
                        'observaciones_asignacion' => $observacionesActualizadas
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Herramienta reasignada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error al reasignar herramienta: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Error al reasignar la herramienta: ' . $e->getMessage());
        }
    }

    public function exportarPorTecnico($tecnicoId)
    {
        try {
            // Buscar el técnico por ID
            $tecnico = User::findOrFail($tecnicoId);

            // Obtener herramientas asignadas al técnico con información completa
            $herramientas = Herramienta::where('user_id', $tecnico->id)
                ->where('estado', Herramienta::ESTADO_ASIGNADA)
                ->with(['categoriaHerramienta'])
                ->get()
                ->map(function ($herramienta) {
                    return [
                        'id' => $herramienta->id,
                        'nombre' => $herramienta->nombre,
                        'numero_serie' => $herramienta->numero_serie,
                        'categoria' => $herramienta->categoria_label,
                        'estado' => $herramienta->estado_label,
                        'costo_reemplazo' => $herramienta->costo_reemplazo,
                        'fecha_ultimo_mantenimiento' => $herramienta->fecha_ultimo_mantenimiento?->format('d/m/Y'),
                        'dias_para_mantenimiento' => $herramienta->dias_para_mantenimiento,
                        'vida_util_meses' => $herramienta->vida_util_meses,
                        'descripcion' => $herramienta->descripcion,
                        'fecha_asignacion' => $herramienta->fecha_asignacion?->format('d/m/Y H:i:s'),
                        'ultima_inspeccion' => null,
                        'condicion_ultima_inspeccion' => 'Sin inspección',
                        'necesita_mantenimiento' => $herramienta->necesitaMantenimiento(),
                        'dias_desde_ultimo_mantenimiento' => $herramienta->dias_desde_ultimo_mantenimiento,
                        'porcentaje_vida_util' => $herramienta->porcentaje_vida_util,
                    ];
                });

            // Información del técnico
            $tecnicoInfo = [
                'id' => $tecnico->id,
                'nombre' => $tecnico->name,
                'apellido' => '',
                'nombre_completo' => $tecnico->name,
                'email' => $tecnico->email,
                'telefono' => $tecnico->telefono,
                'fecha_exportacion' => now()->format('d/m/Y H:i:s'),
            ];

            // Estadísticas
            $estadisticas = [
                'total_herramientas' => $herramientas->count(),
                'valor_total' => $herramientas->sum('costo_reemplazo'),
                'herramientas_mantenimiento' => $herramientas->where('necesita_mantenimiento', true)->count(),
                'herramientas_por_vencer' => $herramientas->where('porcentaje_vida_util', '>', 80)->count(),
            ];

            return Inertia::render('Herramientas/Gestion/Exportar', [
                'tecnico' => $tecnicoInfo,
                'herramientas' => $herramientas,
                'estadisticas' => $estadisticas,
            ]);

        } catch (Exception $e) {
            Log::error('Error en exportación de herramientas por técnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el reporte de herramientas.');
        }
    }

    public function descargarReporteTecnico($tecnicoId)
    {
        try {
            // Buscar el técnico por ID
            $tecnico = User::findOrFail($tecnicoId);

            // Obtener herramientas asignadas al técnico
            $herramientas = Herramienta::where('user_id', $tecnico->id)
                ->where('estado', Herramienta::ESTADO_ASIGNADA)
                ->with(['categoriaHerramienta'])
                ->get();

            $filename = 'herramientas_tecnico_' . $tecnico->nombre . '_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($tecnico, $herramientas) {
                $file = fopen('php://output', 'w');

                // Encabezado del reporte
                fputcsv($file, ['REPORTE DE HERRAMIENTAS ASIGNADAS']);
                fputcsv($file, ['Técnico:', $tecnico->name]);
                fputcsv($file, ['Fecha de Exportación:', now()->format('d/m/Y H:i:s')]);
                fputcsv($file, ['']);

                // Encabezados de la tabla
                fputcsv($file, [
                    'ID',
                    'Nombre',
                    'Número de Serie',
                    'Categoría',
                    'Estado',
                    'Costo de Reemplazo',
                    'Fecha Último Mantenimiento',
                    'Días para Mantenimiento',
                    'Vida Útil (meses)',
                    'Descripción',
                    'Fecha de Asignación',
                    'Necesita Mantenimiento'
                ]);

                foreach ($herramientas as $herramienta) {
                    fputcsv($file, [
                        $herramienta->id,
                        $herramienta->nombre,
                        $herramienta->numero_serie,
                        $herramienta->categoria_label,
                        $herramienta->estado_label,
                        $herramienta->costo_reemplazo,
                        $herramienta->fecha_ultimo_mantenimiento?->format('d/m/Y'),
                        $herramienta->dias_para_mantenimiento,
                        $herramienta->vida_util_meses,
                        $herramienta->descripcion,
                        $herramienta->fecha_asignacion?->format('d/m/Y H:i:s'),
                        $herramienta->necesitaMantenimiento() ? 'Sí' : 'No'
                    ]);
                }

                // Estadísticas finales
                fputcsv($file, ['']);
                fputcsv($file, ['ESTADÍSTICAS']);
                fputcsv($file, ['Total de Herramientas:', $herramientas->count()]);
                fputcsv($file, ['Valor Total:', '$' . number_format($herramientas->sum('costo_reemplazo'), 2)]);
                fputcsv($file, ['Herramientas que necesitan mantenimiento:', $herramientas->where('requiere_mantenimiento', true)->count()]);

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (Exception $e) {
            Log::error('Error en descarga de reporte de herramientas por técnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al descargar el reporte de herramientas.');
        }
    }
}
