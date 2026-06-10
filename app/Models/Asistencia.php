<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class Asistencia extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'user_id',
        'tipo',
        'fecha_hora',
        'latitud',
        'longitud',
        'distancia_oficina',
        'fuera_de_rango',
        'dispositivo',
        'foto_path',
        'notas',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'distancia_oficina' => 'decimal:2',
        'fuera_de_rango' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcular distancia entre dos puntos (Haversine formula) en metros
     */
    public static function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return null;

        $earthRadius = 6371000; // metros

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }
}
