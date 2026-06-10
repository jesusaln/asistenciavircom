<?php

namespace App\Notifications;

use App\Models\Todo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TodoReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $todo;

    public function __construct(Todo $todo)
    {
        $this->todo = $todo;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Recordatorio: {$this->todo->title}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Tienes un pendiente que requiere tu atención:")
            ->line("**Tarea:** {$this->todo->title}")
            ->line("**Prioridad:** " . ucfirst($this->todo->priority))
            ->line("**Vencimiento:** " . ($this->todo->due_date ? $this->todo->due_date->format('d/m/Y H:i') : 'No definida'))
            ->action('Ver Mis Pendientes', route('todos.index'))
            ->line('¡Que tengas un día productivo!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'todo_id' => $this->todo->id,
            'title' => $this->todo->title,
            'priority' => $this->todo->priority,
            'due_date' => $this->todo->due_date,
            'type' => 'todo_reminder',
        ];
    }
}
