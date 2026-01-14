<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrdenCompra>
 */
class OrdenCompraFactory extends Factory
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
            'numero_orden' => null, // Se genera automáticamente
            'fecha_orden' => $this->faker->date(),
            'fecha_entrega_esperada' => $this->faker->dateTimeBetween('+1 week', '+4 weeks'),
            'prioridad' => $this->faker->randomElement(['baja', 'media', 'alta', 'urgente']),
            'direccion_entrega' => $this->faker->address(),
            'terminos_pago' => $this->faker->randomElement(['contado', '15_dias', '30_dias', '45_dias', '60_dias', '90_dias']),
            'metodo_pago' => $this->faker->randomElement(['transferencia', 'cheque', 'efectivo', 'tarjeta']),
            'subtotal' => $this->faker->randomFloat(2, 100, 10000),
            'descuento_items' => $this->faker->randomFloat(2, 0, 500),
            'descuento_general' => $this->faker->randomFloat(2, 0, 200),
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
            'observaciones' => $this->faker->optional()->paragraph(),
            'estado' => $this->faker->randomElement(['pendiente', 'enviado_a_proveedor', 'procesada']),
        ];
    }
}
