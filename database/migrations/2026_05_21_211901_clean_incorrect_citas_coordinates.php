<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiar coordenadas que correspondan al centro de Hermosillo (29.0892, -110.9613)
        // o a la zona de Opatas/oficinas (alrededor de 29.1565, -111.0078).
        // Usamos una tolerancia amplia (rango) para capturar cualquier variación del viewport
        // guardada por el bug de Google Maps.
        
        // 1. Limpieza de coordenadas del Centro de Hermosillo (tolerancia aprox ~400 metros)
        DB::table('citas')
            ->whereBetween('latitud', [29.085, 29.093])
            ->whereBetween('longitud', [-110.965, -110.957])
            ->update([
                'latitud' => null,
                'longitud' => null,
            ]);

        // 2. Limpieza de coordenadas del viewport de Opatas/oficina (tolerancia aprox ~600 metros)
        DB::table('citas')
            ->whereBetween('latitud', [29.152, 29.160])
            ->whereBetween('longitud', [-111.013, -111.003])
            ->update([
                'latitud' => null,
                'longitud' => null,
            ]);
    }

    public function down(): void
    {
        // Operación destructiva de limpieza de datos erróneos, no es reversible de forma automática.
    }
};
