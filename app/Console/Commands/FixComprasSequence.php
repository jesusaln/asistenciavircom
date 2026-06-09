<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixComprasSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:compras-sequence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix PostgreSQL sequence for compras table to prevent unique constraint violations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Fixing compras table sequence...');

            // Get the maximum ID from the compras table
            $maxId = DB::table('compras')->max('id') ?? 0;
            $this->info("Maximum ID in compras table: {$maxId}");

            // Reset the sequence to max ID + 1
            $sequenceName = DB::select("SELECT pg_get_serial_sequence('compras', 'id') as sequence_name")[0]->sequence_name;
            
            if (!$sequenceName) {
                $this->error('Could not find sequence for compras table');
                return 1;
            }

            $this->info("Sequence name: {$sequenceName}");

            // Set the sequence value and get the result
            $result = DB::select("SELECT setval('{$sequenceName}', ?, false) as new_value", [$maxId + 1]);
            $newValue = $result[0]->new_value;
            
            $this->info("Sequence reset successfully!");
            $this->info("New sequence value: {$newValue}");

            $this->newLine();
            $this->info('✓ Compras sequence has been fixed successfully!');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error fixing sequence: ' . $e->getMessage());
            return 1;
        }
    }
}
