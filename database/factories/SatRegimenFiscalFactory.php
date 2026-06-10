<?php

namespace Database\Factories;

use App\Models\SatRegimenFiscal;
use Illuminate\Database\Eloquent\Factories\Factory;

class SatRegimenFiscalFactory extends Factory
{
    protected $model = SatRegimenFiscal::class;

    public function definition(): array
    {
        $claves = ['601', '603', '605', '612', '616', '621', '628', '610', '620', '625', '630', '622', '624', '626', '629', '631'];

        return [
            'clave' => $this->faker->randomElement($claves),
            'descripcion' => $this->faker->sentence,
            'persona_fisica' => $this->faker->boolean,
            'persona_moral' => $this->faker->boolean,
        ];
    }
}
