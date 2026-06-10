<?php

namespace App\Services\Marketing;

use App\Models\MarketingCampana;
use App\Models\MarketingDestinatario;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Jobs\SendWhatsAppTemplate;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    /**
     * Crear una nueva campaña de marketing
     */
    public function createCampaign(array $data, ?Empresa $empresa = null): MarketingCampana
    {
        $empresa = $empresa ?: Empresa::first(); // Fallback to first empresa if not provided

        $campaign = MarketingCampana::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'tipo' => $data['tipo'] ?? 'whatsapp',
            'plantilla_id' => $data['plantilla_id'] ?? null,
            'data_plantilla' => $data['data_plantilla'] ?? null,
            'estado' => 'borrador',
            'user_id' => auth()->id() ?: 1, // Fallback to ID 1 for system
            'empresa_id' => $empresa->id,
            'fecha_programacion' => $data['fecha_programacion'] ?? null,
        ]);

        return $campaign;
    }

    /**
     * Añadir destinatarios a la campaña basándose en filtros
     */
    public function addRecipients(MarketingCampana $campaign, array $filters = []): int
    {
        // Establecer contexto de empresa para asegurar que el scope global funcione
        \App\Support\EmpresaResolver::setContext($campaign->empresa_id);

        $query = Cliente::query()
            ->whereNotNull('telefono')
            ->whereNull('opt_out_at');

        // Optional strict validations removed for better UX testing
        // if ($campaign->tipo === 'whatsapp') {
        //     $query->where('whatsapp_optin', true)
        //         ->whereNotNull('whatsapp_consent_date');
        // } elseif ($campaign->tipo === 'sms') {
        //     $query->where('sms_optin', true);
        // }

        if (!empty($filters['cliente_ids']) && is_array($filters['cliente_ids'])) {
            $query->whereIn('id', $filters['cliente_ids']);
        }

        if (!empty($filters['solo_activos'])) {
            $query->where(function ($clientes) {
                $clientes->where('activo', true)->orWhereNull('activo');
            });
        }

        $clientes = $query->get();
        $count = 0;

        foreach ($clientes as $cliente) {
            // Evitar duplicados en la misma campaña
            $exists = MarketingDestinatario::where('campana_id', $campaign->id)
                ->where('cliente_id', $cliente->id)
                ->exists();

            if (!$exists) {
                MarketingDestinatario::create([
                    'campana_id' => $campaign->id,
                    'cliente_id' => $cliente->id,
                    'estado' => 'pendiente',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Procesar y ejecutar la campaña (envío masivo)
     */
    public function executeCampaign(MarketingCampana $campaign): void
    {
        if ($campaign->estado === 'completado') {
            throw new \Exception('Esta campaña ya ha sido completada.');
        }

        if ($campaign->tipo !== 'whatsapp') {
            throw new \Exception('Actualmente solo se admiten campañas de WhatsApp.');
        }

        $destinatarios = $campaign->destinatarios()->where('estado', 'pendiente')->get();
        $empresa = Empresa::find($campaign->empresa_id);

        if (!$empresa) {
            throw new \Exception('No se encontró la empresa de la campaña.');
        }

        if ($destinatarios->isEmpty()) {
            throw new \Exception('La campaña no tiene destinatarios pendientes.');
        }

        $campaign->update(['estado' => 'en_proceso']);

        $delayTotal = 0;
        foreach ($destinatarios as $destinatario) {
            try {
                $this->dispatchWhatsApp($campaign, $destinatario, $empresa, $delayTotal);
                $delayTotal += 4; // 4 segundos de retraso acumulado entre cada mensaje
            } catch (\Exception $e) {
                Log::error('Error enviando mensaje de campaña', [
                    'campaign_id' => $campaign->id,
                    'destinatario_id' => $destinatario->id,
                    'error' => $e->getMessage(),
                ]);

                $destinatario->update([
                    'estado' => 'fallido',
                    'error_mensaje' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Programar mensaje de WhatsApp usando cola para evitar timeouts.
     */
    private function dispatchWhatsApp(MarketingCampana $campaign, MarketingDestinatario $destinatario, Empresa $empresa, int $delaySeconds = 0): void
    {
        $cliente = $destinatario->cliente;

        if (!$cliente->telefono) {
            throw new \Exception('Cliente sin número de teléfono válido.');
        }

        if (! $cliente->hasWhatsAppConsent() || ! $cliente->hasMarketingConsent()) {
            throw new \Exception('El cliente no cuenta con consentimiento válido para campañas de WhatsApp.');
        }

        $params = [];
        if ($campaign->data_plantilla && isset($campaign->data_plantilla['mapping'])) {
            foreach ($campaign->data_plantilla['mapping'] as $field) {
                $params[] = $field === 'custom' ? '' : ($cliente->{$field} ?? '');
            }
        }

        SendWhatsAppTemplate::dispatch(
            $empresa->id,
            $cliente->telefono,
            $campaign->plantilla_id,
            $empresa->whatsapp_default_language ?: 'es_MX',
            $params,
            [
                'tipo' => 'marketing_campaign',
                'campana_id' => $campaign->id,
                'destinatario_id' => $destinatario->id,
                'delay_seconds' => $delaySeconds,
                'header_params' => !empty($campaign->data_plantilla['header_image_url']) ? [['type' => 'image', 'image' => ['link' => $campaign->data_plantilla['header_image_url']]]] : [],
            ]
        );
    }
}
