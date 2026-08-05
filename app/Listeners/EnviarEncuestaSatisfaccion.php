<?php

namespace App\Listeners;

use App\Events\CitaCompletada;
use App\Models\Cita;
use App\Models\EncuestaSatisfaccion;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EnviarEncuestaSatisfaccion implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CitaCompletada $event): void
    {
        $cita = $event->cita;
        if (!$this->esInstalacion($cita) || !$cita->cliente?->telefono) {
            return;
        }

        $waId = WhatsAppService::canonicalWaId((string) $cita->cliente->telefono);
        $encuesta = EncuestaSatisfaccion::firstOrCreate([
            'cita_id' => $cita->id,
        ], [
            'empresa_id' => $cita->empresa_id,
            'cliente_id' => $cita->cliente_id,
            'wa_id' => $waId,
            'origen' => 'whatsapp',
        ]);

        if ($encuesta->respondida_at) {
            return;
        }

        Cache::put("whatsapp_menu_state_{$cita->empresa_id}_{$waId}", 'satisfaccion_calificacion', now()->addDays(3));
        Cache::put("whatsapp_menu_state_{$cita->empresa_id}_{$waId}_encuesta_id", $encuesta->id, now()->addDays(3));

        $empresa = Empresa::find($cita->empresa_id);
        if (!$empresa || !$empresa->whatsapp_enabled) {
            return;
        }

        $brand = EmpresaConfiguracion::query()->find($cita->empresa_id)?->nombre_empresa
            ?: $empresa->nombre_razon_social
            ?: config('app.name', 'Servicio');

        try {
            WhatsAppService::fromEmpresa($empresa)->sendTextMessage($waId,
                "Hola {$cita->cliente->nombre_razon_social}, gracias por elegir {$brand}.\n\n" .
                "Queremos conocer tu experiencia. Responde con una calificación del 1 al 5, donde 5 es la mejor experiencia."
            );
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('No se pudo enviar encuesta de satisfacción', [
                'empresa_id' => $cita->empresa_id,
                'cita_id' => $cita->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function esInstalacion(Cita $cita): bool
    {
        if (mb_strtolower(trim((string) $cita->tipo_servicio)) === 'instalacion') {
            return true;
        }

        return $cita->items()
            ->with('citable')
            ->get()
            ->contains(fn ($item) => $item->citable?->es_instalacion === true);
    }
}
