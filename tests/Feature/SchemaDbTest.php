<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        // Use updateOrInsert to avoid duplicate key errors in "dirty" DB
        DB::table('sat_estados')->updateOrInsert(
            ['clave' => 'CDMX'],
            ['nombre' => 'Ciudad de México', 'updated_at' => now()]
        );

        $estado = DB::table('sat_estados')->where('clave', 'CDMX')->first();
        $this->assertNotNull($estado);
        $this->assertEquals('CDMX', $estado->clave);
    }

    /**
     * Test: SAT Usos CFDI accepts CP01 (4 chars)
     */
    public function test_sat_usos_cfdi_accepts_cp01(): void
    {
        DB::table('sat_usos_cfdi')->updateOrInsert(
            ['clave' => 'CP01'],
            [
                'descripcion' => 'Pagos Test',
                'persona_fisica' => true,
                'persona_moral' => true,
                'activo' => true,
                'updated_at' => now()
            ]
        );

        $uso = DB::table('sat_usos_cfdi')->where('clave', 'CP01')->first();
        $this->assertNotNull($uso);
        $this->assertEquals('CP01', $uso->clave);
    }

    /**
     * Test: Clientes table works
     */
    public function test_clientes_insert_works(): void
    {
        $email = 'test.' . Str::random(5) . '@schema.db.local';
        $id = DB::table('clientes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'nombre_razon_social' => 'Test Schema Cliente',
            'email' => $email,
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
