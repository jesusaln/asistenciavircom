<?php

namespace App\Console\Commands;

use App\Models\Todo;
use Illuminate\Console\Command;

class ResetMyDayTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todos:reset-my-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia la lista de "Mi Día" para todos los usuarios al final del día.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Todo::where('is_my_day', true)->update(['is_my_day' => false]);
        
        $this->info("Se han limpiado {$count} tareas de \"Mi Día\".");
    }
}
