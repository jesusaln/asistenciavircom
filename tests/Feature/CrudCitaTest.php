<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cita;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia;

class CrudCitaTest extends TestCase
{
    protected User $user;
    protected Cliente $cliente;
    protected User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar para asegurar conteos consistentes
        \Illuminate\Support\Facades\DB::table('citas')->delete();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->cliente = Cliente::factory()->create();
        $this->tecnico = User::factory()->create(['es_tecnico' => true]);
    }

    /** @test */
    public function test_usuario_puede_ver_lista_de_citas(): void
    {
        Cita::factory()->count(3)->create([
            'tipo_servicio' => 'Soporte Técnico',
            'estado' => 'pendiente'
        ]);

        $response = $this->get('/citas');

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Citas/Index')
                ->has('citas.data', 3)
                ->where('citas.data.0.tipo_servicio', 'Soporte Técnico')
        );
    }

    /** @test */
    public function test_usuario_puede_crear_cita(): void
    {
        $data = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Instalación',
            'fecha_hora' => now()->addDays(1)->setHour(10)->format('Y-m-d H:i:s'),
            'prioridad' => 'media',
            'descripcion' => 'Descripción de la cita',
            'estado' => 'pendiente',
            'tipo_equipo' => 'Laptop',
            'marca_equipo' => 'HP',
            'modelo_equipo' => 'Pavilion',
            'problema_reportado' => 'No enciende',
        ];

        $response = $this->post('/citas', $data);

        $response->assertRedirect('/citas');
        $this->assertDatabaseHas('citas', [
            'tipo_servicio' => 'Instalación',
            'tecnico_id' => $this->tecnico->id,
            'tipo_equipo' => 'Laptop'
        ]);
    }

    /** @test */
    public function test_usuario_puede_ver_detalle_de_cita()
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'tipo_servicio' => 'Mantenimiento Preventivo',
            'descripcion' => 'Descripción detallada',
            'estado' => 'pendiente'
        ]);

        $response = $this->get("/citas/{$cita->id}");

        $response->assertStatus(200);
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Citas/Show')
                ->where('cita.tipo_servicio', 'Mantenimiento Preventivo')
                ->where('cita.descripcion', 'Descripción detallada')
        );
    }

    /** @test */
    public function test_usuario_puede_editar_cita()
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'estado' => 'pendiente'
        ]);

        $data = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Servicio actualizado',
            'fecha_hora' => now()->addDays(2)->setHour(14)->format('Y-m-d H:i:s'),
            'prioridad' => 'alta',
            'descripcion' => 'Descripción actualizada',
            'estado' => 'en_proceso',
            'tipo_equipo' => 'Desktop',
            'marca_equipo' => 'Dell',
            'modelo_equipo' => 'Optiplex',
        ];

        $response = $this->put("/citas/{$cita->id}", $data);

        $response->assertRedirect('/citas');
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'tipo_servicio' => 'Servicio actualizado',
            'estado' => 'en_proceso'
        ]);
    }

    /** @test */
    public function test_usuario_puede_eliminar_cita()
    {
        $cita = Cita::factory()->create([
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $this->tecnico->id,
            'estado' => 'pendiente'
        ]);

        $response = $this->delete("/citas/{$cita->id}");

        $response->assertRedirect('/citas');
        $this->assertSoftDeleted('citas', ['id' => $cita->id]);
    }

    /** @test */
    public function test_campos_equipo_son_requeridos()
    {
        $data = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Reparación',
            'fecha_hora' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'estado' => 'pendiente',
            // Faltan campos de equipo
        ];

        $response = $this->post('/citas', $data);

        $response->assertSessionHasErrors(['tipo_equipo', 'marca_equipo', 'modelo_equipo']);
    }

    /** @test */
    public function test_longitud_maxima_campos()
    {
        $data = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => str_repeat('a', 256), // Max 255
            'fecha_hora' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'estado' => 'pendiente',
            'tipo_equipo' => str_repeat('a', 256),
            'marca_equipo' => str_repeat('a', 256),
            'modelo_equipo' => str_repeat('a', 256),
        ];

        $response = $this->post('/citas', $data);

        $response->assertSessionHasErrors(['tipo_servicio', 'tipo_equipo', 'marca_equipo', 'modelo_equipo']);
    }
}
