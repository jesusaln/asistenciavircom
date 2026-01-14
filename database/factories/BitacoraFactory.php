<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Cliente;
use App\Models\BitacoraActividad;

class BitacoraActividadFactory extends Factory
{
    protected $model = BitacoraActividad::class;

    public function definition(): array
    {
        $inicio = $this->faker->dateTimeBetween('-1 week', 'now');
        $fin    = (clone $inicio)->modify('+' . $this->faker->numberBetween(15, 180) . ' minutes');

        return [
            'user_id'       => User::factory(),
            'cliente_id'    => Cliente::factory(),
            'titulo'        => $this->faker->sentence(4),
            'descripcion'   => $this->faker->paragraph(),
            'fecha'         => $inicio->format('Y-m-d'),
            'hora'          => $inicio->format('H:i'),
            'inicio_at'     => $inicio,
            'fin_at'        => $fin,
            'tipo'          => $this->faker->randomElement(['soporte', 'instalacion', 'mantenimiento']),
            'estado'        => $this->faker->randomElement(['pendiente', 'en_proceso', 'completado', 'cancelado']),
            'prioridad'     => $this->faker->randomElement(['baja', 'media', 'alta']),
            'ubicacion'     => $this->faker->address(),
            'adjuntos'      => ['fotos' => [$this->faker->word . '.jpg']],
            'es_facturable' => $this->faker->boolean(),
            'costo_mxn'     => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
