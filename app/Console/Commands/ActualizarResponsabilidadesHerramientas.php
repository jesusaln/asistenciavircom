<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tecnico;
use App\Models\Herramienta;
use App\Models\ResponsabilidadHerramienta;
use App\Models\HistorialHerramienta;
use Illuminate\Support\Facades\DB;

class ActualizarResponsabilidadesHerramientas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'herramientas:actualizar-responsabilidades
                            {--migrar-datos : Migrar datos existentes al nuevo sistema}
                            {--tecnico= : Actualizar solo un técnico específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las responsabilidades de herramientas de todos los técnicos y opcionalmente migra datos existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Iniciando actualización de responsabilidades de herramientas...');

        try {
            DB::beginTransaction();

            if ($this->option('migrar-datos')) {
                $this->migrarDatosExistentes();
            }

            $tecnicoId = $this->option('tecnico');

            if ($tecnicoId) {
                $this->actualizarTecnicoEspecifico($tecnicoId);
            } else {
                $this->actualizarTodosTecnicos();
            }

            DB::commit();
            $this->info('✅ Actualización completada exitosamente!');

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('❌ Error durante la actualización: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Migrar datos existentes al nuevo sistema
     */
    private function migrarDatosExistentes()
    {
        $this->info('📦 Migrando datos existentes...');

        // Migrar asignaciones individuales existentes al historial
        $herramientasAsignadas = Herramienta::whereNotNull('tecnico_id')
                                           ->where('estado', 'asignada')
                                           ->get();

        $this->info("📋 Encontradas {$herramientasAsignadas->count()} herramientas asignadas para migrar");

        $bar = $this->output->createProgressBar($herramientasAsignadas->count());
        $bar->start();

        foreach ($herramientasAsignadas as $herramienta) {
            // Verificar si ya existe un registro en el historial
            $existeHistorial = HistorialHerramienta::where('herramienta_id', $herramienta->id)
                                                  ->where('tecnico_id', $herramienta->tecnico_id)
                                                  ->whereNull('fecha_devolucion')
                                                  ->exists();

            if (!$existeHistorial) {
                HistorialHerramienta::create([
                    'herramienta_id' => $herramienta->id,
                    'tecnico_id' => $herramienta->tecnico_id,
                    'fecha_asignacion' => $herramienta->fecha_asignacion ?? $herramienta->created_at,
                    'asignado_por' => 1, // Usuario admin por defecto
                    'observaciones_asignacion' => 'Migración de datos existentes',
                    'tipo_asignacion' => 'individual'
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Migración de datos completada');
    }

    /**
     * Actualizar responsabilidades de todos los técnicos
     */
    private function actualizarTodosTecnicos()
    {
        $tecnicos = Tecnico::where('activo', true)->get();
        $this->info("👥 Actualizando responsabilidades para {$tecnicos->count()} técnicos activos...");

        $bar = $this->output->createProgressBar($tecnicos->count());
        $bar->start();

        foreach ($tecnicos as $tecnico) {
            ResponsabilidadHerramienta::actualizarParaTecnico($tecnico->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Actualizar responsabilidades de un técnico específico
     */
    private function actualizarTecnicoEspecifico($tecnicoId)
    {
        $tecnico = Tecnico::find($tecnicoId);

        if (!$tecnico) {
            $this->error("❌ Técnico con ID {$tecnicoId} no encontrado");
            return;
        }

        $this->info("👤 Actualizando responsabilidades para {$tecnico->nombre} {$tecnico->apellido}...");

        $responsabilidad = ResponsabilidadHerramienta::actualizarParaTecnico($tecnico->id);

        $this->info("✅ Responsabilidades actualizadas:");
        $this->line("   - Herramientas asignadas: {$responsabilidad->total_herramientas}");
        $this->line("   - Valor total: $" . number_format($responsabilidad->valor_total_herramientas, 2));
        $this->line("   - Herramientas vencidas: " . ($responsabilidad->tiene_herramientas_vencidas ? 'Sí' : 'No'));
        $this->line("   - Promedio días uso: {$responsabilidad->dias_promedio_uso}");
    }
}
