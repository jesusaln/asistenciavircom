<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Obtiene el número de elementos por página de forma segura.
     * Limita el máximo a 100 para prevenir ataques DoS por carga de datos.
     */
    protected function getPerPage(int $default = 15, int $max = 100): int
    {
        $perPage = request()->integer('per_page', $default);
        
        return (int) max(1, min($perPage, $max));
    }
}
