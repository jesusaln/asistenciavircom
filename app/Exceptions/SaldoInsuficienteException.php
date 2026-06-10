<?php

namespace App\Exceptions;

use Exception;

class SaldoInsuficienteException extends Exception
{
    public $details;

    public function __construct($banco, $disponible, $requerido)
    {
        $faltante = $requerido - $disponible;
        $this->details = [
            'banco' => $banco,
            'disponible' => $disponible,
            'requerido' => $requerido,
            'faltante' => $faltante
        ];

        $msg = "Saldo insuficiente en {$banco}. Disponible: \${$disponible}, Requerido: \${$requerido}";
        parent::__construct($msg);
    }

    public function getDetails()
    {
        return $this->details;
    }
}
