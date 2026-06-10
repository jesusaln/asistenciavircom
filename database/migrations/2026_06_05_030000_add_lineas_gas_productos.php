<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoriaId = DB::table('categorias')->where('empresa_id', 8)
            ->where('nombre', 'Accesorios')->value('id')
            ?? DB::table('categorias')->where('empresa_id', 8)->value('id');

        $marcaId = DB::table('marcas')->where('empresa_id', 8)->value('id');
        $proveedorId = DB::table('proveedores')->where('empresa_id', 8)->value('id');
        $almacenId = DB::table('almacenes')->where('empresa_id', 8)->value('id');

        $productos = [
            [
                'nombre' => 'Línea excedente de gas para minisplit 1 Ton (instalada)',
                'precio_venta' => 818.97, // 950 con IVA
            ],
            [
                'nombre' => 'Línea excedente de gas para minisplit 2 Ton (instalada)',
                'precio_venta' => 1077.59, // 1250 con IVA
            ],
        ];

        foreach ($productos as $p) {
            $exists = DB::table('productos')->where('empresa_id', 8)
                ->where('nombre', $p['nombre'])->exists();
            if (!$exists) {
                DB::table('productos')->insert([
                    'empresa_id' => 8,
                    'categoria_id' => $categoriaId,
                    'marca_id' => $marcaId,
                    'proveedor_id' => $proveedorId,
                    'almacen_id' => $almacenId,
                    'nombre' => $p['nombre'],
                    'descripcion' => '',
                    'precio_venta' => $p['precio_venta'],
                    'precio_compra' => $p['precio_venta'] * 0.7,
                    'unidad_medida' => 'Pieza',
                    'tipo_producto' => 'servicio',
                    'incluye_iva' => false,
                    'estado' => 'activo',
                    'catalogo_web' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('productos')->where('empresa_id', 8)
            ->whereIn('nombre', [
                'Línea excedente de gas para minisplit 1 Ton (instalada)',
                'Línea excedente de gas para minisplit 2 Ton (instalada)',
            ])->delete();
    }
};
