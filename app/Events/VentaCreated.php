<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VentaCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Venta $venta
    ) {}
}
