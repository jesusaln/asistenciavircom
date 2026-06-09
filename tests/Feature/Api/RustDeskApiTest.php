<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\PolizaConsumo;
use App\Models\PolizaServicio;
use App\Models\RemoteSupportSession;
use App\Models\User;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RustDeskApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('rustdesk.api_url', 'https://rustdesk.local');
        config()->set('rustdesk.api_token', 'test-token');
        config()->set('rustdesk.endpoints.device_status', '/api/devices/{id}');
        config()->set('rustdesk.endpoints.devices', '/api/devices');
        config()->set('rustdesk.endpoints.sync_alias', '/api/devices/{id}/alias');
        
        EmpresaResolver::clearCache();
    }

    public function test_login_unificado_authenticates_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            "email" => "tecnico@vircom.com",
            "password" => bcrypt("secret123"),
        ]);

        $this->postJson("/api/rustdesk/login", [
            "username" => "tecnico@vircom.com",
            "password" => "secret123",
        ])->assertOk()
          ->assertJsonStructure([
              "access_token",
              "type",
              "user" => ["name", "username", "email"]
          ]);
    }

    public function test_login_unificado_fails_with_invalid_credentials(): void
    {
        $this->postJson("/api/rustdesk/login", [
            "username" => "error@vircom.com",
            "password" => "wrong",
        ])->assertStatus(401);
    }

    public function test_status_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/rustdesk/status/123456')
            ->assertStatus(401);
    }

    public function test_status_endpoint_returns_device_state(): void
    {
        $empresa = Empresa::factory()->create();
        EmpresaResolver::setContext($empresa->id);
        
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $user->assignRole('admin');
        Sanctum::actingAs($user);

        Http::fake([
            'https://rustdesk.local/api/devices/123456' => Http::response([
                'id' => '123456',
                'online' => true,
            ], 200),
        ]);

        $this->getJson('/api/rustdesk/status/123456')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'rustdesk_id' => '123456',
                    'online' => true,
                ],
            ]);
    }

    public function test_sync_alias_endpoint_updates_local_user_alias_when_user_id_is_sent(): void
    {
        $empresa = Empresa::factory()->create();
        EmpresaResolver::setContext($empresa->id);

        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $targetUser = User::factory()->create([
            'empresa_id' => $empresa->id,
            'rustdesk_id' => '99887766',
            'rustdesk_alias' => 'OLD-ALIAS',
        ]);

        Http::fake([
            'https://rustdesk.local/api/devices/99887766/alias' => Http::response(['ok' => true], 200),
        ]);

        $this->postJson('/api/rustdesk/sync-alias', [
            'rustdesk_id' => '99887766',
            'alias' => 'NEW-ALIAS',
            'user_id' => $targetUser->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('NEW-ALIAS', $targetUser->fresh()->rustdesk_alias);
    }

    public function test_complete_session_consumes_hours_in_active_poliza(): void
    {
        Carbon::setTestNow('2026-02-25 10:00:00');

        $empresa = Empresa::factory()->create();
        EmpresaResolver::setContext($empresa->id);

        DB::table('empresa_configuracion')->updateOrInsert(
            ['id' => $empresa->id],
            [
                'empresa_id' => $empresa->id,
                'nombre_empresa' => 'Empresa Test RustDesk',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);

        $poliza = PolizaServicio::create([
            'empresa_id' => $empresa->id,
            'folio' => 'POL-RUSTDESK-001',
            'cliente_id' => $cliente->id,
            'nombre' => 'Poliza Soporte Remoto',
            'descripcion' => 'Plan de soporte con horas incluidas',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonth()->toDateString(),
            'monto_mensual' => 1000,
            'estado' => PolizaServicio::ESTADO_ACTIVA,
            'horas_incluidas_mensual' => 10,
            'horas_consumidas_mes' => 0,
            'costo_hora_excedente' => 350,
        ]);

        $session = RemoteSupportSession::create([
            'empresa_id' => $empresa->id,
            'user_id' => $admin->id,
            'cliente_id' => $cliente->id,
            'rustdesk_id' => '123456789',
            'started_at' => now(),
            'status' => 'started',
            'source' => 'web',
        ]);

        Carbon::setTestNow('2026-02-25 11:30:00');

        $this->postJson("/api/rustdesk/sessions/{$session->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 90)
            ->assertJsonPath('data.billing.applied', true)
            ->assertJsonPath('data.billing.poliza_id', $poliza->id)
            ->assertJsonPath('data.billing.hours', 1.5);

        $this->assertEquals(1.5, (float) $poliza->fresh()->horas_consumidas_mes);

        $consumo = PolizaConsumo::query()
            ->where('poliza_id', $poliza->id)
            ->where('consumible_type', RemoteSupportSession::class)
            ->where('consumible_id', $session->id)
            ->first();

        $this->assertNotNull($consumo);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        EmpresaResolver::clearCache();
        parent::tearDown();
    }
}
