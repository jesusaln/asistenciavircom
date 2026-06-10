<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class FacebookCatalogController extends Controller
{
    /**
     * Generar el feed XML para Facebook Catalog (Google Shopping format)
     */
    public function index()
    {
        // Obtener productos activos con sus relaciones
        $productos = Producto::with(['categoria', 'marca'])
            ->where('estado', 'activo')
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"/>');
        $channel = $xml->addChild('channel');

        // Forzamos el dominio principal
        $baseUrl = 'https://climasdeldesierto.com';

        $channel->addChild('title', 'Catálogo de Productos - Climas del Desierto');
        $channel->addChild('link', $baseUrl);
        $channel->addChild('description', 'Productos y servicios de climatización, aire acondicionado y refrigeración.');

        // IDs VERIFICADOS directamente del archivo oficial de Google Product Taxonomy
        // https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt
        // 605: Home & Garden > Household Appliances > Climate Control Appliances > Air Conditioners
        // 3082: Home & Garden > Household Appliances > Climate Control Appliances > Furnaces & Boilers
        // 611: Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters
        // 6727: Home & Garden > Household Appliances > Climate Control Appliances > Evaporative Coolers
        // 3573: Home & Garden > Household Appliance Accessories > Air Conditioner Accessories > Air Conditioner Filters
        // 1519: Hardware > Heating, Ventilation & Air Conditioning > HVAC Controls
        // 1167: Hardware > Tools
        // 500096: Hardware > Hardware Pumps
        $categoryMapping = [
            'Aires acondicionados' => '605',
            'Boiler' => '3082',
            'Electrico' => '1519',
            'Filtros' => '3573',
            'Refacciones' => '605',
            'Herramientas' => '1167',
            'Mangueras' => '1167',
        ];

        foreach ($productos as $producto) {
            $item = $channel->addChild('item');

            // 1. Identificador
            $item->addChild('g:id', (string) $producto->id, 'http://base.google.com/ns/1.0');

            // 2. Título (Corrección de mayúsculas y guiones)
            $titulo = str_replace(['-', '_'], ' ', $producto->nombre);
            if (mb_strtoupper($titulo, 'UTF-8') === $titulo) {
                $titulo = mb_convert_case(mb_strtolower($titulo, 'UTF-8'), MB_CASE_TITLE, "UTF-8");
            }
            $item->addChild('g:title', $titulo, 'http://base.google.com/ns/1.0');

            // 3. Descripción (Limpieza de HTML y mayúsculas)
            $descripcion = strip_tags($producto->descripcion ?: $producto->nombre);
            if (mb_strtoupper($descripcion, 'UTF-8') === $descripcion) {
                $descripcion = ucfirst(mb_strtolower($descripcion, 'UTF-8'));
            }
            $item->addChild('g:description', $descripcion, 'http://base.google.com/ns/1.0');

            // 4. Link
            $item->addChild('g:link', $baseUrl . '/producto/' . $producto->id, 'http://base.google.com/ns/1.0');

            // 5. Imagen
            $item->addChild('g:image_link', $this->getImageUrl($producto, $baseUrl), 'http://base.google.com/ns/1.0');

            // 6. Condición
            $item->addChild('g:condition', 'new', 'http://base.google.com/ns/1.0');

            // 7. Disponibilidad: Siempre 'in stock' para mantener los anuncios activos
            $item->addChild('g:availability', 'in stock', 'http://base.google.com/ns/1.0');

            // 8. Precio
            $precio = number_format($producto->precio_con_iva, 2, '.', '');
            $item->addChild('g:price', $precio . ' MXN', 'http://base.google.com/ns/1.0');

            // 9. Marca
            $marca = $producto->marca?->nombre ?: 'Climas del Desierto';
            $item->addChild('g:brand', $marca, 'http://base.google.com/ns/1.0');

            // 10. Categoría Google (IDs de Home & Garden / Hardware)
            $catInternal = $producto->categoria?->nombre;
            $googleCatId = $categoryMapping[$catInternal] ?? '1626'; // Default: Climate Control Appliances

            $lowName = mb_strtolower($producto->nombre, 'UTF-8');
            if (str_contains($lowName, 'boiler') || str_contains($lowName, 'calentador') || str_contains($lowName, 'flux')) {
                $googleCatId = '3082'; // Furnaces & Boilers
            } elseif (str_contains($lowName, 'calefactor')) {
                $googleCatId = '611'; // Space Heaters
            } elseif (str_contains($lowName, 'evaporador') || str_contains($lowName, 'evaporat')) {
                $googleCatId = '6727'; // Evaporative Coolers
            } elseif (str_contains($lowName, 'filtro')) {
                $googleCatId = '3573'; // Air Conditioner Filters
            } elseif (str_contains($lowName, 'bomba') || str_contains($lowName, 'presurizadora')) {
                $googleCatId = '500096'; // Hardware Pumps
            } elseif (str_contains($lowName, 'termico') || str_contains($lowName, 'centro de carga') || str_contains($lowName, 'conector') || str_contains($lowName, 'cable')) {
                $googleCatId = '1519'; // HVAC Controls
            } elseif (str_contains($lowName, 'pilas') || str_contains($lowName, 'bater')) {
                $googleCatId = '1626'; // Climate Control (general, closest valid)
            } elseif (str_contains($lowName, 'base') || str_contains($lowName, 'soporte')) {
                $googleCatId = '605'; // Air Conditioners (accessories)
            }

            $item->addChild('g:google_product_category', $googleCatId, 'http://base.google.com/ns/1.0');

            // 11. MPN / SKU
            if ($producto->codigo) {
                $item->addChild('g:mpn', $producto->codigo, 'http://base.google.com/ns/1.0');
            }
        }

        return Response::make($xml->asXML(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Obtener la URL absoluta de la imagen con fallback correcto
     */
    private function getImageUrl($producto, $baseUrl)
    {
        if (empty($producto->imagen)) {
            return $baseUrl . '/images/logo.webp';
        }

        // Si ya es una URL completa (ej. productos CVA)
        if (filter_var($producto->imagen, FILTER_VALIDATE_URL)) {
            return str_replace(['http://localhost', 'http://127.0.0.1'], $baseUrl, $producto->imagen);
        }

        // Si es una ruta de storage local
        return $baseUrl . '/storage/' . ltrim($producto->imagen, '/');
    }
}
