<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        $payload = [];

        $this->putIfColumnExists($payload, 'nombre_razon_social', $this->faker->company);
        $this->putIfColumnExists($payload, 'rfc', $this->faker->unique()->regexify('[A-Z]{3,4}[0-9]{6}[A-Z0-9]{3}'));
        $this->putIfColumnExists($payload, 'tipo_persona', 'moral');
        $this->putIfColumnExists($payload, 'regimen_fiscal', '601');
        $this->putIfColumnExists($payload, 'uso_cfdi', 'G03');
        $this->putIfColumnExists($payload, 'email', $this->faker->companyEmail);
        $this->putIfColumnExists($payload, 'telefono', $this->faker->phoneNumber);
        $this->putIfColumnExists($payload, 'calle', $this->faker->streetName);
        $this->putIfColumnExists($payload, 'numero_exterior', $this->faker->buildingNumber);
        $this->putIfColumnExists($payload, 'codigo_postal', '12345');
        $this->putIfColumnExists($payload, 'colonia', $this->faker->citySuffix);
        $this->putIfColumnExists($payload, 'municipio', $this->faker->city);
        $this->putIfColumnExists($payload, 'estado', 'CDMX');
        $this->putIfColumnExists($payload, 'pais', 'Mexico');
        $this->putIfColumnExists($payload, 'whatsapp_enabled', false);
        $this->putIfColumnExists($payload, 'whatsapp_default_language', 'es_MX');

        return $payload;
    }

    private function putIfColumnExists(array &$payload, string $column, mixed $value): void
    {
        if (Schema::hasTable('empresas') && Schema::hasColumn('empresas', $column)) {
            $payload[$column] = $value;
        }
    }
}
