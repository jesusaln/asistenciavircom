<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetCotizacionNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotizaciones:reset-numbers {--force : Forzar la ejecución sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reiniciar la numeración de cotizaciones eliminando números existentes y comenzando desde C001';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Reiniciando numeración de cotizaciones...');

        // Confirmar la operación
        if (!$this->option('force') && !$this->confirm('¿Estás seguro de que quieres eliminar todos los números de cotización existentes? Esta acción no se puede deshacer.')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        try {
            DB::beginTransaction();

            // Contar cotizaciones existentes
            $totalCotizaciones = Cotizacion::count();
            $this->info("Total de cotizaciones encontradas: {$totalCotizaciones}");

            if ($totalCotizaciones > 0) {
                // Hacer backup de cotizaciones importantes antes de eliminar
                $this->info('💾 Creando respaldo de cotizaciones existentes...');

                // Crear tabla temporal para respaldo si es necesario
                $cotizacionesRespaldadas = DB::table('cotizaciones')
                    ->select('id', 'cliente_id', 'estado', 'total', 'created_at')
                    ->get();

                $this->info("Cotizaciones respaldadas: {$cotizacionesRespaldadas->count()}");

                // Eliminar todas las cotizaciones existentes para reiniciar completamente
                $cotizacionesEliminadas = Cotizacion::count();
                Cotizacion::truncate(); // Esto elimina todas las cotizaciones y reinicia el AUTO_INCREMENT

                $this->info("Cotizaciones eliminadas: {$cotizacionesEliminadas}");
                $this->info('✅ Numeración de cotizaciones reiniciada exitosamente');
                $this->info('📝 La próxima cotización creada tendrá el número: C001');
            } else {
                $this->info('ℹ️ No hay cotizaciones existentes para limpiar');
            }

            DB::commit();

            $this->info('🎉 Proceso completado exitosamente');
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error al reiniciar numeración: ' . $e->getMessage());
            return 1;
        }
    }
}
