<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class FacebookProductFeedController extends Controller
{
    /**
     * Generar XML Product Feed para Meta/Facebook Commerce Manager
     * Formato: RSS 2.0 con extensiones g: (Google/Facebook Product Data)
     * URL: /feed/facebook-products.xml
     */
    public function __invoke(): Response
    {
        $feed = Cache::remember('facebook_product_feed', 1800, function () {
            return $this->generateFeed();
        });

        return response($feed, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }

    private function generateFeed(): string
    {
        $empresa = EmpresaConfiguracion::getConfig();
        $appUrl = rtrim(config('app.url'), '/');
        
        // Corregir URL si es admin.climasdeldesierto.com
        if (str_contains($appUrl, 'admin.climasdeldesierto.com')) {
            $appUrl = str_replace('admin.climasdeldesierto.com', 'climasdeldesierto.com', $appUrl);
        }
        
        $nombreEmpresa = $empresa->nombre_comercial ?? $empresa->razon_social ?? 'Climas del Desierto';
        $tieneCatalogoWeb = Schema::hasColumn('productos', 'catalogo_web');

        $query = Producto::query()
            ->where('estado', 'activo')
            ->whereIn('tipo_producto', ['kit', 'simple', 'producto'])
            ->whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->where('precio_venta', '>', 0)
            ->whereNull('deleted_at')
            ->with(['categoria', 'marca']);

        $productos = $query->orderBy('nombre')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '  <title>' . $this->escape($nombreEmpresa) . ' - Catálogo de Productos</title>' . "\n";
        $xml .= '  <link>' . $appUrl . '/tienda</link>' . "\n";
        $xml .= '  <description>Catálogo de productos de ' . $this->escape($nombreEmpresa) . ' - Aire acondicionado, minisplits y climatización en Hermosillo, Sonora</description>' . "\n";

        foreach ($productos as $producto) {
            $xml .= $this->buildItem($producto, $appUrl, $nombreEmpresa);
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    private function buildItem(Producto $producto, string $appUrl, string $nombreEmpresa): string
    {
        $precioConIva = round($producto->precio_venta * 1.16, 2);
        $precioFormateado = number_format($precioConIva, 2, '.', '') . ' MXN';

        // URL del producto
        $url = $appUrl . '/producto/' . $producto->id;

        // Imagen: usar la del producto o un placeholder
        $imagen = $producto->imagen;
        if ($imagen && !str_starts_with($imagen, 'http')) {
            $imagen = $appUrl . '/storage/' . ltrim($imagen, '/');
        }
        if (empty($imagen)) {
            $imagen = $appUrl . '/images/placeholder-product.webp';
        }

        // Disponibilidad
        $totalStock = ($producto->stock ?? 0) + ($producto->stock_cedis ?? 0);
        $availability = $totalStock > 0 ? 'in stock' : 'available for order';

        // Categoría
        $categoria = $producto->categoria?->nombre ?? 'Climatización';

        // Marca
        $marca = $producto->marca?->nombre ?? $nombreEmpresa;

        // Condición
        $condition = 'new';

        // Descripción limpia
        $descripcion = $producto->descripcion ?: $producto->nombre;
        $descripcion = strip_tags($descripcion);
        if (strlen($descripcion) > 5000) {
            $descripcion = substr($descripcion, 0, 4997) . '...';
        }

        // ID único
        $itemId = $producto->sku ?: ('CDD-' . $producto->id);

        $xml = "  <item>\n";
        $xml .= "    <g:id>" . $this->escape($itemId) . "</g:id>\n";
        $xml .= "    <g:title>" . $this->escape(mb_substr($producto->nombre, 0, 150)) . "</g:title>\n";
        $xml .= "    <g:description>" . $this->escape(mb_substr($descripcion, 0, 5000)) . "</g:description>\n";
        $xml .= "    <g:link>" . $this->escape($url) . "</g:link>\n";
        $xml .= "    <g:image_link>" . $this->escape($imagen) . "</g:image_link>\n";
        $xml .= "    <g:availability>" . $availability . "</g:availability>\n";
        $xml .= "    <g:price>" . $precioFormateado . "</g:price>\n";
        $xml .= "    <g:brand>" . $this->escape($marca) . "</g:brand>\n";
        $xml .= "    <g:condition>" . $condition . "</g:condition>\n";
        $xml .= "    <g:product_type>" . $this->escape($categoria) . "</g:product_type>\n";
        
        // Determinar categoría de Google dinámicamente
        $gpc = '1519'; // HVAC por defecto
        if (str_contains(strtolower($producto->nombre), 'boiler') || str_contains(strtolower($producto->nombre), 'calentador')) {
            $gpc = '2181'; // Water Heaters
        }
        $xml .= "    <g:google_product_category>" . $gpc . "</g:google_product_category>\n";

        // Identificadores (Crucial para aprobación)
        if (empty($producto->codigo_barras) && empty($producto->gtin)) {
            $xml .= "    <g:identifier_exists>no</g:identifier_exists>\n";
        }

        // Código MPN
        if ($producto->codigo) {
            $xml .= "    <g:mpn>" . $this->escape($producto->codigo) . "</g:mpn>\n";
        }

        // Identificador de grupo (por categoría)
        if ($producto->categoria_id) {
            $xml .= "    <g:item_group_id>cat-" . $producto->categoria_id . "</g:item_group_id>\n";
        }

        $xml .= "  </item>\n";

        return $xml;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
