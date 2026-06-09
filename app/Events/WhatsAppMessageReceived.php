<?php

namespace App\Events;

use App\Models\WhatsAppChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(WhatsAppChat $message)
    {
        $this->message = $message->load('user:id,name');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('empresa.' . $this->message->empresa_id . '.whatsapp'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }
}
