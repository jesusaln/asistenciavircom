<?php

namespace Database\Factories;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Almacen;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'cliente_id' => Cliente::factory(),
            'almacen_id' => Almacen::factory(),
            'numero_venta' => $this->faker->unique()->numberBetween(1000, 9999),
            'fecha' => now(),
            'estado' => 'pagado',
            'subtotal' => 100,
            'iva' => 16,
            'total' => 116,
            'metodo_pago' => 'efectivo',
        ];
    }
}
