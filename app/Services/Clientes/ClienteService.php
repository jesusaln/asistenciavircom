<?php

namespace App\Services\Clientes;

use App\Models\SatEstado;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Models\SatFormaPago;
use App\Models\PriceList;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class ClienteService
{
    private const CACHE_TTL = 3600; // 1 Hora

    public function getCatalogData(bool $includeEmpty = true): array
    {
        return [
            'tiposPersona' => $this->formatForVueSelect($this->getTiposPersona(), $includeEmpty),
            'regimenesFiscales' => $this->formatForVueSelect($this->getRegimenesFiscales(), $includeEmpty),
            'usosCFDI' => $this->formatForVueSelect($this->getUsosCFDI(), $includeEmpty),
            'formasPago' => $this->getFormasPago(),
            'estados' => $this->formatForVueSelect($this->getEstadosMexico(), $includeEmpty),
            'priceLists' => $this->getPriceLists(),
        ];
    }

    public function getTiposPersona(): array
    {
        return Cache::remember('tipos_persona', self::CACHE_TTL, fn() => [
            'fisica' => 'Persona Física',
            'moral' => 'Persona Moral',
        ]);
    }

    public function getRegimenesFiscales(): array
    {
        return Cache::remember('regimenes_fiscales_db', self::CACHE_TTL, function () {
            return SatRegimenFiscal::orderBy('clave')
                ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral'])
                ->keyBy('clave')
                ->toArray();
        });
    }

    public function getUsosCFDI(): array
    {
        return Cache::remember('usos_cfdi_db', self::CACHE_TTL, function () {
            return SatUsoCfdi::orderBy('clave')
                ->get(['clave', 'descripcion', 'persona_fisica', 'persona_moral', 'activo'])
                ->keyBy('clave')
                ->toArray();
        });
    }

    public function getFormasPago(): array
    {
        return Cache::remember('formas_pago_db', self::CACHE_TTL, function () {
            return SatFormaPago::orderBy('clave')
                ->get(['clave', 'descripcion'])
                ->map(function ($fp) {
                    return [
                        'value' => $fp->clave,
                        'label' => $fp->clave . ' — ' . $fp->descripcion,
                    ];
                })
                ->toArray();
        });
    }

    public function getEstadosMexico(): array
    {
        return Cache::remember('estados_mexico_db', self::CACHE_TTL, function () {
            return SatEstado::orderBy('nombre')
                ->get(['clave', 'nombre'])
                ->pluck('nombre', 'clave')
                ->toArray();
        });
    }

    public function getPriceLists(): array
    {
        // No cacheamos listas de precios porque pueden cambiar y dependen del user context (empresa)
        return PriceList::withoutGlobalScopes()
            ->where('activa', true)
            ->where(function ($q) {
                $empresaId = auth()->user()?->empresa_id;
                $q->whereNull('empresa_id')
                    ->orWhere('empresa_id', $empresaId);
            })
            ->orderBy('orden')
            ->get(['id', 'nombre', 'descripcion'])
            ->map(fn($lista) => [
                'value' => $lista->id,
                'text' => $lista->nombre,
                'descripcion' => $lista->descripcion,
            ])
            ->toArray();
    }

    private function formatForVueSelect(array $options, bool $includeEmpty = false, bool $showCode = false): array
    {
        $formatted = collect($options)->map(function ($value, $key) use ($showCode) {
            // Conversión explícita a string para evitar problemas de tipos en JSON/Validación
            $strKey = (string) $key;

            if (is_array($value)) {
                $label = $value['descripcion'] ?? ($value['nombre'] ?? $key);
                $extraData = $value;
            } else {
                $label = $value;
                $extraData = [];
            }

            return array_merge($extraData, [
                'value' => $strKey,
                'label' => $showCode ? "{$strKey} - {$label}" : $label,
            ]);
        })->values()->toArray();

        // Ordenar alfabéticamente por label
        usort($formatted, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        if ($includeEmpty) {
            array_unshift($formatted, ['value' => '', 'label' => 'Selecciona una opción']);
        }
        return $formatted;
    }
}
