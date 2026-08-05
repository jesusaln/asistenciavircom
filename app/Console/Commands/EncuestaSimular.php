<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Models\EncuestaSatisfaccion;
use App\Services\EncuestaSatisfaccionService;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EncuestaSimular extends Command
{
    protected $signature = 'encuesta:simular
                            {cita_id : ID de la cita de instalación para simular}
                            {--inmediato : Saltar el delay de 24h y enviar ya}';

    protected $description = 'Simula el flujo de encuesta de satisfacción para una cita de instalación completada.';

    public function handle(): int
    {
        $citaId = (int) $this->argument('cita_id');
        $cita = Cita::find($citaId);

        if (! $cita) {
            $this->error("Cita {$citaId} no encontrada.");
            return self::FAILURE;
        }

        if ($cita->tipo_servicio !== 'instalacion') {
            $this->warn("La cita {$cita->folio} es de tipo '{$cita->tipo_servicio}', no 'instalacion'. Continuando de todos modos...");
        }

        $this->info("Cita: {$cita->folio}");
        $this->info("Estado actual: {$cita->estado}");
        $this->info("Cliente: ".($cita->cliente?->nombre_razon_social ?? 'N/A'));
        $this->info("Empresa: {$cita->empresa_id}");

        EmpresaResolver::setContext($cita->empresa_id);

        try {
            $service = new EncuestaSatisfaccionService;
            $encuesta = $service->crearParaCita($cita);

            if (! $encuesta) {
                $this->error('No se pudo crear la encuesta. ¿El cliente tiene WhatsApp registrado?');
                return self::FAILURE;
            }

            $this->info("Encuesta creada: {$encuesta->folio}");
            $this->info("WA destino: {$encuesta->wa_id}");

            if ($this->option('inmediato')) {
                $encuesta->update(['programada_para' => now()->subMinute()]);
                $enviada = $service->enviarMensajeInicial($encuesta->fresh());
                if ($enviada) {
                    $this->info('✓ Mensaje inicial enviado correctamente.');
                } else {
                    $this->error('✗ No se pudo enviar el mensaje inicial. Revisa logs/laravel.log.');
                    return self::FAILURE;
                }
            } else {
                $this->info("Programada para enviar: {$encuesta->programada_para->format('d/m/Y H:i:s')}");
                $this->info("Para enviar YA, usa --inmediato");
            }

            return self::SUCCESS;
        } finally {
            EmpresaResolver::clearContext();
        }
    }
}