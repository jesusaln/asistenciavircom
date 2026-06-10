<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VentaUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Venta $venta,
        public readonly array $oldData
    ) {}
}
