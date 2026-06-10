<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckMassAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:check-mass-assignment
                            {--model= : Specific model to check}
                            {--fix : Auto-add $fillable to vulnerable models}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audita modelos para verificar protección contra Mass Assignment';

    /**
     * Modelos vulnerables encontrados
     */
    protected array $vulnerableModels = [];

    /**
     * Modelos seguros
     */
    protected array $safeModels = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->option('model');
        $shouldFix = $this->option('fix');

        $this->info('🔒 Audit Mass Assignment Protection');
        $this->newLine();

        if ($modelName) {
            $this->checkSingleModel($modelName, $shouldFix);
        } else {
            $this->auditAllModels($shouldFix);
        }

        $this->displayResults($shouldFix);

        return count($this->vulnerableModels) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Audita todos los modelos del proyecto
     */
    protected function auditAllModels(bool $shouldFix): void
    {
        $modelPath = app_path('Models');
        $files = glob("{$modelPath}/*.php");
        $this->info("📊 Found " . count($files) . " model files");

        $bar = $this->output->createProgressBar(count($files));
        $bar->setMessage('Checking models...');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className)) {
                $this->checkSingleModel($className, $shouldFix, false);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Verifica un modelo específico
     */
    protected function checkSingleModel(string $modelName, bool $shouldFix, bool $displayOutput = true): void
    {
        if (!class_exists($modelName)) {
            if ($displayOutput) {
                $this->error("Model not found: {$modelName}");
            }
            return;
        }

        $reflection = new \ReflectionClass($modelName);

        // Saltar clases abstractas e interfaces
        if ($reflection->isAbstract() || !$reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
            return;
        }

        // Instanciar modelo para verificar propiedades
        try {
            $model = new $modelName();
            $fillableValues = $model->getFillable() ?? [];
            $guardedValues = $model->getGuarded() ?? [];
        } catch (\Throwable $e) {
            // Si no se puede instanciar, intentar con reflection básica
            $fillableValues = $reflection->getDefaultProperties()['fillable'] ?? [];
            $guardedValues = $reflection->getDefaultProperties()['guarded'] ?? [];
        }

        $hasFillable = !empty($fillableValues);
        $hasGuarded = !empty($guardedValues);

        // Verificar si tiene unguuard
        $hasUnguarded = $reflection->hasMethod('unguard');

        $status = $this->determineStatus($hasFillable, $fillableValues, $guardedValues, $hasUnguarded);

        $modelInfo = [
            'name' => $modelName,
            'short_name' => $reflection->getShortName(),
            'has_fillable' => $hasFillable,
            'fillable_count' => count($fillableValues),
            'has_guarded' => $hasGuarded,
            'guarded_count' => count($guardedValues),
            'is_unguarded' => $hasUnguarded,
            'status' => $status,
        ];

        if ($status === 'VULNERABLE') {
            $this->vulnerableModels[] = $modelInfo;

            if ($displayOutput) {
                $this->warn("⚠️  {$modelInfo['short_name']}: No \$fillable or \$guarded defined");
            }

            if ($shouldFix) {
                $this->fixModel($modelName, $reflection);
            }
        } else {
            $this->safeModels[] = $modelInfo;

            if ($displayOutput && $status === 'SECURE') {
                $this->line("✅ {$modelInfo['short_name']}: Protected ({$modelInfo['fillable_count']} fillable fields)");
            }
        }
    }

    /**
     * Determina el estado de protección del modelo
     */
    protected function determineStatus(bool $hasFillable, array $fillableValues, array $guardedValues, bool $hasUnguarded): string
    {
        if ($hasUnguarded) {
            return 'UNGUARDED';
        }

        if ($hasFillable && !empty($fillableValues)) {
            return 'SECURE';
        }

        if (!empty($guardedValues)) {
            return 'SECURE';
        }

        return 'VULNERABLE';
    }

    /**
     * Corrige un modelo añadiendo $fillable
     */
    protected function fixModel(string $modelName, \ReflectionClass $reflection): void
    {
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);

        // Intentar obtener las columnas de la tabla
        try {
            $model = new $modelName();
            $columns = $model->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($model->getTable());

            // Excluir campos comunes que no deberían ser fillable
            $excludeColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];

            if (in_array('empresa_id', $columns)) {
                $excludeColumns[] = 'empresa_id';
            }

            $fillableColumns = array_diff($columns, $excludeColumns);

            $fillableDeclaration = "\n    protected \$fillable = [\n        '"
                . implode("',\n        '", $fillableColumns)
                . "',\n    ];\n";

            // Buscar la posición después de la declaración de la clase
            $classPattern = '/class\s+' . $reflection->getShortName() . '\s+extends\s+Model/';

            if (preg_match($classPattern, $content)) {
                $insertPosition = strpos($content, $classPattern) + strlen($reflection->getName()) + 20;

                // Encontrar el primer { después de la declaración de la clase
                $bracePos = strpos($content, '{', $insertPosition);
                if ($bracePos !== false) {
                    $content = substr_replace($content, $fillableDeclaration, $bracePos + 1, 0);
                    file_put_contents($fileName, $content);

                    $this->info("   ✅ Added \$fillable to {$reflection->getShortName()}");
                }
            }
        } catch (\Throwable $e) {
            $this->error("   ❌ Could not fix {$reflection->getShortName()}: " . $e->getMessage());
        }
    }

    /**
     * Muestra los resultados de la auditoría
     */
    protected function displayResults(bool $shouldFix): void
    {
        $total = count($this->safeModels) + count($this->vulnerableModels);

        $this->info("📊 Results:");
        $this->line("   ✅ Secure models: " . count($this->safeModels));
        $this->line("   ⚠️  Vulnerable models: " . count($this->vulnerableModels));
        $this->line("   🔓 Unguarded models: " . array_reduce($this->safeModels, fn($c, $m) => $c + ($m['is_unguarded'] ? 1 : 0), 0));
        $this->line("   📁 Total checked: {$total}");
        $this->newLine();

        if (!empty($this->vulnerableModels)) {
            $this->warn("Vulnerable models require attention:");
            $this->newLine();

            $headers = ['Model', 'Status', 'Action'];
            $rows = array_map(function ($model) use ($shouldFix) {
                $action = $shouldFix ? 'Auto-fixed' : 'Run with --fix';
                return [$model['short_name'], 'VULNERABLE', $action];
            }, $this->vulnerableModels);

            $this->table($headers, $rows);
            $this->newLine();

            if (!$shouldFix) {
                $this->info("💡 Run 'php artisan security:check-mass-assignment --fix' to auto-add \$fillable");
            }
        }

        if (!empty(array_filter($this->safeModels, fn($m) => $m['is_unguarded']))) {
            $this->warn("\n⚠️  Models using Model::unguard() should be reviewed:");
            $unguarded = array_filter($this->safeModels, fn($m) => $m['is_unguarded']);

            foreach ($unguarded as $model) {
                $this->line("   - {$model['short_name']}");
            }
        }
    }

    /**
     * Obtiene el nombre de la clase desde un archivo
     */
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        $namespace = '';
        $className = '';

        // Extraer namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extraer nombre de clase
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
        }

        return $className ? "{$namespace}\\{$className}" : null;
    }
}
