<?php

return [
    // Métodos de pago que se marcan como 'recibido' automáticamente al crear la entrega
    // Estos van DIRECTO al banco (no pasan por el vendedor)
    'auto_recibido_metodos' => [
        'tarjeta',       // Tarjeta va directo al banco
        'transferencia', // Transferencia va directo al banco
    ],
];

