<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Cita;

class CitaEstadoActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cita;

    public function __construct(Cita $cita)
    {
        $this->cita = $cita->load('tecnico', 'cliente');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('notificaciones'),
        ];
    }
}
