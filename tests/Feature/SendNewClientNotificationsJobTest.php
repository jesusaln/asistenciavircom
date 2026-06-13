<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Models\UserNotification;
use App\Jobs\SendNewClientNotificationsJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SendNewClientNotificationsJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create an Empresa
        $this->empresa = \App\Models\Empresa::create([
            'nombre_razon_social' => 'Empresa Demo Notification Test',
            'tipo_persona' => 'moral',
            'rfc' => 'AAA010101AAA',
            'regimen_fiscal' => '601',
            'uso_cfdi' => 'G03',
            'email' => 'empresa@demo.test',
            'telefono' => '5512345678',
            'calle' => 'Calle 1',
            'numero_exterior' => '123',
            'colonia' => 'Centro',
            'codigo_postal' => '01000',
            'municipio' => 'CDMX',
            'estado' => 'CDMX',
            'pais' => 'MX',
        ]);

        // Create and authenticate user
        $this->user = User::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_creating_client_dispatches_notification_job(): void
    {
        Queue::fake();

        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'TEST CLIENT FOR JOB',
            'email' => 'jobtest@example.com',
            'telefono' => '1234567890',
            'tipo_persona' => 'fisica',
            'empresa_id' => $this->empresa->id,
        ]);

        // Triggers the method which dispatches the job
        UserNotification::createClientNotification($cliente);

        Queue::assertPushed(SendNewClientNotificationsJob::class);
    }

    public function test_job_creates_notifications_for_all_users(): void
    {
        // Prevent events from hitting real broadcasting services
        Event::fake([
            \App\Events\UserNotificationCreated::class
        ]);

        // Create some users
        $user1 = User::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);
        $user2 = User::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        $cliente = Cliente::factory()->create([
            'nombre_razon_social' => 'TEST CLIENT NOTIFICATION COUNT',
            'email' => 'counttest@example.com',
            'telefono' => '1234567890',
            'tipo_persona' => 'fisica',
            'empresa_id' => $this->empresa->id,
        ]);

        // Delete any existing notifications for our test users to have clean assertion counts
        UserNotification::whereIn('user_id', [$user1->id, $user2->id])->delete();

        // Instantiate and run the job directly
        $job = new SendNewClientNotificationsJob($cliente);
        $job->handle();

        // Assert database has notifications for both users
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user1->id,
            'type' => 'new_client',
            'title' => 'Nuevo Cliente Registrado',
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user2->id,
            'type' => 'new_client',
            'title' => 'Nuevo Cliente Registrado',
        ]);
    }
}
