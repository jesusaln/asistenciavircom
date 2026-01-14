<?php

namespace Database\Factories;

use App\Models\Almacen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlmacenFactory extends Factory
{
    protected $model = Almacen::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->unique()->word . ' Warehouse',
            'descripcion' => $this->faker->sentence,
            'ubicacion' => $this->faker->city,
            'direccion' => $this->faker->address,
            'telefono' => $this->faker->phoneNumber,
            'responsable' => User::factory(),
            'estado' => 'activo',
        ];
    }
}
