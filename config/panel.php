<?php

return [
    'max_citas_hoy' => (int) env('PANEL_MAX_CITAS_HOY', 50),

    /**
     * TTL en segundos para fragmentos del panel (Cache::remember).
     * Valores más bajos = datos más frescos, más carga en BD.
     */
    'cache_ttl' => [
        'default' => (int) env('PANEL_CACHE_TTL', 120),
        'stats' => (int) env('PANEL_CACHE_TTL_STATS', 120),
        'charts' => (int) env('PANEL_CACHE_TTL_CHARTS', 180),
        'alerts' => (int) env('PANEL_CACHE_TTL_ALERTS', 120),
        'citas_hoy' => (int) env('PANEL_CACHE_TTL_CITAS_HOY', 90),
        'ordenes' => (int) env('PANEL_CACHE_TTL_ORDENES', 120),
        'bitacora' => (int) env('PANEL_CACHE_TTL_BITACORA', 45),
    ],
];
