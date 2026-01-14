<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\User;

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitaApiControllerTest extends TestCase
{
    protected User $user;
    protected Cliente $cliente;
    protected User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->cliente = Cliente::factory()->create();
        $this->tecnico = User::factory()->create(['es_tecnico' => true]);
    }

    public function test_api_can_list_citas()
    {
        Cita::factory()->count(3)->create(['estado' => Cita::ESTADO_PENDIENTE]);

        $response = $this->getJson('/api/citas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'tecnico_id',
                            'cliente_id',
                            'tipo_servicio',
                            'fecha_hora',
                            'estado'
                        ]
                    ]
                ],
                'stats',
                'meta'
            ]);
    }

    public function test_api_can_create_cita()
    {
        $citaData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Reparación',
            'fecha_hora' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'tipo_equipo' => 'Computadora',
            'marca_equipo' => 'Dell',
            'modelo_equipo' => 'Inspiron 15',
            'estado' => Cita::ESTADO_PENDIENTE,
        ];

        $response = $this->postJson('/api/citas', $citaData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'tecnico_id',
                    'cliente_id',
                    'tipo_servicio',
                    'estado'
                ]
            ]);

        $this->assertDatabaseHas('citas', $citaData);
    }

    public function test_api_validates_cita_creation()
    {
        $response = $this->postJson('/api/citas', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    }

    public function test_api_can_show_cita()
    {
        $cita = Cita::factory()->create([
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'estado' => Cita::ESTADO_PENDIENTE
        ]);

        $response = $this->getJson("/api/citas/{$cita->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'tecnico_id',
                    'cliente_id',
                    'tipo_servicio',
                    'fecha_hora',
                    'estado'
                ]
            ]);
    }

    public function test_api_returns_404_for_non_existent_cita()
    {
        $response = $this->getJson('/api/citas/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Cita no encontrada'
            ]);
    }

    public function test_api_can_update_cita()
    {
        $cita = Cita::factory()->create([
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'estado' => Cita::ESTADO_PENDIENTE
        ]);

        $updateData = [
            'tipo_servicio' => 'Mantenimiento',
            'estado' => Cita::ESTADO_EN_PROCESO
        ];

        $response = $this->putJson("/api/citas/{$cita->id}", $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $this->assertDatabaseHas('citas', array_merge(['id' => $cita->id], $updateData));
    }

    public function test_api_can_delete_cita()
    {
        $cita = Cita::factory()->create([
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'estado' => Cita::ESTADO_PENDIENTE
        ]);

        $response = $this->deleteJson("/api/citas/{$cita->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cita eliminada exitosamente.'
            ]);

        $this->assertSoftDeleted('citas', ['id' => $cita->id]);
    }

    public function test_api_validates_fecha_hora_constraints()
    {
        // Fecha en el pasado
        $citaData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Reparación',
            'fecha_hora' => now()->subDays(1)->format('Y-m-d H:i:s'),
            'tipo_equipo' => 'Computadora',
            'marca_equipo' => 'Dell',
            'modelo_equipo' => 'Inspiron 15',
            'estado' => Cita::ESTADO_PENDIENTE,
        ];

        $response = $this->postJson('/api/citas', $citaData);

        $response->assertStatus(422);
    }

    public function test_api_validates_tecnico_availability()
    {
        // Crear primera cita
        Cita::factory()->create([
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'fecha_hora' => now()->addDays(1)->setHour(10),
            'estado' => Cita::ESTADO_PENDIENTE
        ]);

        // Intentar crear segunda cita a la misma hora
        $citaData = [
            'tecnico_id' => $this->tecnico->id,
            'cliente_id' => $this->cliente->id,
            'tipo_servicio' => 'Reparación',
            'fecha_hora' => now()->addDays(1)->setHour(10)->format('Y-m-d H:i:s'),
            'tipo_equipo' => 'Computadora',
            'marca_equipo' => 'Dell',
            'modelo_equipo' => 'Inspiron 15',
            'estado' => Cita::ESTADO_PENDIENTE,
        ];

        $response = $this->postJson('/api/citas', $citaData);

        $response->assertStatus(422);
    }
}
