<?php

namespace App\Services;

use App\Models\CrmProspecto;
use Illuminate\Support\Facades\Session;

class LeadCaptureService
{
    /**
     * Captura un nuevo prospecto con metadatos de marketing.
     *
     * @param array $data Datos básicos del prospecto (nombre, email, telefono, mensaje)
     * @param int|null $empresaId ID de la empresa del contexto
     * @return CrmProspecto
     */
    public function capture(array $data, ?int $empresaId = null): CrmProspecto
    {
        $marketingData = [
            'utm_source' => Session::get('mkt_utm_source'),
            'utm_medium' => Session::get('mkt_utm_medium'),
            'utm_campaign' => Session::get('mkt_utm_campaign'),
            'utm_term' => Session::get('mkt_utm_term'),
            'utm_content' => Session::get('mkt_utm_content'),
            'gclid' => Session::get('mkt_gclid'),
            'fbclid' => Session::get('mkt_fbclid'),
            'referer' => Session::get('mkt_referer'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $finalData = array_merge($data, $marketingData);

        if ($empresaId) {
            $finalData['empresa_id'] = $empresaId;
        }

        return CrmProspecto::create($finalData);
    }

    /**
     * Limpia los parámetros de marketing de la sesión después de una conversión exitosa.
     */
    public function clearMarketingSession(): void
    {
        $params = [
            'mkt_utm_source',
            'mkt_utm_medium',
            'mkt_utm_campaign',
            'mkt_utm_term',
            'mkt_utm_content',
            'mkt_gclid',
            'mkt_fbclid',
            'mkt_referer',
        ];

        foreach ($params as $param) {
            Session::forget($param);
        }
    }
}
