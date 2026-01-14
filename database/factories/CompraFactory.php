<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compra>
 */
class CompraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proveedor_id' => \App\Models\Proveedor::factory(),
            'orden_compra_id' => null,
            'numero_compra' => null, // Se genera automáticamente
            'fecha_compra' => $this->faker->date(),
            'subtotal' => $this->faker->randomFloat(2, 100, 10000),
            'descuento_general' => $this->faker->randomFloat(2, 0, 200),
            'descuento_items' => $this->faker->randomFloat(2, 0, 500),
            'iva' => function (array $attributes) {
                $subtotal = $attributes['subtotal'] - $attributes['descuento_items'] - $attributes['descuento_general'];
                $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                return round($subtotal * $ivaRate, 2);
            },
            'total' => function (array $attributes) {
                $subtotal = $attributes['subtotal'] - $attributes['descuento_items'] - $attributes['descuento_general'];
                $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
                $iva = round($subtotal * $ivaRate, 2);
                return $subtotal + $iva;
            },
            'notas' => $this->faker->optional()->paragraph(),
            'estado' => $this->faker->randomElement([\App\Enums\EstadoCompra::Procesada, \App\Enums\EstadoCompra::Cancelada]),
        ];
    }
}
