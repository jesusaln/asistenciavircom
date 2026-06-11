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

        // 1. Create/Retrieve Empresa using unique RFC to avoid ID collision
        $this->empresa = Empresa::updateOrCreate(
            ['rfc' => 'XAXX010101000'],
            [
                'nombre_razon_social' => 'Empresa Test S.A.',
                'tipo_persona' => 'moral',
                'regimen_fiscal' => '601',
                'uso_cfdi' => 'G03',
                'estado' => 'activo',
                'email' => 'admin@test.com',
                'telefono' => '1234567890',
                'calle' => 'Calle Falsa 123',
                'codigo_postal' => '12345',
                'municipio' => 'Municipio Test',
                'pais' => 'México'
            ]
        );

        // 2. Set the context of EmpresaResolver to the actual ID of the created Empresa
        \App\Support\EmpresaResolver::setContext($this->empresa->id);

        // 3. Create/Retrieve User linked to the actual Empresa ID
        $this->user = User::updateOrCreate(
            ['email' => 'publisher.test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'empresa_id' => $this->empresa->id
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
        $marca = Marca::create(['nombre' => 'Test Marca', 'empresa_id' => $this->empresa->id]);
        $categoria = Categoria::create(['nombre' => 'Test Categoria', 'empresa_id' => $this->empresa->id]);

        $producto = Producto::create([
            'nombre' => 'Monitor LED Test',
            'codigo' => 'MON-TEST',
            'precio_venta' => 150,
            'precio_compra' => 100,
            'stock' => 10,
            'estado' => 'activo',
            'empresa_id' => $this->empresa->id,
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

    public function test_buscar_productos_returns_matching_products(): void
    {
        $marca = Marca::create(['nombre' => 'Test Marca', 'empresa_id' => $this->empresa->id]);
        $categoria = Categoria::create(['nombre' => 'Test Categoria', 'empresa_id' => $this->empresa->id]);

        Producto::create([
            'nombre' => 'Mouse Optico Inalambrico',
            'codigo' => 'MOU-WIRELESS',
            'precio_venta' => 20,
            'precio_compra' => 10,
            'stock' => 10,
            'estado' => 'activo',
            'empresa_id' => $this->empresa->id,
            'origen' => 'CVA',
            'marca_id' => $marca->id,
            'categoria_id' => $categoria->id,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('mercadolibre.listings.buscar-productos', ['search' => 'Mouse']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['nombre' => 'Mouse Optico Inalambrico']);
    }

    public function test_vincular_product_maps_listing_locally_and_updates_meli_sku(): void
    {
        $marca = Marca::create(['nombre' => 'Test Marca', 'empresa_id' => $this->empresa->id]);
        $categoria = Categoria::create(['nombre' => 'Test Categoria', 'empresa_id' => $this->empresa->id]);

        $producto = Producto::create([
            'nombre' => 'Teclado Mecanico Test',
            'codigo' => 'TEC-MEC-123',
            'precio_venta' => 80,
            'precio_compra' => 50,
            'stock' => 10,
            'estado' => 'activo',
            'empresa_id' => $this->empresa->id,
            'origen' => 'CVA',
            'marca_id' => $marca->id,
            'categoria_id' => $categoria->id,
            'unidad_medida' => 'pieza',
            'tipo_producto' => 'fisico',
        ]);

        $listing = \App\Models\MercadoLibreListing::create([
            'empresa_id' => $this->empresa->id,
            'listing_id' => 'MLM998877665',
            'permalink' => 'https://articulo.mercadolibre.com.mx/MLM998877665',
            'status' => 'active',
            'price' => 100,
            'stock_published' => 2,
            'meli_category_id' => 'MLM1055',
            'title' => 'Teclado Mecanico Sin Vincular',
        ]);

        $this->meliMock->shouldReceive('isConfigured')->once()->andReturn(true);
        $this->meliMock->shouldReceive('put')
            ->once()
            ->with('/items/MLM998877665', ['seller_custom_field' => 'TEC-MEC-123'])
            ->andReturn(['success' => true]);

        $response = $this->actingAs($this->user)
            ->post(route('mercadolibre.listings.vincular', $listing->id), [
                'producto_id' => $producto->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('mercadolibre_listings', [
            'id' => $listing->id,
            'producto_id' => $producto->id,
        ]);
    }
}
