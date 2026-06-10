<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\User;
use App\Models\BitacoraActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\Panel\PanelBitacoraService;

class TodoController extends Controller
{
    private function isAdmin(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false;
    }

    private function todoForUserOrAdmin($todo): bool
    {
        if (!($todo instanceof Todo)) return false;
        return $todo->isOwnedBy(Auth::id()) || $this->isAdmin();
    }

    public function index(Request $request)
    {
        $prefilled = null;
        if ($request->has('cita_id')) {
            $cita = \App\Models\Cita::find($request->cita_id);
            if ($cita) {
                $prefilled = [
                    'title' => "Pendiente de Cita #{$cita->id}: {$cita->folio}",
                    'description' => "Vincular con cita: " . route('citas.show', $cita->id),
                    'related_id' => $cita->id,
                    'related_type' => 'cita',
                ];
            }
        } elseif ($request->has('ticket_id')) {
            $ticket = \App\Models\Ticket::find($request->ticket_id);
            if ($ticket) {
                $prefilled = [
                    'title' => "Pendiente de Ticket #{$ticket->id}: {$ticket->subject}",
                    'description' => "Vincular con ticket: " . route('soporte.show', $ticket->id),
                    'related_id' => $ticket->id,
                    'related_type' => 'ticket',
                ];
            }
        }

        $query = Todo::with(['steps', 'attachments']);
        if ($this->isAdmin() && $request->has('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        } elseif ($this->isAdmin()) {
            $query->with('user:id,name');
        } else {
            $query->where('user_id', auth()->id());
        }

        $todos = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN priority = 'high' THEN 0 WHEN priority = 'medium' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->get();

        // --- UNIFICACIÓN: Traer también actividades de bitácora ---
        $bitacoraQuery = BitacoraActividad::with(['usuario:id,name', 'cliente:id,nombre_razon_social']);
        if ($this->isAdmin() && $request->has('user_id')) {
            $bitacoraQuery->where('asignado_id', $request->integer('user_id'));
        } elseif (!$this->isAdmin()) {
            $bitacoraQuery->where('asignado_id', Auth::id());
        }
        
        $bitacoras = $bitacoraQuery->whereIn('estado', ['pendiente', 'en_proceso', 'completado'])->get();

        // Convertir Bitácora a formato de Todo para la lista unificada
        $virtualTodos = $bitacoras->map(function($b) {
            return (object)[
                'id' => "B{$b->id}", // Prefijo B para identificar bitácora
                'title' => "[TÉCNICO] {$b->titulo}",
                'description' => $b->descripcion,
                'status' => $b->estado === 'completado' ? 'completed' : 'pending',
                'priority' => $b->prioridad == 1 ? 'high' : ($b->prioridad == 2 ? 'medium' : 'low'),
                'due_date' => $b->fecha,
                'is_bitacora' => true,
                'bitacora_id' => $b->id,
                'cliente_nombre' => $b->cliente?->nombre_razon_social,
                'ubicacion' => $b->ubicacion,
                'steps' => [],
                'attachments' => [],
                'user' => $b->usuario,
                'created_at' => $b->created_at
            ];
        });

        // Unificar y re-ordenar
        $unifiedList = collect($todos)->concat($virtualTodos)
            ->sortByDesc('created_at')
            ->sortBy(function($t) {
                return $t->status === 'pending' ? 0 : 1;
            })->values();

        return Inertia::render('Todos/Index', [
            'todos' => $unifiedList,
            'prefilled' => $prefilled,
            'open_id' => $request->get('open_id'), // Mantener como string por el prefijo B
            'users' => $this->isAdmin() ? User::select('id', 'name')->where('activo', true)->orderBy('name')->get() : [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'recurrence' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'is_my_day' => 'nullable|boolean',
            'steps' => 'nullable|array',
            'steps.*.title' => 'required|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = auth()->user();
        $targetUserId = $user->id;
        $assignedBy = null;

        if ($this->isAdmin() && isset($validated['user_id'])) {
            $targetUserId = $validated['user_id'];
            $assignedBy = $user->id;
        }

        $todo = Todo::create([
            'user_id' => $targetUserId,
            'assigned_by' => $assignedBy,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'reminder_at' => $validated['reminder_at'] ?? null,
            'recurrence' => $validated['recurrence'] ?? 'none',
            'is_my_day' => $validated['is_my_day'] ?? false,
            'status' => 'pending',
        ]);

        if (!empty($validated['steps'])) {
            foreach ($validated['steps'] as $step) {
                $todo->steps()->create(['title' => $step['title']]);
            }
        }

        return back()->with('success', 'Tarea agregada correctamente.');
    }

    public function update(Request $request, $todo)
    {
        // --- UNIFICACIÓN: Manejar actualización de Bitácora ---
        if (is_string($todo) && str_starts_with($todo, 'B')) {
            $bitacoraId = substr($todo, 1);
            $bitacora = BitacoraActividad::findOrFail($bitacoraId);
            
            $v = $request->validate([
                'status' => 'sometimes|required|in:pending,completed',
                'title' => 'sometimes|string',
                'description' => 'nullable|string',
            ]);

            $updateData = [];
            if (isset($v['status'])) $updateData['estado'] = ($v['status'] === 'completed' ? 'completado' : 'pendiente');
            if (isset($v['title'])) $updateData['titulo'] = str_replace('[TÉCNICO] ', '', $v['title']);
            if (isset($v['description'])) $updateData['descripcion'] = $v['description'];
            
            $bitacora->update($updateData);
            return back()->with('success', 'Actividad técnica actualizada.');
        }

        // Si es una tarea normal, nos aseguramos de tener el modelo
        if (!($todo instanceof Todo)) {
            $todo = Todo::findOrFail($todo);
        }

        if (!$this->todoForUserOrAdmin($todo)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|in:pending,completed',
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'recurrence' => 'sometimes|required|in:none,daily,weekly,monthly,yearly',
            'is_my_day' => 'sometimes|required|boolean',
            'steps' => 'nullable|array',
            'steps.*.id' => 'nullable|integer',
            'steps.*.title' => 'required|string|max:255',
            'steps.*.is_completed' => 'required|boolean',
            'steps.*.delete' => 'nullable|boolean',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed' && $todo->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif (isset($validated['status']) && $validated['status'] === 'pending') {
            $validated['completed_at'] = null;
        }

        $data = collect($validated)->except(['steps'])->toArray();
        $todo->update($data);

        if ($todo->status === 'completed') {
            $todo->handleRecurrence();
        }

        if (isset($validated['steps'])) {
            foreach ($validated['steps'] as $stepData) {
                if (isset($stepData['delete']) && $stepData['delete'] && isset($stepData['id'])) {
                    $todo->steps()->where('id', $stepData['id'])->delete();
                } elseif (isset($stepData['id'])) {
                    $todo->steps()->where('id', $stepData['id'])->update([
                        'title' => $stepData['title'],
                        'is_completed' => $stepData['is_completed'],
                    ]);
                } else {
                    $todo->steps()->create([
                        'title' => $stepData['title'],
                        'is_completed' => $stepData['is_completed'],
                    ]);
                }
            }
        }

        return back()->with('success', 'Tarea actualizada.');
    }

    public function show($id)
    {
        // --- UNIFICACIÓN: Manejar vista de Bitácora ---
        if (is_string($id) && str_starts_with($id, 'B')) {
             return redirect()->route('todos.index', ['open_id' => $id]);
        }

        $todo = Todo::findOrFail($id);
        if (!$this->todoForUserOrAdmin($todo)) {
            abort(403);
        }

        // Redirigimos al index pasando el ID de la tarea para que el frontend la abra
        return redirect()->route('todos.index', ['open_id' => $todo->id]);
    }

    public function destroy($id)
    {
        // --- UNIFICACIÓN: Manejar eliminación de Bitácora ---
        if (is_string($id) && str_starts_with($id, 'B')) {
            $bitacoraId = substr($id, 1);
            $bitacora = BitacoraActividad::findOrFail($bitacoraId);
            $bitacora->delete();
            app(\App\Services\Panel\PanelBitacoraService::class)->clearCache(Auth::id());
            return back()->with('success', 'Actividad técnica eliminada.');
        }

        $todo = Todo::findOrFail($id);
        if (!$this->todoForUserOrAdmin($todo)) {
            abort(403);
        }

        $todo->delete();
        app(\App\Services\Panel\PanelBitacoraService::class)->clearCache(Auth::id());
        return back()->with('success', 'Tarea eliminada.');
    }
}
