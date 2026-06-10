<?php

namespace App\Observers;

use App\Models\Todo;

class TodoObserver
{
    /**
     * Handle the Todo "created" event.
     */
    public function created(Todo $todo): void
    {
        //
    }

    /**
     * Handle the Todo "updated" event.
     */
    public function updated(Todo $todo): void
    {
        if ($todo->wasChanged('status') && $todo->status === 'completed' && $todo->recurrence !== 'none') {
            $this->createNextRecurringTask($todo);
        }
    }

    protected function createNextRecurringTask(Todo $todo): void
    {
        $newDueDate = null;
        if ($todo->due_date) {
            $newDueDate = match ($todo->recurrence) {
                'daily' => $todo->due_date->addDay(),
                'weekly' => $todo->due_date->addWeek(),
                'monthly' => $todo->due_date->addMonth(),
                'yearly' => $todo->due_date->addYear(),
                default => null,
            };
        } else {
            // Si no tiene fecha, usamos hoy + intervalo
            $newDueDate = match ($todo->recurrence) {
                'daily' => now()->addDay(),
                'weekly' => now()->addWeek(),
                'monthly' => now()->addMonth(),
                'yearly' => now()->addYear(),
                default => null,
            };
        }

        $newTodo = $todo->replicate(['completed_at', 'status']);
        $newTodo->status = 'pending';
        $newTodo->due_date = $newDueDate;
        
        // El recordatorio también se desplaza si existía
        if ($todo->reminder_at && $newDueDate) {
            $diff = $todo->due_date ? $todo->due_date->diffInMinutes($todo->reminder_at, false) : 0;
            $newTodo->reminder_at = (clone $newDueDate)->addMinutes($diff);
        }

        $newTodo->save();

        // Replicar pasos también
        foreach ($todo->steps as $step) {
            $newTodo->steps()->create([
                'title' => $step->title,
                'is_completed' => false,
            ]);
        }
    }

    /**
     * Handle the Todo "deleted" event.
     */
    public function deleted(Todo $todo): void
    {
        //
    }

    /**
     * Handle the Todo "restored" event.
     */
    public function restored(Todo $todo): void
    {
        //
    }

    /**
     * Handle the Todo "force deleted" event.
     */
    public function forceDeleted(Todo $todo): void
    {
        //
    }
}
