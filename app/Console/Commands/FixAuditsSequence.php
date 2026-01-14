<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAuditsSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-audits-sequence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reparar la secuencia de la tabla audits para evitar duplicados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reparando secuencia de audits...');

        // Obtener el máximo id actual
        $maxId = DB::table('audits')->max('id') ?? 0;

        // Resetear la secuencia a max_id + 1
        $nextId = $maxId + 1;
        DB::statement("SELECT setval('audits_id_seq', {$nextId})");

        $this->info("Secuencia audits_id_seq reseteada a {$nextId} (máximo id actual: {$maxId}).");
    }
}
