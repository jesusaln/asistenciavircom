<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Models\Almacen;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->paragraph(),
            'codigo' => $this->faker->unique()->numerify('PROD-###'),
            'codigo_barras' => $this->faker->unique()->ean13(),
            'numero_serie' => $this->faker->optional()->bothify('SN#####'),
            'categoria_id' => Categoria::first()->id ?? Categoria::factory(),
            'marca_id' => Marca::first()->id ?? Marca::factory(),
            'proveedor_id' => Proveedor::first()->id ?? Proveedor::factory(),
            'almacen_id' => Almacen::first()->id ?? Almacen::factory(),
            'stock' => $this->faker->numberBetween(10, 500),
            'stock_minimo' => $this->faker->numberBetween(1, 50),
            'precio_compra' => $this->faker->randomFloat(2, 10, 500),
            'precio_venta' => $this->faker->randomFloat(2, 20, 800),

            'unidad_medida' => 'Pieza',
            'fecha_vencimiento' => $this->faker->optional()->date(),
            'tipo_producto' => 'fisico',
            'imagen' => $this->faker->optional()->imageUrl(400, 400, 'product'),
            'estado' => 'activo',
        ];
    }
}
