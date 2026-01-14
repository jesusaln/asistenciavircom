<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use Carbon\Carbon;

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CitaCrudTest extends TestCase
{
    

    protected function setUp(): void
    {
        parent::setUp();

        // El middleware EnsureSystemInstalled salta en APP_ENV=testing, 
        // pero necesitamos un usuario para actingAs
    }

    public function test_puede_crear_editar_y_eliminar_cita(): void
    {
        // 1. Setup inicial
        $empresa = Empresa::create([
            'nombre_razon_social' => 'Empresa Test',
            'tipo_persona' => 'moral',
            'rfc' => 'TESTCIT01',
            'email' => 'test@citas.com',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'calle' => 'Calle Falsa',
            'numero_exterior' => '123',
            'colonia' => 'Centro',
            'codigo_postal' => '83000',
            'municipio' => 'Hermosillo',
            'estado' => 'Sonora',
            'pais' => 'México',
        ]);

        $tecnico = User::factory()->create([
            'empresa_id' => $empresa->id,
            'es_tecnico' => true,
        ]);

        $cliente = Cliente::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
        ]);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole($adminRole);

        $this->actingAs($admin);

        // 2. Probar Creación (Store)
        // La fecha debe ser futura, no domingo, entre 8am y 6pm
        $fechaCita = now()->addDays(1)->setHour(10)->setMinute(0)->setSecond(0);
        if ($fechaCita->isSunday()) {
            $fechaCita->addDay();
        }

        $payload = [
            'tecnico_id' => $tecnico->id,
            'cliente_id' => $cliente->id,
            'tipo_servicio' => 'Mantenimiento Preventivo',
            'fecha_hora' => $fechaCita->format('Y-m-d H:i:s'),
            'prioridad' => 'media',
            'estado' => 'programado',
            'descripcion' => 'Descripción de prueba para cita',
        ];

        $response = $this->post(route('citas.store'), $payload);
        $response->assertRedirect(route('citas.index'));

        $this->assertDatabaseHas('citas', [
            'tecnico_id' => $tecnico->id,
            'cliente_id' => $cliente->id,
            'tipo_servicio' => 'Mantenimiento Preventivo',
            'estado' => 'programado',
            'empresa_id' => $empresa->id,
        ]);

        $cita = Cita::where('cliente_id', $cliente->id)->firstOrFail();
        $this->assertNotEmpty($cita->folio);

        // 3. Probar Actualización (Update)
        $updatePayload = [
            'tipo_servicio' => 'Reparación Urgente',
            'prioridad' => 'urgente',
            'estado' => 'en_proceso',
        ];

        $response = $this->put(route('citas.update', $cita->id), $updatePayload);
        $response->assertRedirect(route('citas.index'));

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'tipo_servicio' => 'Reparación Urgente',
            'prioridad' => 'urgente',
            'estado' => 'en_proceso',
        ]);

        // 4. Probar Eliminación (Destroy)
        // Cambiar a un estado que permita eliminación (no puede ser en_proceso)
        $this->put(route('citas.update', $cita->id), ['estado' => 'cancelado']);

        $response = $this->delete(route('citas.destroy', $cita->id));
        $response->assertRedirect(route('citas.index'));

        $this->assertSoftDeleted('citas', ['id' => $cita->id]);
    }

    public function test_valida_conflictos_de_horario(): void
    {
        $empresa = Empresa::create([
            'nombre_razon_social' => 'Empresa Conf',
            'tipo_persona' => 'moral',
            'rfc' => 'TESTCON01',
            'email' => 'test2@citas.com',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'calle' => 'Calle Conflicto',
            'numero_exterior' => '456',
            'colonia' => 'Centro',
            'codigo_postal' => '83000',
            'municipio' => 'Hermosillo',
            'estado' => 'Sonora',
            'pais' => 'México',
        ]);

        $tecnico = User::factory()->create(['empresa_id' => $empresa->id, 'es_tecnico' => true]);
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $this->actingAs($admin);

        $fecha = now()->addDays(2)->setHour(11)->setMinute(0)->setSecond(0);
        if ($fecha->isSunday())
            $fecha->addDay();

        // Crear primera cita
        Cita::create([
            'empresa_id' => $empresa->id,
            'tecnico_id' => $tecnico->id,
            'cliente_id' => $cliente->id,
            'fecha_hora' => $fecha,
            'tipo_servicio' => 'Soporte',
            'estado' => 'programado',
            'prioridad' => 'baja',
        ]);



        // Intentar crear otra en el mismo horario con el mismo técnico
        $payload = [
            'tecnico_id' => $tecnico->id,
            'cliente_id' => $cliente->id,
            'tipo_servicio' => 'Otro Servicio',
            'fecha_hora' => $fecha->format('Y-m-d H:i:s'),
            'estado' => 'programado',
        ];

        $response = $this->from(route('citas.index'))->post(route('citas.store'), $payload);

        $response->assertSessionHasErrors(['fecha_hora']);
    }
}
