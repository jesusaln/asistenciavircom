<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\AsistenciaRegistro;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AsistenciaTest extends TestCase
{
    use DatabaseTransactions;

    protected $empresa;
    protected $admin;
    protected $employee;
    protected $almacen;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear empresa
        $this->empresa = Empresa::factory()->create([
            'nombre_razon_social' => 'Empresa Test',
        ]);

        // Crear Admin
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);
        $this->admin->assignRole('admin');

        // Crear Empleado
        $this->employee = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'checkin_token' => 'test-token-123-' . uniqid(),
        ]);


        // Crear Almacén con coordenadas (Hermosillo - Centro)
        $this->almacen = Almacen::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Oficina Central',
            'estado' => 'activo',
            'latitud' => 29.0730,
            'longitud' => -110.9559,
            'geocerca_radio' => 200,
        ]);
    }

    /** @test */
    public function an_employee_can_access_checador_view()
    {
        $response = $this->actingAs($this->employee)
            ->get(route('asistencia.checador'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Asistencia/Checador')
                ->has('employee')
                ->where('employee.name', $this->employee->name)
        );
    }

    /** @test */
    public function an_admin_can_access_logs_view()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('asistencia.logs'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Asistencia/Logs')
                ->has('registros')
        );
    }

    /** @test */
    public function employee_can_register_entry_successfully()
    {
        Storage::fake('public');

        $descriptor = json_encode(array_fill(0, 128, 0.1)); // Mock descriptor de 128 dimensiones

        $response = $this->actingAs($this->employee)
            ->post(route('asistencia.store'), [
                'tipo' => 'entry',
                'latitud' => 29.0730,
                'longitud' => -110.9559,
                'precision_metros' => 10,
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
                'notas' => 'Entrando a trabajar',
                'consentimiento' => true,
                'face_challenge_completed' => true,
                'face_liveness_score' => 0.9,
                'face_descriptor' => $descriptor,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('asistencia_registros', [
            'user_id' => $this->employee->id,
            'tipo' => 'entry',
            'es_incidencia' => false,
        ]);

        // Verificar que se guardó la selfie
        $registro = AsistenciaRegistro::where('user_id', $this->employee->id)->first();
        Storage::disk('public')->assertExists($registro->selfie_path);

        // Verificar que el usuario quedó enrolado
        $this->employee->refresh();
        $this->assertNotNull($this->employee->face_enrolled_at);
        $this->assertNotNull($this->employee->face_descriptor);
    }

    /** @test */
    public function registration_creates_incidencia_if_far_from_office()
    {
        Storage::fake('public');
        $descriptor = json_encode(array_fill(0, 128, 0.1));

        // Coordenadas lejos (p. ej. otra ciudad)
        $response = $this->actingAs($this->employee)
            ->post(route('asistencia.store'), [
                'tipo' => 'entry',
                'latitud' => 32.6245, // Mexicali
                'longitud' => -115.4522,
                'precision_metros' => 10,
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
                'notas' => 'Entrada con incidencia',
                'consentimiento' => true,
                'face_challenge_completed' => true,
                'face_liveness_score' => 0.9,
                'face_descriptor' => $descriptor,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('asistencia_registros', [
            'user_id' => $this->employee->id,
            'tipo' => 'entry',
            'es_incidencia' => true,
        ]);

        $registro = AsistenciaRegistro::where('user_id', $this->employee->id)->first();
        $this->assertStringContainsString('Fuera de zona', $registro->motivo_incidencia);
    }

    /** @test */
    public function cannot_register_same_type_twice_within_five_minutes()
    {
        Storage::fake('public');
        $descriptor = json_encode(array_fill(0, 128, 0.1));

        // Primer registro
        $this->actingAs($this->employee)
            ->post(route('asistencia.store'), [
                'tipo' => 'entry',
                'latitud' => 29.0730,
                'longitud' => -110.9559,
                'precision_metros' => 10,
                'selfie' => UploadedFile::fake()->image('selfie1.jpg'),
                'notas' => 'Primer registro',
                'consentimiento' => true,
                'face_challenge_completed' => true,
                'face_descriptor' => $descriptor,
            ]);

        // Segundo registro inmediato
        $response = $this->actingAs($this->employee)
            ->post(route('asistencia.store'), [
                'tipo' => 'entry',
                'latitud' => 29.0730,
                'longitud' => -110.9559,
                'precision_metros' => 10,
                'selfie' => UploadedFile::fake()->image('selfie2.jpg'),
                'notas' => 'Segundo registro',
                'consentimiento' => true,
                'face_challenge_completed' => true,
                'face_descriptor' => $descriptor,
            ]);

        $response->assertSessionHasErrors('tipo');
        $this->assertEquals(1, AsistenciaRegistro::count());
    }

    /** @test */
    public function can_access_checkin_via_token_without_login()
    {
        $response = $this->get(route('asistencia.token', ['token' => $this->employee->checkin_token]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Asistencia/Checador')
                ->where('employee.name', $this->employee->name)
        );
    }
}
