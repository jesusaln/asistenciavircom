<?php

namespace Database\Factories;

use App\Models\SatUsoCfdi;
use Illuminate\Database\Eloquent\Factories\Factory;

class SatUsoCfdiFactory extends Factory
{
    protected $model = SatUsoCfdi::class;

    public function definition(): array
    {
        $claves = ['G01', 'G02', 'G03', 'I02', 'I03', 'I04', 'I05', 'I06', 'I07', 'I08'];

        return [
            'clave' => $this->faker->randomElement($claves),
            'descripcion' => $this->faker->sentence,
            'persona_fisica' => $this->faker->boolean,
            'persona_moral' => $this->faker->boolean,
        ];
    }
}
