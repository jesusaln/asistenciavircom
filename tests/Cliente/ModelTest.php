<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cliente;
//use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions; // Cambiado aquí


class ModelTest extends TestCase
{
    use DatabaseTransactions; // Ahora usa transacciones
    //use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar seeders necesarios para catálogos SAT
        $this->seed([
            \Database\Seeders\DatabaseSeeder::class,
        ]);
    }

    public function test_tiene_los_campos_fillable_correctos()
    {
        $cliente = new Cliente();
        $fillable = $cliente->getFillable();

        $expected = [
            'nombre_razon_social',
            'tipo_persona',
            'tipo_identificacion',
            'identificacion',
            'curp',
            'rfc',
            'regimen_fiscal',
            'uso_cfdi',
            'domicilio_fiscal_cp',
            'residencia_fiscal',
            'num_reg_id_trib',
            'email',
            'telefono',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'codigo_postal',
            'municipio',
            'estado',
            'pais',
            'activo',
            'notas',
            'facturapi_customer_id',
            'cfdi_default_use',
            'payment_form_default',
        ];

        sort($expected);
        sort($fillable);

        $this->assertEquals($expected, $fillable);
    }

    public function test_tiene_los_casts_correctos()
    {
        $cliente = new Cliente();
        $casts = $cliente->getCasts();

        $this->assertArrayHasKey('activo', $casts);


        $this->assertEquals('boolean', $casts['activo']);
    }

    public function test_puede_tener_relacion_con_cotizaciones()
    {
        $cliente = Cliente::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $cliente->cotizaciones);
    }

    public function test_atributo_activo_se_castea_como_booleano()
    {
        $cliente = Cliente::factory()->create([
            'activo' => true,
        ]);

        $this->assertTrue($cliente->activo);
        $this->assertIsBool($cliente->activo);
    }
}
