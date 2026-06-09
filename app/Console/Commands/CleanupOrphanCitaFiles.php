<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Cita;
use Illuminate\Support\Facades\Log;

class CleanupOrphanCitaFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'citas:cleanup-orphans {--dry-run : Solo mostrar archivos a eliminar sin borrarlos}';

    /**
     * The console command description.
     */
    protected $description = 'Elimina archivos físicos de almacenamiento que no están referenciados por ninguna cita en la base de datos (Resiliencia #610).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directories = ['citas', 'citas/evidencias_finales', 'citas/firmas'];
        $count = 0;
        $dryRun = $this->option('dry-run');

        foreach ($directories as $dir) {
            $files = Storage::disk('public')->files($dir);
            $this->info("Escaneando directorio: {$dir} (" . count($files) . " archivos)");

            foreach ($files as $file) {
                // Verificar si el archivo está en alguna de las columnas de la tabla citas
                $exists = Cita::where('foto_equipo', $file)
                    ->orWhere('foto_hoja_servicio', $file)
                    ->orWhere('foto_identificacion', $file)
                    ->orWhere('firma_cliente', $file)
                    ->orWhere('firma_tecnico', $file)
                    ->orWhereJsonContains('fotos_finales', $file)
                    ->exists();

                if (!$exists) {
                    $this->warn("Archivo huérfano detectado: {$file}");
                    
                    if (!$dryRun) {
                        Storage::disk('public')->delete($file);
                        Log::info("Limpieza automática: Archivo huérfano {$file} eliminado.");
                    }
                    $count++;
                }
            }
        }

        $this->info("Proceso completado. Archivos huérfanos: {$count} (" . ($dryRun ? 'NO ELIMINADOS' : 'ELIMINADOS') . ").");
    }
}
