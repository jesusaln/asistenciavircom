<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingAudiencia;
use App\Models\MarketingCampana;
use App\Services\Marketing\CampaignService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    protected $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Listado de campañas
     */
    public function index()
    {
        $campanias = MarketingCampana::with(['creador:id,name'])
            ->withCount(['destinatarios', 'destinatarios as enviados_count' => function($query) {
                $query->whereIn('estado', ['enviado', 'entregado', 'leido']);
            }])
            ->latest()
            ->paginate(15);

        return Inertia::render('Marketing/Index', [
            'campanias' => $campanias,
        ]);
    }

    /**
     * Vista de creación de campaña
     */
    public function create()
    {
        // Obtener plantillas de WhatsApp disponibles
        $templates = [];
        $selectedAudience = null;
        try {
            $ws = WhatsAppService::fromEmpresa(Auth::user()->empresa ?? \App\Models\Empresa::first());
            $templates = $ws->listTemplates();
        } catch (\Exception $e) {
            // Log error or handle gracefully
        }

        if ($audienciaId = request()->query('audiencia')) {
            $selectedAudience = MarketingAudiencia::with('clientes:id,nombre_razon_social')
                ->find($audienciaId);
        }

        return Inertia::render('Marketing/Create', [
            'templates' => $templates,
            'selectedAudience' => $selectedAudience,
        ]);
    }

    /**
     * Guardar nueva campaña
     */
    public function store(Request $request)
    {
        $empresaId = Auth::user()?->empresa_id ?? \App\Support\EmpresaResolver::resolveId();

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:10000',
            'tipo' => 'required|in:whatsapp',
            'plantilla_id' => 'required|string|max:255',
            'data_plantilla' => 'nullable|array|max:50',
            'data_plantilla.mapping' => 'nullable|array|max:40',
            'data_plantilla.mapping.*' => 'nullable|string|max:120',
            'data_plantilla.header_image_url' => 'nullable|string|max:2048',
            'filtros' => 'nullable|array|max:20',
            'filtros.cliente_ids' => 'nullable|array|max:50000',
            'filtros.cliente_ids.*' => [
                'integer',
                'min:1',
                Rule::exists('clientes', 'id')->where('empresa_id', $empresaId ?? -1),
            ],
            'filtros.solo_activos' => 'nullable|boolean',
        ]);

        $empresa = Auth::user()->empresa ?? \App\Models\Empresa::first();
        $campaign = $this->campaignService->createCampaign($validated, $empresa);
        
        // Añadir destinatarios basados en filtros
        $count = $this->campaignService->addRecipients($campaign, $validated['filtros'] ?? []);

        return redirect()->route('marketing.campanias.show', $campaign)
            ->with('success', "Campaña creada con {$count} destinatarios únicos.");
    }

    /**
     * Detalle de campaña y estadísticas
     */
    public function show(MarketingCampana $campania)
    {
        if ($campania->estado === 'en_proceso' && !$campania->destinatarios()->where('estado', 'pendiente')->exists()) {
            $campania->update(['estado' => 'completado']);
            $campania->refresh();
        }

        $campania->load(['creador:id,name', 'destinatarios.cliente:id,nombre_razon_social,telefono']);
        
        $stats = [
            'total' => $campania->destinatarios()->count(),
            'pendiente' => $campania->destinatarios()->where('estado', 'pendiente')->count(),
            'enviado' => $campania->destinatarios()->where('estado', 'enviado')->count(),
            'entregado' => $campania->destinatarios()->where('estado', 'entregado')->count(),
            'leido' => $campania->destinatarios()->where('estado', 'leido')->count(),
            'fallido' => $campania->destinatarios()->where('estado', 'fallido')->count(),
        ];

        return Inertia::render('Marketing/Show', [
            'campania' => $campania,
            'stats' => $stats,
        ]);
    }

    /**
     * Ejecutar el envío masivo
     */
    public function execute(MarketingCampana $campania)
    {
        if ($campania->estado === 'completado') {
            return back()->with('error', 'Esta campaña ya ha sido ejecutada.');
        }

        try {
            // Podríamos hacerlo sincrónico por ahora si son pocos, pero idealmente sería un Job
            $this->campaignService->executeCampaign($campania);
            return back()->with('success', 'Campaña programada para envío.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al ejecutar: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar campaña
     */
    public function destroy(MarketingCampana $campania)
    {
        $campania->delete();
        return redirect()->route('marketing.campanias.index')
            ->with('success', 'Campaña eliminada correctamente.');
    }
}
