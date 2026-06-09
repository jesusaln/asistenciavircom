<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Console\Command;

class ActualizarPlantillaWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:actualizar-plantilla
                            {plantilla : Nombre de la plantilla a usar}
                            {--empresa_id= : ID específico de empresa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar la plantilla de WhatsApp para recordatorios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plantilla = $this->argument('plantilla');
        $empresaId = $this->option('empresa_id');

        $this->info("Actualizando plantilla de WhatsApp a: {$plantilla}");

        try {
            // Obtener empresa
            if ($empresaId) {
                $empresa = Empresa::findOrFail($empresaId);
            } else {
                $empresaId = $empresaId ?: EmpresaResolver::resolveId();
                $empresa = $empresaId ? Empresa::find($empresaId) : null;

                if (!$empresa) {
                    $this->error('No se encontró ninguna empresa');
                    return 1;
                }
            }

            // Actualizar plantilla
            $empresa->whatsapp_template_payment_reminder = $plantilla;
            $empresa->save();

            $this->info("✅ Plantilla actualizada exitosamente");
            $this->line("Empresa: {$empresa->nombre_razon_social}");
            $this->line("Nueva plantilla: {$empresa->whatsapp_template_payment_reminder}");

            return 0;

        } catch (\Exception $e) {
            $this->error('Error al actualizar plantilla: ' . $e->getMessage());
            return 1;
        }
    }
}
