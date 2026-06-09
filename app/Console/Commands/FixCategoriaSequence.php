<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixCategoriaSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categoria:fix-sequence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige la secuencia de auto-incremento de la tabla categorias en PostgreSQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Verificando la tabla categorias...');

            // Obtener el máximo ID actual
            $maxId = DB::table('categorias')->max('id') ?? 0;
            $this->info("ID máximo actual: {$maxId}");

            // Obtener el valor actual de la secuencia
            $sequenceName = 'categorias_id_seq';
            $currentSequenceValue = DB::selectOne("SELECT last_value FROM {$sequenceName}");

            if ($currentSequenceValue) {
                $this->info("Valor actual de la secuencia: {$currentSequenceValue->last_value}");
            }

            // Resetear la secuencia para que continúe desde el siguiente ID disponible
            $nextId = $maxId + 1;
            DB::statement("SELECT setval('{$sequenceName}', {$nextId}, false)");

            $this->info("✓ Secuencia reseteada correctamente. Próximo ID será: {$nextId}");

            // Verificar que la corrección funcionó
            $newSequenceValue = DB::selectOne("SELECT last_value FROM {$sequenceName}");
            $this->info("Nuevo valor de la secuencia: {$newSequenceValue->last_value}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error al corregir la secuencia: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
