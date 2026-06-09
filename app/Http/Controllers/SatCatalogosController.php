<?php

namespace App\Http\Controllers;

use App\Models\SatClaveProdServ;
use Illuminate\Http\Request;

class SatCatalogosController extends Controller
{
    /**
     * Buscar claves de productos y servicios del SAT vía AJAX
     */
    public function buscarClaveProdServ(Request $request)
    {
        $search = $request->input('search');
        
        if (strlen($search) < 3) {
            return response()->json([]);
        }

        $resultados = SatClaveProdServ::where('clave', 'like', "%{$search}%")
            ->orWhere('descripcion', 'like', "%{$search}%")
            ->limit(15)
            ->get(['clave', 'descripcion']);

        if ($resultados->isEmpty()) {
            $fallback = $this->fallbackClaveProdServ($search);
            if (!empty($fallback)) {
                return response()->json($fallback);
            }
        }

        return response()->json($resultados);
    }

    private function fallbackClaveProdServ(string $search): array
    {
        $catalogo = [
            ['clave' => '01010101', 'descripcion' => 'No existe en el catalogo'],
            ['clave' => '43211500', 'descripcion' => 'Computadoras'],
            ['clave' => '43211508', 'descripcion' => 'Computadoras portatiles'],
            ['clave' => '43211800', 'descripcion' => 'Accesorios de computacion'],
            ['clave' => '43212110', 'descripcion' => 'Impresoras de inyeccion de tinta'],
            ['clave' => '43212105', 'descripcion' => 'Impresoras laser'],
            ['clave' => '44103100', 'descripcion' => 'Papel para impresoras o fotocopiadoras'],
            ['clave' => '52161512', 'descripcion' => 'Teclados'],
            ['clave' => '52161503', 'descripcion' => 'Ratones o mouse'],
            ['clave' => '81112100', 'descripcion' => 'Servicios de mantenimiento o reparacion de computadoras'],
            ['clave' => '81112200', 'descripcion' => 'Servicios de mantenimiento de software'],
            ['clave' => '84111506', 'descripcion' => 'Servicios de facturacion'],
        ];

        $search = trim($search);
        if ($search === '') {
            return [];
        }

        $filtered = array_filter($catalogo, function ($item) use ($search) {
            return stripos($item['clave'], $search) !== false
                || stripos($item['descripcion'], $search) !== false;
        });

        return array_values(array_slice($filtered, 0, 15));
    }
}
