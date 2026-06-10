<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixVentaItemsSequence extends Command
{
    protected $signature = 'fix:venta-items-sequence';
    protected $description = 'Fix venta_items table sequence desynchronization';

    public function handle()
    {
        try {
            $this->info('🔧 Corrigiendo secuencia de venta_items...');
            
            // Resetear la secuencia al valor correcto
            DB::statement("SELECT setval(pg_get_serial_sequence('venta_items', 'id'), (SELECT MAX(id) FROM venta_items));");
            
            // Verificar el resultado
            $currentSeq = DB::selectOne("SELECT currval(pg_get_serial_sequence('venta_items', 'id')) as current_value");
            $maxId = DB::selectOne("SELECT MAX(id) as max_id FROM venta_items");
            
            $this->info('✅ Secuencia corregida exitosamente!');
            $this->line("Próximo ID que se asignará: " . ($currentSeq->current_value + 1));
            $this->line("ID máximo en la tabla: " . $maxId->max_id);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
