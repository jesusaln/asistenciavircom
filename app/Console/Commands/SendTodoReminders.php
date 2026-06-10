<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Todo;
use App\Notifications\TodoReminderNotification;

class SendTodoReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todos:send-reminders';
    protected $description = 'Envia recordatorios de tareas pendientes que están por vencer';

    public function handle()
    {
        $now = now();
        // Revisamos tareas pendientes con recordatorio hoy o en el pasado que no se hayan enviado
        $todos = Todo::where('status', 'pending')
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', $now)
            ->where('reminder_sent', false)
            ->get();

        foreach ($todos as $todo) {
            $todo->user->notify(new TodoReminderNotification($todo));
            
            // Marcamos como enviado
            $todo->update(['reminder_sent' => true]);
            
            $this->info("Notificación enviada para: {$todo->title}");
        }

        return Command::SUCCESS;
    }
}
