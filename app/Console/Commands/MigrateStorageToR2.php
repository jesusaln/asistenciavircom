<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateStorageToR2 extends Command
{
    protected $signature = 'storage:migrate-to-r2
                            {--dry-run : Solo mostrar lo que se migraría sin hacer nada}
                            {--dir=* : Directorios específicos a migrar (ej: citas empresas)}';

    protected $description = 'Migra archivos del disco local (public) a Cloudflare R2';

    public function handle(): int
    {
        $dirs = $this->option('dir') ?: ['citas', 'empresas', 'clientes', 'polizas', 'rentas'];
        $dryRun = $this->option('dry-run');

        $localDisk = Storage::disk('public');
        $r2Disk = Storage::disk('r2');

        $totalFiles = 0;
        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($dirs as $dir) {
            $this->info("📁 Escaneando {$dir}/...");
            $files = $localDisk->allFiles($dir);

            if (empty($files)) {
                $this->line("   └─ Sin archivos");
                continue;
            }

            $totalFiles += count($files);
            $this->line("   └─ " . count($files) . " archivos encontrados");

            foreach ($files as $file) {
                // Saltar directorios (allFiles solo devuelve archivos, pero por seguridad)
                if (str_ends_with($file, '/')) {
                    continue;
                }

                // Verificar si ya existe en R2
                if (!$dryRun && $r2Disk->exists($file)) {
                    $this->line("   ⏭️  {$file} (ya existe en R2)");
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("   🔍 {$file}");
                    continue;
                }

                try {
                    // Leer del disco local y escribir en R2
                    $content = $localDisk->get($file);
                    
                    if ($content === null) {
                        $this->warn("   ⚠️  {$file} (no se pudo leer)");
                        $errors++;
                        continue;
                    }

                    $r2Disk->put($file, $content, 'public');
                    $this->line("   ✅ {$file}");
                    $migrated++;
                } catch (\Exception $e) {
                    $this->error("   ❌ {$file}: " . $e->getMessage());
                    $errors++;
                }
            }
        }

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->line("   Total:    {$totalFiles}");
        $this->line("   Migrados: {$migrated}");
        $this->line("   Saltados: {$skipped}");
        $this->line("   Errores:  {$errors}");

        return 0;
    }
}
