<?php

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * TimezoneCast
 *
 * Cast para normalizar fechas automáticamente entre UTC y timezone local.
 * Garantiza consistencia en el manejo de fechas del sistema.
 *
 * Uso en modelos:
 *   protected $casts = [
 *       'fecha_hora' => TimezoneCast::class,
 *       'created_at' => 'timezone',  // Laravel built-in
 *   ];
 *
 * Configuración:
 *   protected $casts = [
 *       'fecha' => TimezoneCast::class.':America/Hermosillo',
 *   ];
 */
class TimezoneCast implements CastsAttributes
{
    /**
     * Timezone de destino para display
     */
    protected string $timezone;

    /**
     * Formato de salida
     */
    protected ?string $format;

    /**
     * Constructor
     */
    public function __construct(string $timezone = null, ?string $format = null)
    {
        $this->timezone = $timezone ?? config('app.timezone', 'America/Hermosillo');
        $this->format = $format;
    }

    /**
     * Obtener el valor del modelo (al leer de DB)
     *
     * Convierte UTC a timezone local para display
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        // Parsear como UTC y convertir al timezone local
        $carbon = Carbon::parse($value, 'UTC');

        return $carbon->timezone($this->timezone);
    }

    /**
     * Establecer el valor en el modelo (al escribir a DB)
     *
     * Convierte del timezone local a UTC para almacenamiento
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Si ya es Carbon, usar su timezone
        if ($value instanceof Carbon) {
            return $value->timezone('UTC')->toDateTimeString();
        }

        // Parsear en timezone local y convertir a UTC
        $carbon = Carbon::parse($value, $this->timezone);

        return $carbon->timezone('UTC')->toDateTimeString();
    }

    /**
     * Transformar para serialización (JSON, API)
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof Carbon) {
            return $value->format($this->format ?? 'Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
