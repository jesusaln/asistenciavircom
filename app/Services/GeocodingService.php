<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeocodingService
{
    /**
     * Geocodificación inversa de coordenadas usando Nominatim (OpenStreetMap)
     *
     * @param float|string $lat
     * @param float|string $lon
     * @return string|null
     */
    public static function reverseGeocode($lat, $lon): ?string
    {
        if (empty($lat) || empty($lon)) {
            return null;
        }

        // Precisión de caché: ~11 metros (4 decimales)
        $cacheKey = "geo_addr_" . round($lat, 4) . "_" . round($lon, 4);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lon) {
            try {
                // Nominatim requiere un User-Agent descriptivo
                $response = Http::withHeaders([
                    'User-Agent' => 'AsistenciaVircom-App/1.0 (contact: soporte@vircom.com)'
                ])->timeout(5)
                    ->get("https://nominatim.openstreetmap.org/reverse", [
                        'format' => 'json',
                        'lat' => $lat,
                        'lon' => $lon,
                        'zoom' => 18,
                        'addressdetails' => 1
                    ]);

                if ($response->successful()) {
                    return $response->json('display_name');
                }
            } catch (\Exception $e) {
                Log::error("Geocoding falló para $lat, $lon: " . $e->getMessage());
            }

            return null;
        });
    }
}
