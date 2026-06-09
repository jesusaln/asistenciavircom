<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index()
    {
        try {
            $tecnicos = \App\Models\User::tecnicos()
                ->where(function ($query) {
                    $query->where('activo', true)->orWhereNull('activo');
                })
                ->select('id', 'name as nombre', 'email', 'activo') // name replaces nombre/apellido in unified model
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tecnicos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los técnicos: ' . $e->getMessage()
            ], 500);
        }
    }
}
