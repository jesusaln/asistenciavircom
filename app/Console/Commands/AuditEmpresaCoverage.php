<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

class AuditEmpresaCoverage extends Command
{
    protected $signature = 'audit:empresa-coverage';
    protected $description = 'Audit models for empresa_id column and BelongsToEmpresa trait usage';

    public function handle()
    {
        $modelsPath = app_path('Models');
        $files = File::allFiles($modelsPath);

        $results = [];
        $header = ['Model', 'Has firma_id Column', 'Has Trait', 'Status'];

        foreach ($files as $file) {
            $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (!class_exists($className)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($className);
                if ($reflection->isAbstract() || !$reflection->isSubclassOf(Model::class)) {
                    continue;
                }

                $model = new $className;
                $table = $model->getTable();

                // Skip if table doesn't exist (e.g. view or misconfigured)
                if (!Schema::hasTable($table)) {
                    continue;
                }

                $hasColumn = Schema::hasColumn($table, 'empresa_id');
                $traits = class_uses_recursive($className);
                $hasTrait = in_array('App\Models\Concerns\BelongsToEmpresa', $traits);

                $status = 'OK';
                if ($hasColumn && !$hasTrait) {
                    $status = 'RISK: Missing Trait';
                } elseif (!$hasColumn && $hasTrait) {
                    $status = 'ERROR: Trait without Column';
                } elseif (!$hasColumn) {
                    $status = 'Global/Shared';
                }

                $results[] = [
                    'model' => $className,
                    'has_column' => $hasColumn ? 'YES' : 'NO',
                    'has_trait' => $hasTrait ? 'YES' : 'NO',
                    'status' => $status,
                ];

            } catch (\Throwable $e) {
                $this->error("Error examining {$className}: " . $e->getMessage());
            }
        }

        $this->table(['Model', 'Has empresa_id', 'Has Trait', 'Status'], $results);

        // Filter significantly
        $risks = array_filter($results, fn($r) => str_contains($r['status'], 'RISK'));

        if (!empty($risks)) {
            $this->error("\nFound " . count($risks) . " models with RISKS (Has Column but Missing Trait):");
            foreach ($risks as $risk) {
                $this->line(" - " . $risk['model']);
            }
        } else {
            $this->info("\nNo High Risks found. All models with 'empresa_id' have the Trait.");
        }
    }
}
