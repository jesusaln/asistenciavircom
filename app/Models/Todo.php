<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\BelongsToEmpresa;

class Todo extends Model
{
    use HasFactory, BelongsToEmpresa;
    
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($todo) {
            if ($todo->isDirty('reminder_at')) {
                $todo->reminder_sent = false;
            }
        });
    }

    protected $fillable = [
        'empresa_id',
        'user_id',
        'assigned_by',
        'title',
        'description',
        'notes',
        'status',
        'priority',
        'is_my_day',
        'due_date',
        'reminder_at',
        'recurrence',
        'completed_at',
        'related_id',
        'related_type',
        'reminder_sent',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'reminder_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_my_day' => 'boolean',
        'reminder_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAdmin($query)
    {
        return $query->with('user:id,name');
    }

    public function isOwnedBy($userId): bool
    {
        return $this->user_id === $userId;
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TodoStep::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TodoAttachment::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calcula y actualiza la siguiente fecha si la tarea es recurrente.
     */
    public function handleRecurrence()
    {
        if ($this->recurrence === 'none') return;

        $nextDueDate = $this->due_date ? clone $this->due_date : null;
        $nextReminderAt = $this->reminder_at ? clone $this->reminder_at : null;

        $interval = match ($this->recurrence) {
            'daily' => 'addDay',
            'weekly' => 'addWeek',
            'monthly' => 'addMonth',
            'yearly' => 'addYear',
            default => null,
        };

        if ($interval) {
            if ($nextDueDate) $nextDueDate->$interval();
            if ($nextReminderAt) $nextReminderAt->$interval();

            // En lugar de modificar la tarea actual (que ya está completada),
            // creamos una COPIA para el siguiente ciclo, como hace MS To Do.
            $newTask = $this->replicate();
            $newTask->status = 'pending';
            $newTask->completed_at = null;
            $newTask->due_date = $nextDueDate;
            $newTask->reminder_at = $nextReminderAt;
            $newTask->save();

            // Copiar pasos si existen
            foreach ($this->steps as $step) {
                $newTask->steps()->create([
                    'title' => $step->title,
                    'is_completed' => false,
                ]);
            }
        }
    }
}
