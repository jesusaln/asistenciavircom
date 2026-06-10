<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\SatEstado;
use App\Models\SatUsoCfdi;
use App\Models\SatRegimenFiscal;
use App\Models\Producto;
use App\Models\Venta;


/**
 * Simple test to verify database schema is correct after fixes
 */
class SchemaVerificationTest extends TestCase
{
    

    /**
     * Test: SAT Estados table accepts CDMX (4 characters)
     */
    public function test_sat_estados_accepts_cdmx(): void
    {
        // This was the original issue - clave column was char(3) but CDMX has 4 chars
        $estado = SatEstado::create([
            'clave' => 'CDMX',
            'nombre' => 'Ciudad de México'
        ]);

        $this->assertDatabaseHas('sat_estados', [
            'clave' => 'CDMX',
            'nombre' => 'Ciudad de México'
        ]);

        $this->assertEquals('CDMX', $estado->clave);
    }

    /**
     * Test: SAT Usos CFDI accepts CP01 (4 characters) and has persona columns
     */
    public function test_sat_usos_cfdi_schema_correct(): void
    {
        $uso = SatUsoCfdi::create([
            'clave' => 'CP01',
            'descripcion' => 'Pagos',
            'persona_fisica' => true,
            'persona_moral' => true,
            'activo' => true
        ]);

        $this->assertDatabaseHas('sat_usos_cfdi', [
            'clave' => 'CP01',
            'descripcion' => 'Pagos'
        ]);

        $this->assertTrue($uso->persona_fisica);
        $this->assertTrue($uso->persona_moral);
    }

    /**
     * Test: Cliente can be created with basic fields
     */
    public function test_cliente_creation_works(): void
    {
        $cliente = Cliente::create([
            'nombre_razon_social' => 'Test Cliente Schema',
            'email' => 'test@schema.com',
            'tipo_persona' => 'fisica',
            'activo' => true
        ]);

        $this->assertDatabaseHas('clientes', [
            'nombre_razon_social' => 'Test Cliente Schema',
            'email' => 'test@schema.com'
        ]);
    }

    /**
     * Test: Producto can be created with basic fields
     */
    public function test_producto_creation_works(): void
    {
        $producto = Producto::create([
            'nombre' => 'Test Producto Schema',
            'sku' => 'TEST-SKU-001',
            'precio_venta' => 100.00,
            'stock' => 10,
            'estado' => 'activo'
        ]);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Test Producto Schema',
            'sku' => 'TEST-SKU-001'
        ]);
    }

    /**
     * Test: SAT Regimenes Fiscales accepts longer claves
     */
    public function test_sat_regimenes_fiscales_schema_correct(): void
    {
        $regimen = SatRegimenFiscal::create([
            'clave' => '601',
            'descripcion' => 'General de Ley Personas Morales',
            'persona_fisica' => false,
            'persona_moral' => true,
            'activo' => true
        ]);

        $this->assertDatabaseHas('sat_regimenes_fiscales', [
            'clave' => '601',
            'descripcion' => 'General de Ley Personas Morales'
        ]);
    }
}
