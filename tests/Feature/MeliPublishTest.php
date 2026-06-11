<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Producto;
use App\Models\Empresa;
use App\Models\Team;
use App\Models\Marca;
use App\Models\Categoria;
use App\Services\MeliService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MeliPublishTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Empresa $empresa;
    protected $meliMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Forzar contexto de empresa a ID 1
        \App\Support\EmpresaResolver::setContext(1);

        $this->empresa = Empresa::updateOrCreate(
            ['id' => 1],
            [
                'nombre_razon_social' => 'Empresa Test S.A.',
                'tipo_persona' => 'moral',
                'regimen_fiscal' => '601',
                'uso_cfdi' => 'G03',
                'estado' => 'activo',
                'rfc' => 'XAXX010101000',
                'email' => 'admin@test.com',
                'telefono' => '1234567890',
                'calle' => 'Calle Falsa 123',
                'codigo_postal' => '12345',
                'municipio' => 'Municipio Test',
                'pais' => 'México'
            ]
        );

        $this->user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'empresa_id' => 1
            ]
        );

        $team = Team::updateOrCreate(
            ['name' => 'Test Team'],
            [
                'user_id' => $this->user->id,
                'personal_team' => true
            ]
        );

        $this->user->update(['current_team_id' => $team->id]);

        // Mock MeliService
        $this->meliMock = $this->mock(MeliService::class);
    }

    public function test_publicar_view_redirects_unauthenticated(): void
    {
        // Log out first to ensure unauthenticated status
        auth()->logout();
        
        $response = $this->get(route('mercadolibre.listings.publicar-view'));
        $response->assertRedirect('/login');
    }

    public function test_publicar_view_works_when_configured(): void
    {
        $this->meliMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(true);

        $this->meliMock->shouldReceive('getUser')
            ->once()
            ->andReturn(['nickname' => 'TEST_SELLER', 'site_id' => 'MLM', 'email' => 'test@test.com']);

        // Authenticate user
        $response = $this->actingAs($this->user)
            ->get(route('mercadolibre.listings.publicar-view'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('MercadoLibre/Publish')
            ->has('productos')
            ->has('meliUser')
        );
    }

    public function test_publicar_view_returns_error_when_not_configured(): void
    {
        $this->meliMock->shouldReceive('isConfigured')
            ->once()
            ->andReturn(false);

        $response = $this->actingAs($this->user)
            ->get(route('mercadolibre.listings.publicar-view'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('MercadoLibre/Publish')
            ->has('error')
        );
    }

    public function test_publish_creates_item_successfully(): void
    {
        $marca = Marca::create(['nombre' => 'Test Marca', 'empresa_id' => 1]);
        $categoria = Categoria::create(['nombre' => 'Test Categoria', 'empresa_id' => 1]);

        $producto = Producto::create([
            'nombre' => 'Monitor LED Test',
            'codigo' => 'MON-TEST',
            'precio_venta' => 150,
            'precio_compra' => 100,
            'stock' => 10,
            'estado' => 'activo',
            'empresa_id' => 1,
            'origen' => 'CVA',
            'marca_id' => $marca->id,
            'categoria_id' => $categoria->id,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
        ]);

        $this->meliMock->shouldReceive('createItem')
            ->once()
            ->withArgs(function ($arg) use ($producto) {
                return $arg['producto_id'] === $producto->id 
                    && $arg['price'] === 199.0
                    && $arg['available_quantity'] === 5
                    && $arg['listing_type_id'] === 'gold_special';
            })
            ->andReturn(['id' => 'MLM123456789', 'permalink' => 'https://articulo.mercadolibre.com.mx/MLM123456789']);

        $response = $this->actingAs($this->user)
            ->post(route('mercadolibre.listings.publicar'), [
                'producto_id' => $producto->id,
                'precio_venta' => 199,
                'listing_type_id' => 'gold_special',
                'stock_published' => 5,
            ]);

        $response->assertRedirect(route('mercadolibre.listings.index'));
        $response->assertSessionHas('success');
    }
}
