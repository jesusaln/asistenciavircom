<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RealignSequences extends Command
{
    protected $signature = 'db:realign-sequences {tables* : One or more table names}';
    protected $description = 'Realign PostgreSQL sequences (or identities) for given tables to MAX(id)+1';

    public function handle(): int
    {
        $tables = $this->argument('tables');
        foreach ($tables as $table) {
            try {
                $this->realign($table);
                $this->info("Sequence aligned for {$table}.id");
            } catch (\Throwable $e) {
                $this->error("Failed to align {$table}.id: {$e->getMessage()}");
                return self::FAILURE;
            }
        }
        return self::SUCCESS;
    }

    private function realign(string $table): void
    {
        // Build a DO block without using PHP sprintf on % tokens used by Postgres format()
        $tableLiteral = str_replace("'", "''", $table);
        $sql = <<<SQL
DO $$
DECLARE
    seq_name text;
    max_id bigint;
    tbl text := '{$tableLiteral}';
BEGIN
    EXECUTE format('SELECT COALESCE(MAX(id), 0) + 1 FROM %I', tbl) INTO max_id;
    EXECUTE format('SELECT pg_get_serial_sequence(''%I'',''id'')', tbl) INTO seq_name;

    IF seq_name IS NOT NULL THEN
        PERFORM setval(seq_name, max_id, false);
    ELSE
        EXECUTE format('ALTER TABLE %I ALTER COLUMN id RESTART WITH %s', tbl, max_id);
    END IF;
END $$;
SQL;
        DB::statement($sql);
    }
}
