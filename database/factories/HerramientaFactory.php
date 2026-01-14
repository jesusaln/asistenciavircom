<?php

namespace Database\Factories;

use App\Models\Tecnico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Herramienta>
 */
class HerramientaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $herramientas = [
            'Taladro inalámbrico',
            'Sierra circular',
            'Martillo neumático',
            'Soldador eléctrico',
            'Generador portátil',
            'Compresor de aire',
            'Llave de impacto',
            'Multímetro digital',
            'Nivel láser',
            'Cortadora de plasma',
            'Elevador hidráulico',
            'Prensa hidráulica',
            'Pulidora orbital',
            'Lijadora de banda',
            'Amoladora angular',
            'Destornillador eléctrico',
            'Sierra de calar',
            'Taladro de columna',
            'Máquina de soldar MIG',
            'Compresor de tornillo'
        ];

        return [
            'nombre' => $this->faker->randomElement($herramientas),
            'numero_serie' => $this->faker->unique()->bothify('HERR-####-????'),
            'foto' => $this->faker->optional(0.3)->imageUrl(400, 300, 'tools'),
            'tecnico_id' => $this->faker->optional(0.6)->randomElement(Tecnico::pluck('id')->toArray()),
        ];
    }

    /**
     * Estado para herramientas asignadas.
     */
    public function asignada(): static
    {
        return $this->state(fn (array $attributes) => [
            'tecnico_id' => Tecnico::inRandomOrder()->first()?->id ?? Tecnico::factory(),
        ]);
    }

    /**
     * Estado para herramientas sin asignar.
     */
    public function sinAsignar(): static
    {
        return $this->state(fn (array $attributes) => [
            'tecnico_id' => null,
        ]);
    }
}
