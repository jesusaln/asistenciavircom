<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nombre_razon_social' => $this->faker->company,
            'rfc' => $this->faker->unique()->regexify('[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}'),
            'tipo_persona' => 'moral',
            'regimen_fiscal' => '601', // General de Ley Personas Morales
            'uso_cfdi' => 'G03', // Gastos en general
            'email' => $this->faker->companyEmail,
            'telefono' => $this->faker->phoneNumber,
            'calle' => $this->faker->streetName,
            'numero_exterior' => $this->faker->buildingNumber,
            'codigo_postal' => '12345',
            'colonia' => $this->faker->citySuffix,
            'municipio' => $this->faker->city,
            'estado' => 'CDMX',
            'pais' => 'México',
            'whatsapp_enabled' => false,
            'whatsapp_default_language' => 'es_MX',
        ];
    }
}
