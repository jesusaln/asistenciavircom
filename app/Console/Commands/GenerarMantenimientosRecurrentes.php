<?php

namespace App\Console\Commands;

use App\Models\Mantenimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerarMantenimientosRecurrentes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mantenimientos:generar-recurrentes {--dry-run : Ejecutar en modo prueba sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera automáticamente mantenimientos recurrentes para los vencidos (Legacy - la funcionalidad ahora está integrada)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando generación de mantenimientos recurrentes...');

        // Obtener mantenimientos vencidos
        $mantenimientosVencidos = Mantenimiento::where('proximo_mantenimiento', '<', now())
            ->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO)
            ->with('carro')
            ->get();

        if ($mantenimientosVencidos->isEmpty()) {
            $this->info('✅ No hay mantenimientos vencidos para procesar.');
            return 0;
        }

        $this->info("📋 Encontrados {$mantenimientosVencidos->count()} mantenimientos vencidos:");

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠️  Ejecutando en modo DRY RUN - No se harán cambios reales');
        }

        $creados = 0;
        $errores = 0;

        foreach ($mantenimientosVencidos as $mantenimiento) {
            try {
                $this->line("  • Procesando: {$mantenimiento->tipo} - {$mantenimiento->carro->marca} {$mantenimiento->carro->modelo}");

                if (!$dryRun) {
                    // Calcular el intervalo entre la fecha actual y la próxima
                    if ($mantenimiento->fecha && $mantenimiento->proximo_mantenimiento) {
                        $intervaloDias = $mantenimiento->fecha->diffInDays($mantenimiento->proximo_mantenimiento);

                        // Crear nuevo mantenimiento
                        $nuevoMantenimiento = Mantenimiento::create([
                            'carro_id' => $mantenimiento->carro_id,
                            'tipo' => $mantenimiento->tipo,
                            'fecha' => $mantenimiento->proximo_mantenimiento, // La fecha vencida se convierte en la fecha del servicio
                            'proximo_mantenimiento' => now()->addDays($intervaloDias), // Próximo mantenimiento
                            'descripcion' => $mantenimiento->descripcion,
                            'notas' => $mantenimiento->notas,
                            'costo' => $mantenimiento->costo,
                            'estado' => Mantenimiento::ESTADO_PENDIENTE, // Nuevo mantenimiento como pendiente
                            'kilometraje_actual' => $mantenimiento->carro->kilometraje ?? $mantenimiento->kilometraje_actual,
                            'proximo_kilometraje' => $mantenimiento->proximo_kilometraje,
                            'prioridad' => $mantenimiento->prioridad,
                            'dias_anticipacion_alerta' => $mantenimiento->dias_anticipacion_alerta,
                            'requiere_aprobacion' => $mantenimiento->requiere_aprobacion,
                            'observaciones_alerta' => $mantenimiento->observaciones_alerta,
                        ]);

                        // Marcar el mantenimiento anterior como completado
                        $mantenimiento->update([
                            'estado' => Mantenimiento::ESTADO_COMPLETADO,
                            'notas' => ($mantenimiento->notas ? $mantenimiento->notas . ' | ' : '') . 'Mantenimiento completado automáticamente - generado nuevo registro ID: ' . $nuevoMantenimiento->id
                        ]);

                        $creados++;
                        $this->line("    ✅ Creado nuevo mantenimiento ID: {$nuevoMantenimiento->id}");
                    } else {
                        $this->warn("    ⚠️  No se puede calcular el intervalo para el mantenimiento ID: {$mantenimiento->id}");
                        $errores++;
                    }
                } else {
                    $this->line("    🔍 Simulación: Se crearía nuevo mantenimiento");
                    $creados++;
                }

            } catch (\Exception $e) {
                $this->error("    ❌ Error procesando mantenimiento ID {$mantenimiento->id}: {$e->getMessage()}");
                Log::error('Error en GenerarMantenimientosRecurrentes', [
                    'mantenimiento_id' => $mantenimiento->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errores++;
            }
        }

        $this->newLine();
        $this->info("📊 Resumen de la operación:");
        $this->line("  ✅ Mantenimientos procesados: {$creados}");
        if ($errores > 0) {
            $this->error("  ❌ Errores: {$errores}");
        }

        if (!$dryRun) {
            $this->warn("⚠️  ¡Proceso completado! Se generaron {$creados} mantenimientos recurrentes (Legacy).");
            $this->info("💡 Nota: La generación de mantenimientos recurrentes ahora es automática al crear y completar servicios.");
        } else {
            $this->warn("⚠️  ¡Simulación completada! Se simularon {$creados} mantenimientos recurrentes (Legacy).");
            $this->info("💡 Nota: La generación de mantenimientos recurrentes ahora es automática al crear y completar servicios.");
        }

        return $creados;
    }
}
