<?php

namespace App\Console\Commands\Code;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class CheckValidationConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:check-validation
                            {--path= : Specific path to check}
                            {--fix : Create validation base classes for missing patterns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audita consistencia de validaciones en Requests y Controllers';

    /**
     * Requests encontrados sin validación crítica
     */
    protected array $riskyRequests = [];

    /**
     * Requests con buena implementación
     */
    protected array $goodRequests = [];

    /**
     * Controllers con validación en modelo/servicio
     */
    protected array $controllersWithServiceValidation = [];

    /**
     * Controllers SIN validación en modelo/servicio
     */
    protected array $controllersWithoutServiceValidation = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Audit Validation Consistency');
        $this->newLine();

        $this->checkRequests();
        $this->checkControllers();

        $this->displayResults();

        return count($this->riskyRequests) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Audit de todos los Requests
     */
    protected function checkRequests(): void
    {
        $requestsPath = app_path('Http/Requests');
        $files = File::allFiles($requestsPath);

        $this->info("📋 Checking " . count($files) . " Request files...");

        foreach ($files as $file) {
            $this->analyzeRequest($file->getPathname());
        }

        $this->newLine();
    }

    /**
     * Analiza un archivo Request
     */
    protected function analyzeRequest(string $path): void
    {
        $content = File::get($path);
        $className = $this->getClassNameFromFile($path);

        if (!$className || !class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);

        // Saltar clases abstractas
        if ($reflection->isAbstract()) {
            return;
        }

        // Verificar si usa ValidatedRequest
        $usesValidatedRequest = str_contains($content, 'ValidatedRequest');
        $hasCriticalRules = str_contains($content, 'getCriticalRules');
        $hasDisabledValidation = $this->hasDisabledValidation($content);

        if ($hasDisabledValidation) {
            $this->riskyRequests[] = [
                'class' => $reflection->getShortName(),
                'path' => $path,
                'issue' => 'Has disabled validation (return early, commented code)',
            ];
        } elseif (!$usesValidatedRequest && !$hasCriticalRules) {
            $this->riskyRequests[] = [
                'class' => $reflection->getShortName(),
                'path' => $path,
                'issue' => 'Does not use ValidatedRequest pattern',
            ];
        } else {
            $this->goodRequests[] = $reflection->getShortName();
        }
    }

    /**
     * Verifica si el Request tiene validación deshabilitada
     */
    protected function hasDisabledValidation(string $content): bool
    {
        // Buscar patrones comunes de validación deshabilitada
        $patterns = [
            '/return\s*;/' => 'Empty return (validation disabled)',
            '/\$validator->after\(/' => null,
            '/function\s+\w+Validation\s*\(\s*\$.*\)\s*\{/' => null,
        ];

        // Verificar si hay métodos de validación vacíos o con return temprano
        if (preg_match_all('/function\s+(?:validate|check)\w+\s*\([^)]*\)\s*\{[^}]*(return\s*;)/', $content, $matches)) {
            if (!empty($matches[0])) {
                return true;
            }
        }

        // Verificar comentarios que indican validación deshabilitada
        if (preg_match('/(REMOVED|DISABLED|COMMENTED|OPTIONAL|BUSINESS DECISION).*validation/i', $content)) {
            return true;
        }

        return false;
    }

    /**
     * Audit de Controllers para verificar validación en servicios
     */
    protected function checkControllers(): void
    {
        $controllersPath = app_path('Http/Controllers');
        $files = File::allFiles($controllersPath);

        $this->info("📋 Checking " . count($files) . " Controller files...");

        foreach ($files as $file) {
            $this->analyzeController($file->getPathname());
        }

        $this->newLine();
    }

    /**
     * Analiza un archivo Controller
     */
    protected function analyzeController(string $path): void
    {
        $content = File::get($path);
        $className = $this->getClassNameFromFile($path);

        if (!$className || !class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);
        $shortName = $reflection->getShortName();

        // Ignorar Controllers base
        if (in_array($shortName, ['Controller', 'BaseController'])) {
            return;
        }

        // Verificar si llama a servicios que validan
        $callsService = preg_match('/::(make|create|update|store|validateAndCreate|validateAndUpdate)/', $content);
        $hasInlineValidation = preg_match('/Validator::make|validate\(/', $content);

        if ($hasInlineValidation && !$callsService) {
            $this->controllersWithoutServiceValidation[] = [
                'class' => $shortName,
                'path' => $path,
                'issue' => 'Has inline validation but may not call validated services',
            ];
        } elseif ($callsService) {
            $this->controllersWithServiceValidation[] = $shortName;
        }
    }

    /**
     * Muestra los resultados
     */
    protected function displayResults(): void
    {
        $this->info("📊 Validation Audit Results:");
        $this->line("   ✅ Secure Requests: " . count($this->goodRequests));
        $this->line("   ⚠️  Risky Requests: " . count($this->riskyRequests));
        $this->line("   ✅ Controllers using services: " . count($this->controllersWithServiceValidation));
        $this->line("   ⚠️  Controllers with inline validation: " . count($this->controllersWithoutServiceValidation));
        $this->newLine();

        if (!empty($this->riskyRequests)) {
            $this->warn("🚨 Requests with potential validation issues:");
            $this->newLine();

            $headers = ['Request', 'Issue'];
            $rows = array_map(function ($request) {
                return [$request['class'], $request['issue']];
            }, $this->riskyRequests);

            $this->table($headers, $rows);
            $this->newLine();

            $this->info("💡 Recommendations:");
            $this->line("   1. Use ValidatedRequest abstract class for critical validations");
            $this->line("   2. Implement validation in services using WithValidation trait");
            $this->line("   3. Never disable validations - use feature flags instead");
            $this->line("   4. Add getCriticalRules() method to mark non-negotiable validations");
            $this->newLine();
        }

        if (!empty($this->controllersWithoutServiceValidation)) {
            $this->warn("⚠️  Controllers with inline validation patterns:");
            foreach (array_slice($this->controllersWithoutServiceValidation, 0, 5) as $controller) {
                $this->line("   - {$controller['class']}");
            }
            if (count($this->controllersWithoutServiceValidation) > 5) {
                $this->line("   ... and " . (count($this->controllersWithoutServiceValidation) - 5) . " more");
            }
            $this->newLine();
        }

        if (empty($this->riskyRequests) && empty($this->controllersWithoutServiceValidation)) {
            $this->info("✅ All validations follow best practices!");
        }
    }

    /**
     * Obtiene el nombre de clase de un archivo
     */
    protected function getClassNameFromFile(string $path): ?string
    {
        $content = File::get($path);
        $namespace = '';
        $className = '';

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
        }

        return $className ? "{$namespace}\\{$className}" : null;
    }
}
