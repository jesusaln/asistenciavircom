<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\MarketingAudiencia;
use App\Models\PriceList;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AudienceController extends Controller
{
    public function index()
    {
        $audiencias = MarketingAudiencia::withCount('clientes')
            ->with(['creador:id,name', 'clientes:id,nombre_razon_social,telefono,email,activo'])
            ->orderBy('nombre')
            ->get();

        // Obtener wa_ids de los últimos mensajes entrantes en las últimas 24 horas
        $activeWaIds = \App\Models\WhatsAppChat::where('direction', 'inbound')
            ->where('received_at', '>=', now()->subHours(24))
            ->pluck('wa_id')
            ->unique()
            ->map(function($id) {
                $digits = preg_replace('/\D+/', '', (string) $id);
                return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
            })
            ->filter()
            ->values()
            ->toArray();

        $clientes = $this->eligibleClientsQuery()
            ->select([
                'id',
                'nombre_razon_social',
                'telefono',
                'email',
                'activo',
                'marketing_optin',
                'whatsapp_optin',
                'whatsapp_consent_date',
                'opt_out_at',
                'municipio',
                'price_list_id',
            ])
            ->with(['priceList:id,nombre'])
            ->withCount([
                'ventas',
                'polizas as polizas_activas_count' => function ($query) {
                    $query->where('estado', 'activa');
                },
            ])
            ->orderBy('nombre_razon_social')
            ->get()
            ->map(function ($client) use ($activeWaIds) {
                $telClean = preg_replace('/\D+/', '', (string) $client->telefono);
                $suffix = strlen($telClean) >= 10 ? substr($telClean, -10) : $telClean;
                $client->has_active_window = !empty($suffix) && in_array($suffix, $activeWaIds);
                return $client;
            });

        $priceLists = PriceList::activas()
            ->select(['id', 'nombre'])
            ->get();

        $municipios = $clientes->pluck('municipio')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Marketing/Audiencias/Index', [
            'audiencias' => $audiencias,
            'clientes' => $clientes,
            'priceLists' => $priceLists,
            'municipios' => $municipios,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'integer|exists:clientes,id',
        ]);

        $audiencia = MarketingAudiencia::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'empresa_id' => EmpresaResolver::resolveId(),
            'user_id' => Auth::id(),
        ]);

        $clienteIds = $this->resolveEligibleClientIds($validated['cliente_ids']);

        if (empty($clienteIds)) {
            return redirect()->route('marketing.audiencias.index')
                ->with('error', 'Selecciona al menos un cliente elegible para guardar la audiencia.');
        }

        $audiencia->clientes()->sync($clienteIds);

        return redirect()->route('marketing.audiencias.index')
            ->with('success', 'Audiencia guardada correctamente.');
    }

    public function update(Request $request, MarketingAudiencia $audiencia)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'cliente_ids' => 'required|array|min:1',
            'cliente_ids.*' => 'integer|exists:clientes,id',
        ]);

        $audiencia->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        $clienteIds = $this->resolveEligibleClientIds($validated['cliente_ids']);

        if (empty($clienteIds)) {
            return redirect()->route('marketing.audiencias.index')
                ->with('error', 'Selecciona al menos un cliente elegible para actualizar la audiencia.');
        }

        $audiencia->clientes()->sync($clienteIds);

        return redirect()->route('marketing.audiencias.index')
            ->with('success', 'Audiencia actualizada correctamente.');
    }

    public function destroy(MarketingAudiencia $audiencia)
    {
        $audiencia->delete();

        return redirect()->route('marketing.audiencias.index')
            ->with('success', 'Audiencia eliminada correctamente.');
    }

    private function eligibleClientsQuery()
    {
        return Cliente::query()
            ->whereNotNull('telefono')
            ->whereNull('opt_out_at');
    }

    private function resolveEligibleClientIds(array $clientIds): array
    {
        return $this->eligibleClientsQuery()
            ->whereIn('id', $clientIds)
            ->pluck('id')
            ->all();
    }
}
