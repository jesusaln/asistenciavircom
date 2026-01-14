<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClienteCrudTest extends TestCase
{
    

    public function test_puede_crear_editar_y_eliminar_cliente(): void
    {
        $empresa = Empresa::create([
            'nombre_razon_social' => 'Empresa Demo',
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

        // Crear un super-admin para satisfacer el middleware EnsureSystemInstalled
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminUser = User::factory()->create();
        $superAdminUser->assignRole($superAdminRole);

        // Limpiar cache de permisos para asegurar que middleware lo vea
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);
        $this->actingAs($user);

        $email = strtolower('cliente.' . Str::random(6) . '@test.com');

        $payload = [
            'nombre_razon_social' => 'Cliente Demo',
            'email' => $email,
            'telefono' => '5512345678',
            'requiere_factura' => false,
            'tipo_persona' => 'fisica',
        ];

        $response = $this->post(route('clientes.store'), $payload);
        $response->assertRedirect(route('clientes.index'));

        $assertData = [
            'nombre_razon_social' => 'CLIENTE DEMO',
            'email' => $email,
        ];

        if (Schema::hasColumn('clientes', 'empresa_id')) {
            $assertData['empresa_id'] = $empresa->id;
        }

        $this->assertDatabaseHas('clientes', $assertData);

        $cliente = Cliente::where('email', $email)->firstOrFail();

        $updatedEmail = strtolower('cliente.' . Str::random(6) . '@test.com');
        $updatePayload = [
            'nombre_razon_social' => 'Cliente Editado',
            'email' => $updatedEmail,
            'telefono' => '5510000000',
            'requiere_factura' => false,
        ];

        $response = $this->put(route('clientes.update', $cliente->id), $updatePayload);
        $response->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre_razon_social' => 'CLIENTE EDITADO',
            'email' => $updatedEmail,
        ]);

        $response = $this->delete(route('clientes.destroy', $cliente->id));
        $response->assertRedirect(route('clientes.index'));

        $this->assertSoftDeleted('clientes', ['id' => $cliente->id]);
    }
}
