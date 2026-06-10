<?php

namespace Database\Factories;

use App\Models\SatEstado;
use Illuminate\Database\Eloquent\Factories\Factory;

class SatEstadoFactory extends Factory
{
    protected $model = SatEstado::class;

    public function definition(): array
    {
        $claves = ['AGU', 'BCN', 'BCS', 'CAMP', 'COAH', 'COL', 'CDMX', 'CHIS', 'CHIH', 'DUR', 'GTO', 'HGO', 'JAL', 'MEX', 'MICH', 'MOR', 'NAY', 'NL', 'OAX', 'PUE', 'QR', 'QRO', 'ROO', 'SLP', 'SIN', 'SON', 'TAB', 'TAMPS', 'TLAX', 'VER', 'YUC', 'ZAC'];

        return [
            'clave' => $this->faker->randomElement($claves),
            'nombre' => $this->faker->city,
        ];
    }
}
