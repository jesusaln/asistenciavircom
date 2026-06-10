<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Almacen;
use App\Enums\EstadoCotizacion;
use App\Support\EmpresaResolver;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DebugCotizacionTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $empresa;
    protected $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin']);
        $permissions = ['view cotizaciones', 'create cotizaciones', 'view ventas'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
        $role->syncPermissions($permissions);

        $this->empresa = Empresa::create([
            'nombre_razon_social' => 'Empresa Test ' . uniqid(),
            'rfc' => 'ABC' . rand(100, 999) . '123456',
            'regimen_fiscal' => '601',
            'email' => 'test@empresa.com'
        ]);

        EmpresaResolver::setContext($this->empresa->id);

        $this->almacen = Almacen::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Almacén Test',
            'codigo' => 'ALM-' . uniqid(),
            'estado' => 'activo'
        ]);

        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'email' => 'admin_' . uniqid() . '@test.com',
            'almacen_venta_id' => $this->almacen->id
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_debug_store()
    {
        $this->withoutExceptionHandling();
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            dump($query->sql);
        });
        $this->actingAs($this->admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $this->empresa->id]);
        $producto = Producto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'stock' => 100,
            'precio_venta' => 1000,
            'estado' => 'activo'
        ]);

        $payload = [
            'cliente_id' => $cliente->id,
            'productos' => [
                [
                    'id' => $producto->id,
                    'tipo' => 'producto',
                    'cantidad' => 2,
                    'precio' => 1000,
                    'descuento' => 0
                ]
            ],
            'notas' => 'Debug test'
        ];

        $response = $this->postJson(route('cotizaciones.store'), $payload);

        if ($response->status() !== 302 || $response->isRedirect(url('/'))) {
            dump('Status: ' . $response->status());
            dump('Redirect to: ' . $response->headers->get('Location'));
            dump('Errors:', $response->getSession()?->get('errors')?->getMessages());
        }

        $response->assertRedirect(route('cotizaciones.index'));
    }
}
