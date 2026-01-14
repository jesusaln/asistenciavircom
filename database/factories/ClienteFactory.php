<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        // Garantiza catálogos mínimos
        $estadoClave = SatEstado::query()->inRandomOrder()->value('clave')
            ?? SatEstado::factory()->create(['clave' => 'SON', 'nombre' => 'Sonora'])->clave;

        $usoClave = SatUsoCfdi::query()->inRandomOrder()->value('clave')
            ?? SatUsoCfdi::factory()->create(['clave' => 'G03', 'descripcion' => 'Gastos en general'])->clave;

        // Tipo persona y régimen compatible
        $tipo = $this->faker->randomElement(['fisica', 'moral']);

        $regimenQuery = SatRegimenFiscal::query();
        $regimenQuery = $tipo === 'moral'
            ? $regimenQuery->where('persona_moral', true)
            : $regimenQuery->where('persona_fisica', true);

        $regimenClave = $regimenQuery->inRandomOrder()->value('clave');

        if (!$regimenClave) {
            $regimenClave = $tipo === 'moral'
                ? SatRegimenFiscal::factory()->create([
                    'clave' => '601',
                    'descripcion' => 'General de Ley Personas Morales',
                    'persona_moral' => true,
                    'persona_fisica' => false,
                ])->clave
                : SatRegimenFiscal::factory()->create([
                    'clave' => '612',
                    'descripcion' => 'PF con Actividades Empresariales y Profesionales',
                    'persona_moral' => false,
                    'persona_fisica' => true,
                ])->clave;
        }

        // RFC acorde a tipo (12 moral, 13 física)
        $rfc = $tipo === 'moral'
            ? strtoupper($this->faker->bothify('???########??'))  // 12
            : strtoupper($this->faker->bothify('????######???')); // 13

        $rfc = substr(preg_replace('/[^A-Z0-9Ñ&]/u', '', $rfc), 0, $tipo === 'moral' ? 12 : 13);

        return [
            'nombre_razon_social' => $this->faker->company(),
            'email'               => $this->faker->unique()->safeEmail(),
            'telefono'            => $this->faker->phoneNumber(),
            'tipo_persona'        => $tipo,
            'rfc'                 => $rfc,
            // OJO: usamos CLAVES, no descripciones
            'regimen_fiscal'      => $regimenClave, // e.g. '601', '612', ...
            'uso_cfdi'            => $usoClave,     // e.g. 'G03'
            'calle'               => $this->faker->streetName(),
            'numero_exterior'     => (string)$this->faker->numberBetween(1, 9999),
            'numero_interior'     => $this->faker->boolean() ? (string)$this->faker->numberBetween(1, 50) : null,
            'colonia'             => $this->faker->citySuffix(),
            'codigo_postal'       => $this->faker->numerify('#####'),
            'municipio'           => $this->faker->city(),
            'estado'              => $estadoClave,  // CLAVE existente (e.g. 'CDMX', 'SON')
            'pais'                => 'MX',          // CLAVE, no "MÉXICO"
            'activo'              => true,
        ];
    }
}
