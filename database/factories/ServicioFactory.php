<?php

namespace Database\Factories;

use App\Models\Servicio;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServicioFactory extends Factory
{
    protected $model = Servicio::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->paragraph(),
            'codigo' => $this->faker->unique()->numerify('SERV-###'),
            'precio' => $this->faker->randomFloat(2, 100, 2000),
            'duracion' => $this->faker->numberBetween(30, 240),
            'estado' => 'activo',
            'categoria_id' => Categoria::first()->id ?? Categoria::factory(),
        ];
    }
}
