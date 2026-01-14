<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Schema verification using DB facade (no Eloquent models)
 */
class SchemaDbTest extends TestCase
{
    /**
     * Test: SAT Estados accepts CDMX (4 chars) - the original issue
     */
    public function test_sat_estados_accepts_cdmx(): void
    {
        // Use DB::table instead of Eloquent to avoid model observers
        $id = DB::table('sat_estados')->insertGetId([
            'clave' => 'CDMX',
            'nombre' => 'Ciudad de México',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->assertGreaterThan(0, $id);

        $estado = DB::table('sat_estados')->where('id', $id)->first();
        $this->assertEquals('CDMX', $estado->clave);

        // Cleanup
        DB::table('sat_estados')->where('id', $id)->delete();
    }

    /**
     * Test: SAT Usos CFDI accepts CP01 (4 chars)
     */
    public function test_sat_usos_cfdi_accepts_cp01(): void
    {
        $id = DB::table('sat_usos_cfdi')->insertGetId([
            'clave' => 'CP01',
            'descripcion' => 'Pagos Test',
            'persona_fisica' => true,
            'persona_moral' => true,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->assertGreaterThan(0, $id);

        $uso = DB::table('sat_usos_cfdi')->where('id', $id)->first();
        $this->assertEquals('CP01', $uso->clave);
        $this->assertTrue((bool) $uso->persona_fisica);
        $this->assertTrue((bool) $uso->persona_moral);

        // Cleanup
        DB::table('sat_usos_cfdi')->where('id', $id)->delete();
    }

    /**
     * Test: Clientes table works
     */
    public function test_clientes_insert_works(): void
    {
        $id = DB::table('clientes')->insertGetId([
            'nombre_razon_social' => 'Test Schema Cliente',
            'email' => 'test@schema.db.local',
            'tipo_persona' => 'fisica',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->assertGreaterThan(0, $id);

        $cliente = DB::table('clientes')->where('id', $id)->first();
        $this->assertEquals('Test Schema Cliente', $cliente->nombre_razon_social);

        // Cleanup
        DB::table('clientes')->where('id', $id)->delete();
    }
}
