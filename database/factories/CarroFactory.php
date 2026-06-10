<?php

namespace Database\Factories;

use App\Models\Carro;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarroFactory extends Factory
{
    protected $model = Carro::class;

    public function definition(): array
    {
        $marcas = ['Toyota', 'Nissan', 'Ford', 'Chevrolet', 'Honda', 'Mazda', 'Volkswagen', 'Hyundai'];
        $modelos = ['Sedan', 'SUV', 'Pickup', 'Hatchback', 'Camioneta'];
        $colores = ['Blanco', 'Negro', 'Rojo', 'Azul', 'Gris', 'Plateado'];

        return [
            'marca' => $this->faker->randomElement($marcas),
            'modelo' => $this->faker->randomElement($modelos),
            'anio' => $this->faker->numberBetween(2015, 2025),
            'color' => $this->faker->randomElement($colores),
            'precio' => $this->faker->randomFloat(2, 100000, 800000),
            'kilometraje' => $this->faker->numberBetween(10000, 150000),
            'placa' => strtoupper($this->faker->bothify('???-###')),
            'numero_serie' => strtoupper($this->faker->bothify('?????????????????')),
            'combustible' => $this->faker->randomElement(['Gasolina', 'Diésel']),
            'activo' => true,
        ];
    }
}
