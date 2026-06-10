<?php

namespace Database\Factories;

use App\Models\CuentaBancaria;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CuentaBancaria>
 */
class CuentaBancariaFactory extends Factory
{
    protected $model = CuentaBancaria::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => $this->faker->company() . ' - ' . $this->faker->currencyCode(),
            'banco' => $this->faker->randomElement(['BBVA', 'Banamex', 'Santander', 'HSBC', 'Banorte']),
            'numero_cuenta' => $this->faker->bankAccountNumber(),
            'clabe' => $this->faker->numerify('##################'),
            'moneda' => 'MXN',
            'saldo_inicial' => 0,
            'saldo_actual' => 0,
            'activa' => true,
        ];
    }
}
