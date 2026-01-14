<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\User;

use Tests\TestCase;

class CitaModelTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }
    public function test_cita_model_has_correct_fillable_attributes()
    {
        $cita = new Cita();

        $expectedFillable = [
            'folio',
            'empresa_id',
            'tecnico_id',
            'cliente_id',
            'tipo_servicio',
            'fecha_hora',
            'descripcion',
            'problema_reportado',
            'prioridad',
            'estado',
            'evidencias',
            'foto_equipo',
            'foto_hoja_servicio',
            'foto_identificacion',
            'tipo_equipo',
            'marca_equipo',
            'modelo_equipo',
            'subtotal',
            'descuento_general',
            'descuento_items',
            'iva',
            'total',
            'notas',
            'inicio_servicio',
            'fin_servicio',
            'tiempo_servicio',
        ];

        $this->assertEquals($expectedFillable, $cita->getFillable());
    }

    public function test_cita_model_has_correct_casts()
    {
        $cita = new Cita();

        $casts = $cita->getCasts();

        // Verificar que los casts principales estén presentes
        $this->assertEquals('datetime', $casts['fecha_hora']);
        $this->assertEquals('datetime', $casts['created_at']);
        $this->assertEquals('datetime', $casts['updated_at']);
        $this->assertEquals('datetime', $casts['deleted_at']);
    }

    public function test_cita_model_has_relationships()
    {
        $cita = new Cita();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $cita->cliente());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $cita->tecnico());
    }

    public function test_cita_model_has_correct_constants()
    {
        $this->assertEquals('pendiente', Cita::ESTADO_PENDIENTE);
        $this->assertEquals('en_proceso', Cita::ESTADO_EN_PROCESO);
        $this->assertEquals('completado', Cita::ESTADO_COMPLETADO);
        $this->assertEquals('cancelado', Cita::ESTADO_CANCELADO);

        $this->assertEquals('baja', Cita::PRIORIDAD_BAJA);
        $this->assertEquals('media', Cita::PRIORIDAD_MEDIA);
        $this->assertEquals('alta', Cita::PRIORIDAD_ALTA);
        $this->assertEquals('urgente', Cita::PRIORIDAD_URGENTE);
    }

    public function test_cita_model_scopes_work_correctly()
    {
        User::factory()->create(['es_tecnico' => true]);
        Cliente::factory()->create();

        // Crear citas con diferentes estados
        Cita::factory()->create(['estado' => Cita::ESTADO_PENDIENTE]);
        Cita::factory()->create(['estado' => Cita::ESTADO_EN_PROCESO]);
        Cita::factory()->create(['estado' => Cita::ESTADO_COMPLETADO]);
        Cita::factory()->create(['estado' => Cita::ESTADO_CANCELADO]);

        $this->assertEquals(1, Cita::pendientes()->count());
        $this->assertEquals(1, Cita::enProceso()->count());
        $this->assertEquals(1, Cita::completadas()->count());
        $this->assertEquals(1, Cita::canceladas()->count());
    }

    public function test_cita_model_accessors_work_correctly()
    {
        $cita = new Cita(['estado' => Cita::ESTADO_PENDIENTE]);

        $this->assertEquals('yellow', $cita->estado_color);

        $cita->estado = Cita::ESTADO_COMPLETADO;
        $this->assertEquals('green', $cita->estado_color);
    }

    public function test_cita_model_uses_soft_deletes()
    {
        $cita = Cita::factory()->create();

        $cita->delete();

        $this->assertEquals(0, Cita::count());
        $this->assertEquals(1, Cita::withTrashed()->count());
    }
}
