<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class ComisionesController extends Controller
{
    public function nom019()
    {
        return Inertia::render('Comisiones/Nom019');
    }

    public function recorridos()
    {
        return Inertia::render('Comisiones/Recorridos');
    }

    public function repse()
    {
        return Inertia::render('Comisiones/Repse');
    }

    public function vencimientos()
    {
        return Inertia::render('Comisiones/Vencimientos');
    }

    public function pulse()
    {
        return Inertia::render('Comisiones/Pulse');
    }

    public function pulseConfig()
    {
        return Inertia::render('Comisiones/PulseConfig');
    }

    public function nom035()
    {
        $service = new \App\Services\Nom035Service();
        $coverageService = new \App\Services\Nom035CoverageService();

        $activePeriod = \App\Models\Nom035EvaluationPeriod::where('active', true)->first() 
            ?? \App\Models\Nom035EvaluationPeriod::latest('id')->first();
        
        $respondents = \App\Models\Nom035Respondent::with('empleado.user')->get();
        $respondentsForPeriod = \App\Models\Nom035Respondent::where('evaluation_period_id', $activePeriod?->id)->get();

        $coverageMetrics = $coverageService->forPeriod($activePeriod);
        $advancedStats = $service->getAggregatedStats($respondentsForPeriod);
        $recommendations = $service->getRecommendations($advancedStats);

        return Inertia::render('Comisiones/Nom035', [
            'periods' => \App\Models\Nom035EvaluationPeriod::all(),
            'activePeriod' => $activePeriod,
            'respondents' => $respondents,
            'coverageMetrics' => $coverageMetrics,
            'advancedStats' => $advancedStats,
            'recommendations' => $recommendations,
        ]);
    }
}
