<?php

namespace Database\Factories;

use App\Models\Mantenimiento;
use App\Models\Carro;
use Illuminate\Database\Eloquent\Factories\Factory;

class MantenimientoFactory extends Factory
{
    protected $model = Mantenimiento::class;

    public function definition(): array
    {
        $tipo = $this->faker->randomElement(Mantenimiento::TIPOS);

        return [
            'carro_id' => Carro::factory(),
            'tipo' => $tipo,
            'fecha' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'proximo_mantenimiento' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'descripcion' => $this->faker->sentence(),
            'costo' => $this->faker->randomFloat(2, 200, 5000),
            'estado' => Mantenimiento::ESTADO_PENDIENTE,
            'kilometraje_actual' => $this->faker->numberBetween(10000, 150000),
            'prioridad' => $this->faker->randomElement(['baja', 'media', 'alta', 'critica']),
            'dias_anticipacion_alerta' => $this->faker->numberBetween(7, 60),
            'requiere_aprobacion' => false,
        ];
    }

    public function completado(): static
    {
        return $this->state(fn(array $attributes) => [
            'estado' => Mantenimiento::ESTADO_COMPLETADO,
            'fecha' => $this->faker->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d'),
            'proximo_mantenimiento' => null,
        ]);
    }

    public function vencido(): static
    {
        return $this->state(fn(array $attributes) => [
            'proximo_mantenimiento' => $this->faker->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d'),
            'estado' => Mantenimiento::ESTADO_PENDIENTE,
        ]);
    }

    public function enProceso(): static
    {
        return $this->state(fn(array $attributes) => [
            'estado' => Mantenimiento::ESTADO_EN_PROCESO,
        ]);
    }

    public function conAlertaEnviada(): static
    {
        return $this->state(fn(array $attributes) => [
            'alerta_enviada' => true,
        ]);
    }

    public function recurrente(): static
    {
        $tipo = $this->faker->randomElement(Mantenimiento::TIPOS_RECURRENTES);

        return $this->state(fn(array $attributes) => [
            'tipo' => $tipo,
        ]);
    }
}
