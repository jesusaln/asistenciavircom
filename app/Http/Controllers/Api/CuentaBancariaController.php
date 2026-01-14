<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CuentaBancaria;
use Illuminate\Http\Request;

class CuentaBancariaController extends Controller
{
    public function activas()
    {
        $cuentas = CuentaBancaria::activas()
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'banco', 'numero_cuenta', 'saldo_actual']);

        return response()->json($cuentas);
    }
}
