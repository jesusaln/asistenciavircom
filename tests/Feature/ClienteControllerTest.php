<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cliente;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Models\SatEstado;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Tests\TestCase;
use Inertia\Testing\AssertableInertia;
use Mockery;

class ClienteControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear Empresa base para el contexto
        $this->empresa = Empresa::factory()->create();
        EmpresaResolver::setContext($this->empresa->id);

        // 2. Autenticar como usuario admin de esa empresa
        $this->user = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'activo' => true
        ]);
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
    }

    /** @test */
    public function index_returns_paginated_clients_with_stats(): void
    {
        Cliente::factory(16)->create([
            'empresa_id' => $this->empresa->id,
            'activo' => true
        ]);
        Cliente::factory(3)->create([
            'empresa_id' => $this->empresa->id,
            'activo' => false
        ]);

        $response = $this->get(route('clientes.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Clientes/Index')
                ->has('clientes.data', 10) // página 1, por defecto 10 por página
                ->where('clientes.total', 19)
                ->where('estadisticas.total', 19)
                ->where('estadisticas.activos', 16)
                ->where('estadisticas.inactivos', 3)
        );
    }

    /** @test */
    public function index_applies_filters(): void
    {
        // 5 activos que deben aparecer
        Cliente::factory(5)->create([
            'empresa_id' => $this->empresa->id,
            'nombre_razon_social' => 'Test Search',
            'activo' => true,
        ]);

        // ruido que NO debe aparecer
        Cliente::factory(3)->create([
            'empresa_id' => $this->empresa->id,
            'nombre_razon_social' => 'Otro',
            'activo' => true,
        ]);
        Cliente::factory(2)->create([
            'empresa_id' => $this->empresa->id,
            'nombre_razon_social' => 'Test Search',
            'activo' => false,
        ]);

        $response = $this->get(route('clientes.index', [
            'search' => 'Test',
            'activo' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Clientes/Index')
                ->has('clientes.data', 5)
        );
    }

    /** @test */
    public function create_renders_form_with_catalogs(): void
    {
        // Los seeders ya crean los datos necesarios, no necesitamos crear más
        // Solo verificamos que el controlador puede acceder a los catálogos

        $response = $this->get(route('clientes.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Clientes/Create')
                ->has('catalogs.tiposPersona')
                ->has('catalogs.estados')
                ->has('catalogs.regimenesFiscales')
                ->has('catalogs.usosCFDI')
                ->where('cliente.tipo_persona', 'fisica')
                ->where('cliente.pais', 'MX')
                ->where('cliente.estado', 'SON')
                ->where('cliente.uso_cfdi', 'G03')
        );
    }

    /** @test */
    public function store_creates_valid_client(): void
    {
        // Usar datos existentes de los seeders en lugar de crear nuevos
        $regimen = SatRegimenFiscal::where('persona_fisica', true)->first(); // Asegurar compatibilidad físico
        $uso = SatUsoCfdi::first();       // Ya existe por seeder
        $estado = SatEstado::first();       // Ya existe por seeder

        $data = [
            'nombre_razon_social' => 'Test Cliente',
            'email' => 'test@local.test',
            'tipo_persona' => 'fisica',
            'rfc' => 'XAXX010101000', // 13 chars PF
            'regimen_fiscal' => $regimen->clave,
            'uso_cfdi' => $uso->clave,
            'calle' => 'Test Calle',
            'numero_exterior' => '123',
            'colonia' => 'Test Colonia',
            'codigo_postal' => '12345',
            'municipio' => 'Test Municipio',
            'estado' => $estado->clave,
            'pais' => 'MX',
            'telefono' => '1234567890',
            'activo' => true,
            'requiere_factura' => true,
        ];

        $response = $this->post(route('clientes.store'), $data);

        $response->assertRedirect(route('clientes.index'));
        $response->assertSessionHas('success', 'Cliente creado correctamente');

        $this->assertDatabaseHas('clientes', [
            'empresa_id' => $this->empresa->id,
            'nombre_razon_social' => 'TEST CLIENTE',
            'email' => 'test@local.test',
            'rfc' => 'XAXX010101000',
            'activo' => true,
        ]);
    }

    /** @test */
    public function store_fails_validation(): void
    {
        $data = [
            'nombre_razon_social' => '',
            'email' => 'invalid-email',
            'tipo_persona' => 'invalid',
            'rfc' => 'invalid',
            'regimen_fiscal' => 'nonexistent',
            'uso_cfdi' => 'nonexistent',
            'calle' => '',
            'numero_exterior' => '',
            'colonia' => '',
            'codigo_postal' => '123',
            'municipio' => '',
            'estado' => 'XXX',
            'pais' => 'US',
        ];

        $countBefore = Cliente::count();
        $response = $this->post(route('clientes.store'), $data);

        // En Inertia, por defecto retorna 302 con errores en sesión. Si tu controlador
        // usa Inertia::render con 422, deja 422. Cambia si tu flujo es 302.
        $status = $response->getStatusCode();
        $this->assertTrue(in_array($status, [302, 422]), "Unexpected status $status");

        // Asegura que NO se insertó un registro inválido
        $this->assertEquals($countBefore, Cliente::count());
    }

    /** @test */
    public function show_displays_client_with_relations(): void
    {
        // Usar datos existentes de los seeders
        $regimen = SatRegimenFiscal::first();
        $uso = SatUsoCfdi::first();
        $estado = SatEstado::first();

        $cliente = Cliente::factory()->create([
            'empresa_id' => $this->empresa->id,
            'regimen_fiscal' => $regimen->clave,
            'uso_cfdi' => $uso->clave,
            'estado' => $estado->clave,
            'pais' => 'MX',
        ]);

        $response = $this->get(route('clientes.show', $cliente));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            // Ajusta el componente si tu ruta usa otro (p. ej. 'Clientes/Edit')
            $page->component('Clientes/Show')
                ->has(
                    'cliente',
                    fn(AssertableInertia $c) =>
                    $c->where('id', $cliente->id)
                        ->where('nombre_razon_social', $cliente->nombre_razon_social)
                        ->where('regimen_fiscal', $regimen->clave)
                        ->where('uso_cfdi', $uso->clave)
                        ->where('estado', $estado->clave)
                        ->where('pais', 'MX')
                        ->etc()
                )
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
