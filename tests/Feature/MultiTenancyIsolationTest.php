<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Models\Producto;
use App\Models\Cliente;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MultiTenancyIsolationTest extends TestCase
{
    // Usamos RefreshDatabase para limpiar la BD entre tests
    

    protected $empresaA;
    protected $empresaB;
    protected $userA;
    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear dos empresas distintas
        $this->empresaA = Empresa::create([
            'nombre_razon_social' => 'Empresa A',
            'rfc' => 'AAA010101AAA',
            'email' => 'a@test.com',
            'tipo_persona' => 'moral',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'codigo_postal' => '00000',
            'calle' => 'Calle A',
            'numero_exterior' => '1',
            'colonia' => 'Colonia A',
            'municipio' => 'Ciudad A',
            'estado' => 'Estado A',
            'pais' => 'México'
        ]);

        $this->empresaB = Empresa::create([
            'nombre_razon_social' => 'Empresa B',
            'rfc' => 'BBB020202BBB',
            'email' => 'b@test.com',
            'tipo_persona' => 'moral',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'codigo_postal' => '00000',
            'calle' => 'Calle B',
            'numero_exterior' => '1',
            'colonia' => 'Colonia B',
            'municipio' => 'Ciudad B',
            'estado' => 'Estado B',
            'pais' => 'México'
        ]);

        // 2. Crear usuarios para cada empresa
        $this->userA = User::factory()->create(['empresa_id' => $this->empresaA->id, 'email' => 'user_a@test.com']);
        $this->userB = User::factory()->create(['empresa_id' => $this->empresaB->id, 'email' => 'user_b@test.com']);

        // 3. Crear Role Super Admin y un usuario dummy para satisfacer EnsureSystemInstalled
        \Spatie\Permission\Models\Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['empresa_id' => $this->empresaA->id, 'email' => 'admin@test.com']);
        $admin->assignRole('super-admin');
    }

    #[Test]
    public function usuario_solo_ve_productos_de_su_empresa()
    {
        // Producto para Empresa A
        Producto::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'nombre' => 'Producto A',
        ]);

        // Producto para Empresa B
        Producto::factory()->create([
            'empresa_id' => $this->empresaB->id,
            'nombre' => 'Producto B',
        ]);

        // Actuar como User A
        $this->actingAs($this->userA);

        // Verificar que solo ve el producto de A
        $productos = Producto::all();
        $this->assertTrue($productos->contains('nombre', 'Producto A'));
        $this->assertFalse($productos->contains('nombre', 'Producto B'), 'User A no debería ver Producto B');
        $this->assertCount(1, $productos);
    }

    #[Test]
    public function usuario_solo_ve_clientes_de_su_empresa()
    {
        // Cliente para Empresa A
        Cliente::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'nombre_razon_social' => 'Cliente A',
        ]);

        // Cliente para Empresa B
        Cliente::factory()->create([
            'empresa_id' => $this->empresaB->id,
            'nombre_razon_social' => 'Cliente B',
        ]);

        // Actuar como User A
        $this->actingAs($this->userA);

        // Verificar
        $clientes = Cliente::all();
        $this->assertTrue($clientes->contains('nombre_razon_social', 'Cliente A'));
        $this->assertFalse($clientes->contains('nombre_razon_social', 'Cliente B'));
    }

    #[Test]
    public function nuevo_registro_hereda_empresa_del_usuario()
    {
        $this->actingAs($this->userA);

        // Crear producto sin especificar empresa_id
        // Crear producto usando factory para asegurar campos requeridos
        $producto = Producto::factory()->create([
            'nombre' => 'Producto Automatico',
            'codigo' => 'AUTO123',
            // NO pasamos empresa_id, el trait debe ponerlo
            'empresa_id' => null
        ]);

        $this->assertEquals($this->empresaA->id, $producto->empresa_id, 'El producto debe heredar el empresa_id del usuario creador');
    }

    #[Test]
    public function acceso_sin_empresa_es_denegado()
    {
        // Usuario sin empresa
        $userSinEmpresa = User::factory()->create(['empresa_id' => null]);

        $response = $this->actingAs($userSinEmpresa)
            ->get('/'); // Ruta raíz protegida por middleware

        // Debe ser Forbidden (403) por EnforceEmpresaContext
        $response->assertStatus(403);
    }
}
