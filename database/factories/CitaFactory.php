<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cita>
 */
class CitaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => null,
            'tecnico_id' => \App\Models\User::factory()->state(['es_tecnico' => true]),
            'cliente_id' => \App\Models\Cliente::factory(),
            'tipo_servicio' => $this->faker->randomElement([
                'Reparación',
                'Mantenimiento',
                'Instalación',
                'Diagnóstico',
                'Actualización',
                'Soporte Técnico'
            ]),
            'fecha_hora' => $this->faker->dateTimeBetween('now', '+30 days'),
            'descripcion' => $this->faker->optional(0.7)->sentence(),
            'tipo_equipo' => $this->faker->randomElement([
                'Computadora',
                'Laptop',
                'Impresora',
                'Servidor',
                'Red',
                'Software'
            ]),
            'marca_equipo' => $this->faker->randomElement([
                'Dell',
                'HP',
                'Lenovo',
                'Apple',
                'Asus',
                'Acer',
                'Samsung',
                'LG'
            ]),
            'modelo_equipo' => $this->faker->bothify('??-###'),
            'problema_reportado' => $this->faker->optional(0.8)->sentence(),
            'prioridad' => $this->faker->randomElement([
                \App\Models\Cita::PRIORIDAD_BAJA,
                \App\Models\Cita::PRIORIDAD_MEDIA,
                \App\Models\Cita::PRIORIDAD_ALTA,
                \App\Models\Cita::PRIORIDAD_URGENTE
            ]),
            'estado' => $this->faker->randomElement([
                \App\Models\Cita::ESTADO_PENDIENTE,
                \App\Models\Cita::ESTADO_EN_PROCESO,
                \App\Models\Cita::ESTADO_COMPLETADO,
                \App\Models\Cita::ESTADO_CANCELADO
            ]),
            'evidencias' => $this->faker->optional(0.5)->text(200),
        ];
    }

    /**
     * Estado pendiente
     */
    public function pendiente()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => \App\Models\Cita::ESTADO_PENDIENTE,
                'fecha_hora' => $this->faker->dateTimeBetween('tomorrow', '+30 days'),
            ];
        });
    }

    /**
     * Estado completado
     */
    public function completada()
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => \App\Models\Cita::ESTADO_COMPLETADO,
            ];
        });
    }

    /**
     * Prioridad alta
     */
    public function urgente()
    {
        return $this->state(function (array $attributes) {
            return [
                'prioridad' => \App\Models\Cita::PRIORIDAD_URGENTE,
            ];
        });
    }
}
