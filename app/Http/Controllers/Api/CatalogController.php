<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Models\SatEstado;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Models\Almacen;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class CatalogController extends Controller
{
    private const CACHE_TTL = 60;

    /**
     * Obtener regímenes fiscales
     */
    public function regimenesFiscales(): JsonResponse
    {
        try {
            $regimenes = Cache::remember('api_regimenes_fiscales', self::CACHE_TTL, function () {
                return SatRegimenFiscal::orderBy('clave')
                    ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral'])
                    ->map(function ($regimen) {
                        return [
                            'value' => $regimen->clave,
                            'label' => "{$regimen->clave} - {$regimen->descripcion}",
                            'clave' => $regimen->clave,
                            'descripcion' => $regimen->descripcion,
                            'persona_fisica' => $regimen->persona_fisica,
                            'persona_moral' => $regimen->persona_moral,
                        ];
                    });
            });

            return response()->json($regimenes);
        } catch (Exception $e) {
            Log::error('Error obteniendo regímenes fiscales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar regímenes fiscales'
            ], 500);
        }
    }

    /**
     * Obtener usos de CFDI
     */
    public function usosCfdi(): JsonResponse
    {
        try {
            $usos = Cache::remember('api_usos_cfdi', self::CACHE_TTL, function () {
                return SatUsoCfdi::where('activo', true)
                    ->orderBy('clave')
                    ->get(['clave', 'descripcion'])
                    ->map(function ($uso) {
                        return [
                            'value' => $uso->clave,
                            'label' => "{$uso->clave} - {$uso->descripcion}",
                            'clave' => $uso->clave,
                            'descripcion' => $uso->descripcion,
                        ];
                    });
            });

            return response()->json($usos);
        } catch (Exception $e) {
            Log::error('Error obteniendo usos CFDI: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar usos CFDI'
            ], 500);
        }
    }

    /**
     * Obtener estados de México
     */
    public function estados(): JsonResponse
    {
        try {
            $estados = Cache::remember('api_estados_mexico', self::CACHE_TTL, function () {
                return SatEstado::orderBy('nombre')
                    ->get(['clave', 'nombre'])
                    ->map(function ($estado) {
                        return [
                            'value' => $estado->clave,
                            'label' => $estado->nombre,
                            'clave' => $estado->clave,
                            'nombre' => $estado->nombre,
                        ];
                    });
            });

            return response()->json($estados);
        } catch (Exception $e) {
            Log::error('Error obteniendo estados: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar estados'
            ], 500);
        }
    }

    /**
     * Obtener todos los catálogos en una sola llamada
     */
    public function all(): JsonResponse
    {
        try {
            $catalogs = [
                'tiposPersona' => [
                    ['value' => 'fisica', 'label' => 'Persona Física'],
                    ['value' => 'moral', 'label' => 'Persona Moral'],
                ],
                'regimenesFiscales' => $this->getRegimenesFiscalesArray(),
                'usosCfdi' => $this->getUsosCfdiArray(),
                'estados' => $this->getEstadosArray(),
                // Product catalogs
                'categorias' => $this->getCategoriasArray(),
                'marcas' => $this->getMarcasArray(),
                'proveedores' => $this->getProveedoresArray(),
                'almacenes' => $this->getAlmacenesArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $catalogs
            ]);
        } catch (Exception $e) {
            Log::error('Error obteniendo catálogos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar catálogos'
            ], 500);
        }
    }

    // Métodos privados para reutilizar lógica
    private function getRegimenesFiscalesArray(): array
    {
        return Cache::remember('api_regimenes_fiscales', self::CACHE_TTL, function () {
            return SatRegimenFiscal::orderBy('clave')
                ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral'])
                ->map(function ($regimen) {
                    return [
                        'value' => $regimen->clave,
                        'label' => "{$regimen->clave} - {$regimen->descripcion}",
                        'clave' => $regimen->clave,
                        'descripcion' => $regimen->descripcion,
                        'persona_fisica' => $regimen->persona_fisica,
                        'persona_moral' => $regimen->persona_moral,
                    ];
                })->toArray();
        });
    }

    private function getUsosCfdiArray(): array
    {
        return Cache::remember('api_usos_cfdi', self::CACHE_TTL, function () {
            return SatUsoCfdi::where('activo', true)
                ->orderBy('clave')
                ->get(['clave', 'descripcion'])
                ->map(function ($uso) {
                    return [
                        'value' => $uso->clave,
                        'label' => "{$uso->clave} - {$uso->descripcion}",
                        'clave' => $uso->clave,
                        'descripcion' => $uso->descripcion,
                    ];
                })->toArray();
        });
    }

    private function getEstadosArray(): array
    {
        return Cache::remember('api_estados_mexico', self::CACHE_TTL, function () {
            return SatEstado::orderBy('nombre')
                ->get(['clave', 'nombre'])
                ->map(function ($estado) {
                    return [
                        'value' => $estado->clave,
                        'label' => $estado->nombre,
                        'clave' => $estado->clave,
                        'nombre' => $estado->nombre,
                    ];
                })->toArray();
        });
    }

    private function getCategoriasArray(): array
    {
        return Cache::remember('api_categorias_productos', self::CACHE_TTL, function () {
            return Categoria::where('estado', 'activo')
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])
                ->toArray();
        });
    }

    private function getMarcasArray(): array
    {
        return Cache::remember('api_marcas_productos', self::CACHE_TTL, function () {
            return Marca::where('estado', 'activo')
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn($m) => ['id' => $m->id, 'nombre' => $m->nombre])
                ->toArray();
        });
    }

    private function getProveedoresArray(): array
    {
        return Cache::remember('api_proveedores', self::CACHE_TTL, function () {
            return Proveedor::where('estado', 'activo')
                ->orderBy('nombre_razon_social')
                ->get(['id', 'nombre_razon_social'])
                ->map(fn($p) => ['id' => $p->id, 'nombre_razon_social' => $p->nombre_razon_social])
                ->toArray();
        });
    }

    private function getAlmacenesArray(): array
    {
        return Cache::remember('api_almacenes', self::CACHE_TTL, function () {
            return Almacen::where('estado', 'activo')
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn($a) => ['id' => $a->id, 'nombre' => $a->nombre])
                ->toArray();
        });
    }
}
