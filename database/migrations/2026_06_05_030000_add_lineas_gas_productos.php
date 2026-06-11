<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')->value('id') ?? 1;

        $categoriaId = DB::table('categorias')->where('empresa_id', $empresaId)
            ->where('nombre', 'Accesorios')->value('id')
            ?? DB::table('categorias')->where('empresa_id', $empresaId)->value('id');
        if (!$categoriaId) {
            $categoriaId = DB::table('categorias')->insertGetId([
                'empresa_id' => $empresaId,
                'nombre' => 'ACCESORIOS',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $marcaId = DB::table('marcas')->where('empresa_id', $empresaId)->value('id');
        if (!$marcaId) {
            $marcaId = DB::table('marcas')->insertGetId([
                'empresa_id' => $empresaId,
                'nombre' => 'GENERICO',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $proveedorId = DB::table('proveedores')->where('empresa_id', $empresaId)->value('id');
        if (!$proveedorId) {
            $proveedorId = DB::table('proveedores')->insertGetId([
                'empresa_id' => $empresaId,
                'nombre_razon_social' => 'PROVEEDOR GENERICO',
                'rfc' => 'XAXX010101000',
                'tipo_persona' => 'fisica',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $almacenId = DB::table('almacenes')->where('empresa_id', $empresaId)->value('id');
        if (!$almacenId) {
            $almacenId = DB::table('almacenes')->insertGetId([
                'empresa_id' => $empresaId,
                'nombre' => 'ALMACEN PRINCIPAL',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $productos = [
            [
                'nombre' => 'Línea excedente de gas para minisplit 1 Ton (instalada)',
                'codigo' => 'LINEA-GAS-1TON',
                'precio_venta' => 818.97, // 950 con IVA
            ],
            [
                'nombre' => 'Línea excedente de gas para minisplit 2 Ton (instalada)',
                'codigo' => 'LINEA-GAS-2TON',
                'precio_venta' => 1077.59, // 1250 con IVA
            ],
        ];

        foreach ($productos as $p) {
            $exists = DB::table('productos')->where('empresa_id', $empresaId)
                ->where('nombre', $p['nombre'])->exists();
            if (!$exists) {
                DB::table('productos')->insert([
                    'empresa_id' => $empresaId,
                    'categoria_id' => $categoriaId,
                    'marca_id' => $marcaId,
                    'proveedor_id' => $proveedorId,
                    'almacen_id' => $almacenId,
                    'nombre' => $p['nombre'],
                    'codigo' => $p['codigo'],
                    'descripcion' => '',
                    'precio_venta' => $p['precio_venta'],
                    'precio_compra' => $p['precio_venta'] * 0.7,
                    'unidad_medida' => 'Pieza',
                    'tipo_producto' => 'servicio',
                    'incluye_iva' => false,
                    'estado' => 'activo',
                    'catalogo_web' => false,
                    'stock' => 0,
                    'stock_minimo' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $empresaId = DB::table('empresas')->value('id') ?? 1;

        DB::table('productos')->where('empresa_id', $empresaId)
            ->whereIn('nombre', [
                'Línea excedente de gas para minisplit 1 Ton (instalada)',
                'Línea excedente de gas para minisplit 2 Ton (instalada)',
            ])->delete();
    }
};
