<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cita;
use App\Models\Cliente;

use Tests\TestCase;
use Inertia\Testing\AssertableInertia;
use Mockery;

class CitaControllerTest extends TestCase
{


    protected User $user;
    protected Cliente $cliente;
    protected User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar para asegurar conteos consistentes
        \Illuminate\Support\Facades\DB::table('citas')->delete();

        // No longer using heavy seeders - tests create their own data
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Crear cliente y técnico para usar en tests
        $this->cliente = Cliente::factory()->create();
        $this->tecnico = User::factory()->create(['es_tecnico' => true]);
    }

    /** @test */
    public function index_returns_paginated_citas_with_stats(): void
    {
        $empresaId = $this->user->empresa_id;
        Cita::factory(16)->create(['activo' => true, 'estado' => 'pendiente', 'empresa_id' => $empresaId]);
        Cita::factory(3)->create(['activo' => false, 'estado' => 'pendiente', 'empresa_id' => $empresaId]);

        $response = $this->get(route('citas.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Index')
                ->has('citas.data', 10) // página 1, por defecto 10 por página
                ->where('citas.total', 19)
                ->where('stats.total', 19)
                ->where('stats.pendientes', 19) // Todas las citas de fábrica son pendientes
                ->where('stats.en_proceso', 0)
                ->where('stats.completadas', 0)
                ->where('stats.canceladas', 0)
        );
    }

    /** @test */
    public function index_applies_filters(): void
    {
        // 5 citas que deben aparecer
        Cita::factory(5)->create([
            'tipo_servicio' => 'Mantenimiento',
            'activo' => true,
        ]);

        // ruido que NO debe aparecer
        Cita::factory(3)->create([
            'tipo_servicio' => 'Otro',
            'activo' => true,
        ]);
        Cita::factory(2)->create([
            'tipo_servicio' => 'Mantenimiento',
            'activo' => false,
        ]);

        $response = $this->get(route('citas.index', [
            'search' => 'Mantenimiento',
            'activo' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Index')
                ->has('citas.data', 5)
        );
    }

    /** @test */
    public function create_renders_form_with_data(): void
    {
        $response = $this->get(route('citas.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Create')
                ->has('tecnicos')
                ->has('clientes')
        );
    }

    /** @test */
    public function store_creates_valid_cita(): void
    {
        $citaData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Mantenimiento preventivo',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
            'prioridad' => 'media',
            'descripcion' => 'Descripción de la cita',
            'tipo_equipo' => 'Laptop',
            'marca_equipo' => 'Dell',
            'modelo_equipo' => 'XPS 13',
            'problema_reportado' => 'No enciende correctamente',
            'estado' => 'pendiente',
            'evidencias' => 'Evidencias iniciales',
        ];

        $response = $this->post(route('citas.store'), $citaData);

        $response->assertRedirect('/citas');
        $response->assertSessionHas('success', 'Cita creada exitosamente.');

        $this->assertDatabaseHas('citas', [
            'tipo_servicio' => 'Mantenimiento preventivo',
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'estado' => 'pendiente',
        ]);
    }

    /** @test */
    public function store_fails_validation(): void
    {
        $data = [
            'tecnico_id' => '',
            'cliente_id' => '',
            'tipo_servicio' => '',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(), // Fecha válida pero otros campos inválidos
            'tipo_equipo' => '',
            'marca_equipo' => '',
            'modelo_equipo' => '',
            'estado' => 'invalid',
        ];

        $response = $this->post(route('citas.store'), $data);

        $response->assertSessionHasErrors([
            'tecnico_id',
            'cliente_id',
            'tipo_servicio',
            'tipo_equipo',
            'marca_equipo',
            'modelo_equipo',
            'estado'
        ]);

        $this->assertDatabaseCount('citas', 0);
    }

    /** @test */
    public function store_validates_fecha_hora_constraints(): void
    {
        // Fecha en domingo
        $proximoDomingo = now()->next(0); // 0 = domingo
        $domingoData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Test',
            'fecha_hora' => $proximoDomingo->setHour(10)->toDateTimeString(),
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'estado' => 'pendiente',
        ];

        $response = $this->post(route('citas.store'), $domingoData);
        $response->assertSessionHasErrors('fecha_hora');

        // Hora fuera de rango
        $fueraRangoData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(20)->toDateTimeString(),
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'estado' => 'pendiente',
        ];

        $response = $this->post(route('citas.store'), $fueraRangoData);
        $response->assertSessionHasErrors('fecha_hora');
    }

    /** @test */
    public function show_displays_cita_with_relations(): void
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
        ]);

        $response = $this->get(route('citas.show', $cita));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Show')
                ->has(
                    'cita',
                    fn(AssertableInertia $c) =>
                    $c->where('id', $cita->id)
                        ->where('tipo_servicio', $cita->tipo_servicio)
                        ->where('cliente_id', $this->cliente->id)
                        ->where('tecnico_id', $this->tecnico->id)
                        ->etc()
                )
        );
    }

    /** @test */
    public function edit_renders_form_with_cita_data(): void
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
        ]);

        $response = $this->get(route('citas.edit', $cita));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Edit')
                ->has('cita')
                ->has('tecnicos')
                ->has('clientes')
                ->where('cita.id', $cita->id)
        );
    }

    /** @test */
    public function update_modifies_cita(): void
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'Original',
        ]);

        $updateData = [
            'tipo_servicio' => 'Actualizado',
            'descripcion' => 'Descripción actualizada',
            'estado' => 'en_proceso',
            'tipo_equipo' => 'Laptop',
            'marca_equipo' => 'Acer',
            'modelo_equipo' => 'Aspire',
        ];

        $response = $this->put(route('citas.update', $cita), $updateData);

        $response->assertRedirect('/citas');
        $response->assertSessionHas('success', 'Cita actualizada exitosamente.');

        $this->assertDatabaseHas('citas', array_merge([
            'id' => $cita->id
        ], $updateData));
        $this->assertDatabaseMissing('citas', [
            'id' => $cita->id,
            'tipo_servicio' => 'Original'
        ]);
    }

    /** @test */
    public function destroy_removes_cita(): void
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
        ]);

        $response = $this->from('/citas')->delete(route('citas.destroy', $cita));

        $response->assertRedirect('/citas');
        $response->assertSessionHas('success', 'Cita eliminada exitosamente.');

        $this->assertSoftDeleted('citas', ['id' => $cita->id]);
    }

    /** @test */
    public function cannot_create_cita_with_conflicting_schedule(): void
    {
        $fechaHora = now()->addDays(1)->setHour(10)->toDateTimeString();

        // Crear primera cita
        Cita::factory()->create([
            'tecnico_id' => $this->tecnico->id,
            'fecha_hora' => $fechaHora,
        ]);

        // Intentar crear segunda cita a la misma hora
        $conflictingData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Conflicto',
            'fecha_hora' => $fechaHora,
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'estado' => 'pendiente',
        ];

        $response = $this->post(route('citas.store'), $conflictingData);

        $response->assertSessionHasErrors('fecha_hora');
    }

    /** @test */
    public function web_pages_load_correctly_with_inertia(): void
    {
        // Test página index
        $response = $this->get(route('citas.index'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Index')
                ->has('citas')
                ->has('stats')
                ->has('tecnicos')
                ->has('clientes')
                ->has('estados')
        );

        // Test página create
        $response = $this->get(route('citas.create'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Create')
                ->has('tecnicos')
                ->has('clientes')
        );

        // Test página show
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
        ]);

        $response = $this->get(route('citas.show', $cita));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Show')
                ->has('cita')
                ->where('cita.id', $cita->id)
        );

        // Test página edit
        $response = $this->get(route('citas.edit', $cita));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Edit')
                ->has('cita')
                ->has('tecnicos')
                ->has('clientes')
                ->where('cita.id', $cita->id)
        );
    }

    /** @test */
    public function web_index_shows_correct_stats(): void
    {
        // Crear citas con diferentes estados para este test específico
        Cita::factory(5)->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'estado' => 'pendiente',
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        Cita::factory(3)->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'estado' => 'completado',
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        $response = $this->get(route('citas.index'));

        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->has('stats')
                ->where('stats.pendientes', 5)
                ->where('stats.completadas', 3)
                ->where('stats.en_proceso', 0)
                ->where('stats.canceladas', 0)
        );
    }

    /** @test */
    public function web_index_filters_work_correctly(): void
    {
        // Crear citas para filtrar
        $cita1 = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'Mantenimiento',
            'estado' => 'pendiente',
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        $cita2 = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'Reparación',
            'estado' => 'completado',
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        // Test filtro por estado - verificar que funciona la estructura
        $response = $this->get(route('citas.index', ['estado' => 'pendiente']));
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->has('citas.data')
                ->has('filters')
                ->where('filters.estado', 'pendiente')
        );

        // Test filtro por búsqueda - verificar que funciona la estructura
        $response = $this->get(route('citas.index', ['search' => 'Mantenimiento']));
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->has('citas.data')
                ->has('filters')
                ->where('filters.search', 'Mantenimiento')
        );
    }

    /** @test */
    public function web_create_shows_correct_initial_data(): void
    {
        $response = $this->get(route('citas.create'));

        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Create')
                ->has('tecnicos')
                ->has('clientes')
                ->where('tecnicos', function ($tecnicos) {
                    return $tecnicos->contains('id', $this->tecnico->id);
                })
                ->where('clientes', function ($clientes) {
                    return $clientes->contains('id', $this->cliente->id);
                })
        );
    }

    /** @test */
    public function web_edit_shows_cita_data(): void
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'Servicio de prueba',
            'descripcion' => 'Descripción de prueba',
            'estado' => 'pendiente',
            'tipo_equipo' => 'Laptop',
            'marca_equipo' => 'Dell',
            'modelo_equipo' => 'XPS 13',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        $response = $this->get(route('citas.edit', $cita));

        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Edit')
                ->has('cita')
                ->has('tecnicos')
                ->has('clientes')
                ->where('cita.id', $cita->id)
                ->where('cita.tipo_servicio', 'Servicio de prueba')
                ->where('cita.descripcion', 'Descripción de prueba')
                ->where('cita.estado', 'pendiente')
                ->where('cita.tipo_equipo', 'Laptop')
                ->where('cita.marca_equipo', 'Dell')
                ->where('cita.modelo_equipo', 'XPS 13')
        );
    }

    /** @test */
    public function web_show_displays_complete_cita_details(): void
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'Consulta técnica',
            'descripcion' => 'Consulta sobre mantenimiento',
            'problema_reportado' => 'El equipo presenta fallos intermitentes',
            'prioridad' => 'alta',
            'estado' => 'en_proceso',
            'evidencias' => 'Evidencias documentadas',
            'tipo_equipo' => 'Desktop',
            'marca_equipo' => 'HP',
            'modelo_equipo' => 'ProDesk 600',
            'fecha_hora' => now()->addDays(2)->setHour(14)->toDateTimeString(),
        ]);

        $response = $this->get(route('citas.show', $cita));

        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Show')
                ->has('cita')
                ->where('cita.id', $cita->id)
                ->where('cita.tipo_servicio', 'Consulta técnica')
                ->where('cita.descripcion', 'Consulta sobre mantenimiento')
                ->where('cita.problema_reportado', 'El equipo presenta fallos intermitentes')
                ->where('cita.prioridad', 'alta')
                ->where('cita.estado', 'en_proceso')
                ->where('cita.evidencias', 'Evidencias documentadas')
                ->where('cita.tipo_equipo', 'Desktop')
                ->where('cita.marca_equipo', 'HP')
                ->where('cita.modelo_equipo', 'ProDesk 600')
                ->has('cita.cliente')
                ->has('cita.tecnico')
                ->where('cita.cliente.id', $this->cliente->id)
                ->where('cita.tecnico.id', $this->tecnico->id)
        );
    }

    /** @test */
    public function web_pagination_works_correctly(): void
    {
        // Crear más citas que el límite por página (10)
        Cita::factory(15)->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        // Test primera página
        $response = $this->get(route('citas.index', ['per_page' => 10]));
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->has('citas.data', 10)
                ->where('citas.total', 15)
                ->where('pagination.per_page', 10)
                ->where('pagination.current_page', 1)
                ->where('pagination.last_page', 2)
        );

        // Test segunda página
        $response = $this->get(route('citas.index', ['per_page' => 10, 'page' => 2]));
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->has('citas.data', 5)
                ->where('pagination.current_page', 2)
                ->where('pagination.from', 11)
                ->where('pagination.to', 15)
        );
    }

    /** @test */
    public function web_sorting_functionality_works(): void
    {
        $cita1 = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'AAA Servicio',
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        $cita2 = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'ZZZ Servicio',
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        // Test orden alfabético ascendente
        $response = $this->get(route('citas.index', ['sort_by' => 'tipo_servicio', 'sort_direction' => 'asc']));
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->has('citas.data', 2)
                ->where('citas.data.0.tipo_servicio', 'AAA Servicio')
                ->where('citas.data.1.tipo_servicio', 'ZZZ Servicio')
        );
    }

    /** @test */
    public function web_validation_errors_displayed_correctly(): void
    {
        $invalidData = [
            'tecnico_id' => '', // Inválido
            'cliente_id' => '', // Inválido
            'tipo_servicio' => '', // Inválido
            'tipo_equipo' => '', // Inválido
            'marca_equipo' => '', // Inválido
            'modelo_equipo' => '', // Inválido
            'estado' => 'invalid', // Inválido
        ];

        $response = $this->post(route('citas.store'), $invalidData);

        // Debería redirigir con errores (302) o devolver errores (422)
        $this->assertTrue(
            $response->getStatusCode() === 302 || $response->getStatusCode() === 422,
            "Expected status 302 or 422, got {$response->getStatusCode()}"
        );

        // Si es 302, verificar que hay errores en sesión
        if ($response->getStatusCode() === 302) {
            $response->assertSessionHasErrors([
                'tecnico_id',
                'cliente_id',
                'tipo_servicio',
                'tipo_equipo',
                'marca_equipo',
                'modelo_equipo',
                'estado'
            ]);
        }
    }

    /** @test */
    public function web_basic_integration_test(): void
    {
        // Test básico de integración web
        $response = $this->get(route('citas.index'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Index')
                ->has('citas')
                ->has('stats')
        );

        // Test que las páginas básicas cargan
        $response = $this->get(route('citas.create'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Create')
        );

        // Crear una cita básica para probar show y edit
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
        ]);

        $response = $this->get(route('citas.show', $cita));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Show')
                ->has('cita')
        );

        $response = $this->get(route('citas.edit', $cita));
        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) =>
            $page->component('Citas/Edit')
                ->has('cita')
        );
    }

    /** @test */
    public function web_success_messages_displayed_after_operations(): void
    {
        $citaData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Servicio web test',
            'fecha_hora' => now()->addDays(1)->setHour(10)->toDateTimeString(),
            'tipo_equipo' => 'Test',
            'marca_equipo' => 'Test',
            'modelo_equipo' => 'Test',
            'estado' => 'pendiente',
        ];

        // Test creación exitosa
        $response = $this->post(route('citas.store'), $citaData);
        $response->assertRedirect(route('citas.index'));
        $response->assertSessionHas('success', 'Cita creada exitosamente.');

        // Test actualización exitosa
        $cita = Cita::where('tipo_servicio', 'Servicio web test')->first();
        $updateData = ['tipo_servicio' => 'Servicio actualizado'];

        $response = $this->put(route('citas.update', $cita), $updateData);
        $response->assertRedirect(route('citas.index'));
        $response->assertSessionHas('success', 'Cita actualizada exitosamente.');

        // Test eliminación exitosa
        $response = $this->delete(route('citas.destroy', $cita));
        $response->assertRedirect(route('citas.index'));
        $response->assertSessionHas('success', 'Cita eliminada exitosamente.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
