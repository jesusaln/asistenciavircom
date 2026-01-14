<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAllSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:sequences {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix PostgreSQL sequences for all tables or a specific table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $tableName = $this->argument('table');

            if ($tableName) {
                // Fix specific table
                $this->fixTableSequence($tableName);
            } else {
                // Fix all tables
                $this->info('Fixing sequences for all tables...');
                $this->newLine();

                $tables = $this->getAllTables();
                
                foreach ($tables as $table) {
                    $this->fixTableSequence($table);
                }

                $this->newLine();
                $this->info('✓ All sequences have been fixed successfully!');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error fixing sequences: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get all table names from the database
     */
    private function getAllTables(): array
    {
        $tables = DB::select("
            SELECT tablename 
            FROM pg_tables 
            WHERE schemaname = 'public'
            ORDER BY tablename
        ");

        return array_map(fn($table) => $table->tablename, $tables);
    }

    /**
     * Fix sequence for a specific table
     */
    private function fixTableSequence(string $tableName): void
    {
        try {
            // Check if table exists
            $tableExists = DB::select("
                SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_schema = 'public' 
                    AND table_name = ?
                )
            ", [$tableName]);

            if (!$tableExists[0]->exists) {
                $this->warn("Table '{$tableName}' does not exist. Skipping...");
                return;
            }

            // Get the sequence name for the id column
            $sequenceResult = DB::select("SELECT pg_get_serial_sequence(?, 'id') as sequence_name", [$tableName]);
            $sequenceName = $sequenceResult[0]->sequence_name ?? null;

            if (!$sequenceName) {
                $this->line("  <fg=gray>No sequence found for table '{$tableName}'. Skipping...</>");
                return;
            }

            // Get the maximum ID from the table
            $maxId = DB::table($tableName)->max('id') ?? 0;

            // Reset the sequence to max ID + 1
            $result = DB::select("SELECT setval('{$sequenceName}', ?, false) as new_value", [$maxId + 1]);
            $newValue = $result[0]->new_value;

            $this->info("  ✓ Fixed '{$tableName}' sequence: {$sequenceName} → {$newValue}");
        } catch (\Exception $e) {
            $this->error("  ✗ Error fixing '{$tableName}': " . $e->getMessage());
        }
    }
}
