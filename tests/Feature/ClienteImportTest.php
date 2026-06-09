<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Support\EmpresaResolver;
use App\Imports\ClientesImport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ClienteImportTest extends TestCase
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
    public function it_can_download_the_template()
    {
        $response = $this->get(route('clientes.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=plantilla_clientes.xlsx');
    }

    /** @test */
    public function it_can_upload_and_import_clients()
    {
        Excel::fake();

        $file = UploadedFile::fake()->create('clientes.xlsx');

        $response = $this->post(route('clientes.import'), [
            'file' => $file
        ]);

        $response->assertRedirect(route('clientes.index'));
        $response->assertSessionHas('success', 'Clientes importados correctamente.');

        Excel::assertImported('clientes.xlsx');
    }

    /** @test */
    public function it_correctly_maps_excel_row_to_cliente_model()
    {
        $import = new ClientesImport();

        $row = [
            'nombre_razon_social' => 'Imported Client',
            'rfc' => 'XAXX010101000',
            'tipo_persona' => 'fisica',
            'email' => 'imported@test.com',
            'celular' => '1234567890',
            'pais' => 'MX'
        ];

        $model = $import->model($row);

        $this->assertInstanceOf(Cliente::class, $model);
        $this->assertEquals('Imported Client', $model->nombre_razon_social);
        $this->assertEquals('XAXX010101000', $model->rfc);
        $this->assertEquals('fisica', $model->tipo_persona);
        $this->assertEquals('imported@test.com', $model->email);
        $this->assertTrue($model->activo);
    }

    /** @test */
    public function it_skips_row_if_required_fields_are_missing()
    {
        $import = new ClientesImport();

        $row = [
            'nombre_razon_social' => 'Incomplete Client',
            // Missing RFC
        ];

        $model = $import->model($row);

        $this->assertNull($model);
    }

    /** @test */
    public function it_prevents_duplicate_rfc_import()
    {
        // Asegurarnos de que el contexto de la empresa esté establecido
        EmpresaResolver::setContext($this->empresa->id);

        Cliente::factory()->create([
            'rfc' => 'XAXX010101000',
            'empresa_id' => $this->empresa->id
        ]);

        $import = new ClientesImport();

        $row = [
            'nombre_razon_social' => 'Duplicate Client',
            'rfc' => 'XAXX010101000',
        ];

        $model = $import->model($row);

        $this->assertNull($model, 'Row with duplicate RFC should be skipped.');
    }
}
