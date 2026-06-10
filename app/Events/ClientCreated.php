<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCreated
{
    use Dispatchable, SerializesModels;

    public $cliente;

    public function __construct($cliente)
    {
        $this->cliente = $cliente;
    }
}
