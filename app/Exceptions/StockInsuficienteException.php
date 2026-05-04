<?php

namespace App\Exceptions;

use Exception;

class StockInsuficienteException extends Exception
{
    protected $details;

    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
