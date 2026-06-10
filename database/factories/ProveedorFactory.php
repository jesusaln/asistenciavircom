<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proveedor>
 */
class ProveedorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_razon_social' => $this->faker->company(),
            'tipo_persona' => $this->faker->randomElement(['fisica', 'moral']),
            'rfc' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'regimen_fiscal' => $this->faker->randomElement(['601', '603', '605', '606', '607', '608', '609', '610', '611', '612', '614', '615', '616', '620', '621', '622', '623', '624', '625', '626']),
            'uso_cfdi' => $this->faker->randomElement(['G01', 'G02', 'G03', 'I01', 'I02', 'I03', 'I04', 'I05', 'I06', 'I07', 'I08', 'D01', 'D02', 'D03', 'D04', 'D05', 'D06', 'D07', 'D08', 'D09', 'D10', 'S01', 'CP01', 'CN01']),
            'email' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->phoneNumber(),
            'calle' => $this->faker->streetName(),
            'numero_exterior' => $this->faker->buildingNumber(),
            'numero_interior' => $this->faker->optional()->buildingNumber(),
            'colonia' => $this->faker->citySuffix(),
            // CP de 5 dígitos para cumplir con la restricción del esquema
            'codigo_postal' => $this->faker->numerify('#####'),
            'municipio' => $this->faker->city(),
            'estado' => $this->faker->state(),
            'pais' => 'México',
            'activo' => true,
        ];
    }
}
