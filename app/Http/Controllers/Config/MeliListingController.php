<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\MercadoLibreListing;
use App\Models\Producto;
use App\Models\MeliCategoryMapping;
use App\Services\MeliService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class MeliListingController extends Controller
{
    protected MeliService $meli;

    public function __construct(MeliService $meli)
    {
        $this->meli = $meli;
    }

    public function index()
    {
        if (!$this->meli->isConfigured()) {
            return Inertia::render('MercadoLibre/Listings', [
                'error' => 'MercadoLibre no está configurado. Ve a Configuración de Empresa -> Tienda Online para configurar las credenciales.',
                'listings' => [],
                'meliUser' => null
            ]);
        }

        $listings = MercadoLibreListing::with('producto')
            ->orderBy('id', 'desc')
            ->get();

        $meliUser = null;
        try {
            $userResponse = $this->meli->getUser();
            if (!isset($userResponse['error'])) {
                $meliUser = $userResponse;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching ML user in controller: ' . $e->getMessage());
        }

        return Inertia::render('MercadoLibre/Listings', [
            'listings' => $listings,
            'meliUser' => $meliUser
        ]);
    }

    public function destroy($id)
    {
        $listing = MercadoLibreListing::findOrFail($id);
        $listingId = $listing->listing_id;

        // Close the item on ML
        $closeResult = $this->meli->put("/items/{$listingId}", ['status' => 'closed']);
        
        if (isset($closeResult['error'])) {
            Log::error("Failed to close ML item {$listingId}", $closeResult);
            // If the item doesn't exist on ML (404), we should still allow deleting it from local DB
            if (isset($closeResult['status']) && $closeResult['status'] === 404) {
                $listing->delete();
                return redirect()->back()->with('success', 'Publicación eliminada localmente (no encontrada en MercadoLibre).');
            }
            return redirect()->back()->with('error', 'Error al cerrar la publicación en MercadoLibre: ' . ($closeResult['error'] ?? 'desconocido'));
        }

        // Try to fully delete it on ML (only works if no sales)
        $deleteResult = $this->meli->put("/items/{$listingId}", ['deleted' => 'true']);
        if (isset($deleteResult['error'])) {
            Log::warning("Failed to fully delete ML item {$listingId}, but it was closed successfully.", $deleteResult);
        }

        // Delete from local DB
        $listing->delete();

        return redirect()->back()->with('success', 'Publicación eliminada de MercadoLibre correctamente.');
    }

    public function sync()
    {
        if (!$this->meli->isConfigured()) {
            return redirect()->back()->with('error', 'MercadoLibre no está configurado.');
        }

        $userId = $this->meli->config->meli_user_id;
        if (!$userId) {
            return redirect()->back()->with('error', 'Usuario de MercadoLibre no vinculado.');
        }

        // 1. Get all listings of the user from ML API
        $searchResult = $this->meli->get("/users/{$userId}/items/search", ['limit' => 100]);
        if (isset($searchResult['error'])) {
            return redirect()->back()->with('error', 'Error al obtener publicaciones de MercadoLibre: ' . $searchResult['error']);
        }

        $meliListingIds = $searchResult['results'] ?? [];
        $syncedCount = 0;

        foreach ($meliListingIds as $id) {
            $item = $this->meli->getItem($id);
            if (isset($item['error'])) {
                continue;
            }

            // Sync with local DB
            $listing = MercadoLibreListing::where('listing_id', $id)->first();
            
            // Try to match product by custom SKU or title
            $producto = null;
            $sku = $item['seller_custom_field'] ?? null;
            if ($sku) {
                $producto = Producto::where('codigo', $sku)->orWhere('cva_clave', $sku)->first();
            }
            
            if (!$producto) {
                // Try to match by title
                $producto = Producto::where('nombre', $item['title'])->first();
            }

            $listingData = [
                'empresa_id' => $this->meli->config->empresa_id,
                'listing_id' => $id,
                'permalink' => $item['permalink'] ?? null,
                'status' => $item['status'] ?? 'active',
                'price' => $item['price'] ?? 0,
                'stock_published' => $item['available_quantity'] ?? 0,
                'meli_category_id' => $item['category_id'] ?? null,
                'producto_id' => $producto?->id,
                'last_sync_at' => now(),
            ];

            if ($listing) {
                $listing->update($listingData);
            } else {
                MercadoLibreListing::create($listingData);
            }

            $syncedCount++;
        }

        // Clean up listings that no longer exist or are closed/deleted on ML
        $localListings = MercadoLibreListing::all();
        foreach ($localListings as $local) {
            if (!in_array($local->listing_id, $meliListingIds)) {
                // Double check status on ML
                $item = $this->meli->getItem($local->listing_id);
                if (isset($item['status']) && in_array($item['status'], ['closed', 'inactive'])) {
                    $local->update(['status' => $item['status']]);
                } elseif (isset($item['error']) && isset($item['status']) && $item['status'] === 404) {
                    $local->delete();
                }
            }
        }

        return redirect()->back()->with('success', "Se sincronizaron {$syncedCount} publicaciones de MercadoLibre.");
    }

    public function publicarView(Request $request)
    {
        if (!$this->meli->isConfigured()) {
            return Inertia::render('MercadoLibre/Publish', [
                'error' => 'MercadoLibre no está configurado. Ve a Configuración de Empresa -> Tienda Online para configurar las credenciales.',
                'productos' => [
                    'data' => [],
                    'links' => []
                ],
                'meliUser' => null,
                'filters' => []
            ]);
        }

        $meliUser = null;
        try {
            $userResponse = $this->meli->getUser();
            if (!isset($userResponse['error'])) {
                $meliUser = $userResponse;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching ML user in publicarView: ' . $e->getMessage());
        }

        $query = Producto::where('estado', 'activo')
            ->where('origen', 'CVA')
            ->where('precio_compra', '>', 0)
            ->whereDoesntHave('mercadolibreListings');

        // Apply filters
        $search = $request->input('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('codigo', 'ilike', "%{$search}%")
                  ->orWhere('cva_clave', 'ilike', "%{$search}%");
            });
        }

        $productos = $query->with(['categoria', 'marca'])
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('MercadoLibre/Publish', [
            'productos' => $productos,
            'meliUser' => $meliUser,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function publicar(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'precio_venta' => 'required|numeric|min:0.01',
            'listing_type_id' => 'required|in:gold_special,gold_premium',
            'stock_published' => 'required|integer|min:1',
        ]);

        $producto = Producto::findOrFail($request->input('producto_id'));

        // Resolve MercadoLibre category mapping
        $catNombre = $producto->categoria?->nombre;
        $mapping = MeliCategoryMapping::where('cva_grupo', $catNombre)->first();
        $categoryId = $mapping?->meli_category_id ?? 'MLM0000';

        // Build product images
        $pictures = [];
        if ($producto->imagen) {
            $pictures[] = ['source' => $producto->imagen];
        }

        // Build item structure for ML
        $itemData = [
            'title' => mb_substr($producto->nombre, 0, 60),
            'category_id' => $categoryId,
            'price' => (float)$request->input('precio_venta'),
            'currency_id' => 'MXN',
            'available_quantity' => (int)$request->input('stock_published'),
            'buying_mode' => 'buy_it_now',
            'listing_type_id' => $request->input('listing_type_id'),
            'condition' => 'new',
            'pictures' => $pictures,
            'producto_id' => $producto->id,
            'seller_custom_field' => $producto->codigo ?: $producto->cva_clave,
        ];

        // Call ML Service to create the listing
        $result = $this->meli->createItem($itemData);

        if (isset($result['id'])) {
            return redirect()->route('mercadolibre.listings.index')
                ->with('success', '¡Producto publicado en MercadoLibre con éxito!');
        }

        $errorMsg = $result['error'] ?? 'Error desconocido al publicar en MercadoLibre.';
        if (isset($result['cause']) && is_array($result['cause'])) {
            $causes = collect($result['cause'])->map(fn($c) => $c['message'] ?? '')->implode(', ');
            $errorMsg .= ' Detalle: ' . $causes;
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Error al publicar en MercadoLibre: ' . $errorMsg);
    }
}
