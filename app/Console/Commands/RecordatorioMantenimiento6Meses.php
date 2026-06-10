<?php

namespace App\Console\Commands;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecordatorioMantenimiento6Meses extends Command
{
    protected $signature = 'whatsapp:recordatorio-6meses
                            {--empresa_id= : ID de empresa específica (opcional)}';

    protected $description = 'Envía recordatorio por WhatsApp a clientes que cumplieron 6 meses desde su instalación';

    public function handle(): void
    {
        $this->info('Buscando instalaciones con 6 meses de antigüedad...');

        $empresaId = $this->option('empresa_id');
        $query = Cita::query()
            ->where('tipo_servicio', 'instalacion')
            ->whereIn('estado', ['completado', 'programado', 'confirmado'])
            ->whereNotNull('cliente_id')
            ->with(['cliente', 'empresa']);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $citas = $query->get();

// Saltar clientes que ya solicitaron baja
$citas = $citas->filter(fn($c) => $c->cliente && !$c->cliente->opt_out_at);
        $enviados = 0;
        $saltados = 0;

        foreach ($citas as $cita) {
            $fechaInstalacion = $cita->fecha_hora ?? $cita->created_at;
            if (!$fechaInstalacion) continue;

            $diasDesdeInstalacion = $fechaInstalacion->diffInDays(now());
            $seisMeses = 180;

            if ($diasDesdeInstalacion < $seisMeses) {
                continue;
            }

            $cliente = $cita->cliente;
            if (!$cliente || !$cliente->telefono) {
                $saltados++;
                continue;
            }

            $cacheKey = "recordatorio_6meses_{$cita->id}";
            if (Cache::has($cacheKey)) {
                $saltados++;
                continue;
            }

            $telefono = preg_replace('/[^0-9]/', '', $cliente->telefono);
            if (strlen($telefono) < 10) {
                $saltados++;
                continue;
            }

            $telE164 = '52' . substr($telefono, -10);
            $nombre = $cliente->nombre_razon_social ?: 'cliente';

            try {
                $empresa = $cita->empresa ?? Empresa::find($cita->empresa_id);
                if (!$empresa || !$empresa->whatsapp_enabled) {
                    $saltados++;
                    continue;
                }

                $whatsapp = WhatsAppService::fromEmpresa($empresa);

                $mensaje = "🌵 *Climas del Desierto*\n\n" .
                    "Hola *{$nombre}*! 👋\n\n" .
                    "Hace {$diasDesdeInstalacion} días realizamos la instalación de tu equipo y queremos asegurarnos de que siga funcionando al 100% 💪❄️\n\n" .
                    "Recuerda que para mantener tu *garantía de fábrica vigente*, es necesario realizar un *mantenimiento preventivo* cada 6 meses.\n\n" .
                    "¿Te gustaría agendar tu mantenimiento? Solo responde *mantenimiento* y con gusto te ayudo a elegir el día que más te acomode. 📅\n\n" .
                    "Si ya no deseas recibir mensajes, responde *BAJA* y te daremos de baja automáticamente. 👍\n\n" .
                    "🌐 *Climas del Desierto* — Tu climatización, nuestra prioridad.";

                $whatsapp->sendTextMessage($telE164, $mensaje);

                Cache::put($cacheKey, true, now()->addDays(60));
                $enviados++;

                $this->info("Recordatorio enviado a {$nombre} ({$telE164}) — Instalación: {$fechaInstalacion->format('d/m/Y')}");

            } catch (\Throwable $e) {
                Log::error("Error enviando recordatorio 6 meses a {$telE164}: " . $e->getMessage());
                $this->error("Error con {$nombre}: {$e->getMessage()}");
            }
        }

        $this->info("Completado. Enviados: {$enviados}, Saltados: {$saltados}");
    }
}
