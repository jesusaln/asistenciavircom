<?php

namespace App\Console\Commands;

use App\Models\TallerOrden;
use App\Models\UserNotification;
use Illuminate\Console\Command;

class CheckTallerAlerts extends Command
{
    protected $signature = 'taller:check-alerts';
    protected $description = 'Verifica órdenes de taller próximas a vencer o vencidas para notificar.';

    public function handle()
    {
        $this->info('Verificando alertas de taller...');

        // 1. Órdenes vencidas (fecha_compromiso < ahora y no entregado)
        $vencidas = TallerOrden::where('estado', '!=', 'entregado')
            ->whereNotNull('fecha_compromiso')
            ->where('fecha_compromiso', '<', now())
            ->get();

        foreach ($vencidas as $orden) {
            UserNotification::createTallerNotification($orden, 'atrasada');
            $this->line("Notificación de atraso para: {$orden->folio}");
        }

        // 2. Órdenes por vencer (en las próximas 24 horas)
        $proximas = TallerOrden::where('estado', '!=', 'entregado')
            ->whereNotNull('fecha_compromiso')
            ->whereBetween('fecha_compromiso', [now(), now()->addDay()])
            ->get();

        foreach ($proximas as $orden) {
            UserNotification::createTallerNotification($orden, 'proxima_entrega');
            $this->line("Notificación de próxima entrega para: {$orden->folio}");
        }

        $this->info('Proceso completado.');
    }
}
