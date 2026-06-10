<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\TodoStep;
use App\Models\TodoAttachment;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    private function isAdmin($user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    private function todoForUserOrAdmin($user, Todo $todo): ?Todo
    {
        if ($todo->isOwnedBy($user->id) || $this->isAdmin($user)) {
            return $todo;
        }
        return null;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Todo::with(['steps', 'attachments']);

        if ($this->isAdmin($user)) {
            $query->with('user:id,name');
            if ($request->has('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        } else {
            $query->where('user_id', $user->id);
        }

        $todos = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN priority = 'high' THEN 0 WHEN priority = 'medium' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($todos);
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

        $user = $request->user();
        $targetUserId = $user->id;
        $assignedBy = null;

        // If admin assigns to another user, verify same company if not super-admin
        if ($this->isAdmin($user) && isset($validated['user_id'])) {
            $targetUserId = $validated['user_id'];
            $assignedBy = $user->id;
            
            // Security check: ensure target user belongs to the same company
            $targetUser = \App\Models\User::find($targetUserId);
            if ($targetUser && $targetUser->empresa_id !== $user->empresa_id && !$user->hasRole('super-admin')) {
                return response()->json(['message' => 'Cannot assign task to a user in a different company.'], 403);
            }
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

        return response()->json($todo->load(['steps', 'attachments']), 201);
    }

    public function show(Request $request, Todo $todo)
    {
        if (!$this->todoForUserOrAdmin($request->user(), $todo)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($todo->load('steps'));
    }

    public function update(Request $request, Todo $todo)
    {
        $user = $request->user();
        if (!$this->todoForUserOrAdmin($user, $todo)) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        return response()->json($todo->load('steps'));
    }

    public function destroy(Request $request, Todo $todo)
    {
        $user = $request->user();
        if (!$this->todoForUserOrAdmin($user, $todo)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $todo->delete();
        return response()->json(['message' => 'Todo deleted successfully']);
    }

    public function toggleStep(Request $request, TodoStep $step)
    {
        $todo = $step->todo;
        $user = $request->user();
        if (!$this->todoForUserOrAdmin($user, $todo)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $step->update([
            'is_completed' => !$step->is_completed
        ]);

        return response()->json($step);
    }

    public function uploadAttachment(Request $request, Todo $todo)
    {
        if (!$this->todoForUserOrAdmin($request->user(), $todo)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('todos/attachments', 'public');

        $attachment = $todo->attachments()->create([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json($attachment);
    }

    public function deleteAttachment(Todo $todo, TodoAttachment $attachment)
    {
        if (!$this->todoForUserOrAdmin(request()->user(), $todo)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($attachment->todo_id !== $todo->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        \Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
