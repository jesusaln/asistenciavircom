<?php

namespace Database\Factories;

use App\Models\Renta;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentaFactory extends Factory
{
    protected $model = Renta::class;

    public function definition(): array
    {
        return [
            'empresa_id' => \App\Support\EmpresaResolver::resolveId() ?? Empresa::factory(),
            'numero_contrato' => 'R-' . $this->faker->unique()->numberBetween(1000, 9999),
            'cliente_id' => Cliente::factory(),
            'fecha_inicio' => now()->subMonths(6),
            'fecha_fin' => now()->addMonths(6),
            'monto_mensual' => $this->faker->randomFloat(2, 500, 5000),
            'estado' => 'activo',
        ];
    }
}
