<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Services\WhatsAppService;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = [];
        $error = null;

        try {
            $empresa = \Illuminate\Support\Facades\Auth::user()->empresa ?? \App\Models\Empresa::first();
            $ws = WhatsAppService::fromEmpresa($empresa);
            $templates = $ws->listTemplates();
        } catch (\Exception $e) {
            $error = $e->getMessage();
            \Illuminate\Support\Facades\Log::error('Error al listar plantillas en Controller', [
                'error' => $error,
                'empresa_id' => isset($empresa) ? $empresa->id : null
            ]);
        }

        return Inertia::render('Marketing/Plantillas/Index', [
            'templates' => $templates,
            'error' => $error
        ]);
    }
}
