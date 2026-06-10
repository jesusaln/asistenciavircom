<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Nom035EvaluationPeriod;
use App\Models\Nom035Respondent;
use App\Models\Nom035Question;
use App\Models\User;
use App\Services\Nom035Service;
use App\Services\Nom035CoverageService;

class Nom035Controller extends Controller
{
    protected Nom035Service $service;
    protected Nom035CoverageService $coverageService;

    public function __construct(Nom035Service $service, Nom035CoverageService $coverageService)
    {
        $this->service = $service;
        $this->coverageService = $coverageService;
    }

    public function index(Request $request)
    {
        $empresa_id = \App\Support\EmpresaResolver::resolveId();
        $activePeriod = Nom035EvaluationPeriod::where('empresa_id', $empresa_id)->where('active', true)->first() 
            ?? Nom035EvaluationPeriod::where('empresa_id', $empresa_id)->latest('id')->first();
        
        $respondents = Nom035Respondent::where('empresa_id', $empresa_id)->with('empleado')->get();
        $respondentsForPeriod = Nom035Respondent::where('empresa_id', $empresa_id)->where('evaluation_period_id', $activePeriod?->id)->get();

        $coverageMetrics = $this->coverageService->forPeriod($activePeriod);
        $advancedStats = $this->service->getAggregatedStats($respondentsForPeriod);
        $recommendations = $this->service->getRecommendations($advancedStats);

        // ✅ NOM-035 Numeral 7.3: Re-evaluación cada 2 años
        $lastClosedPeriod = Nom035EvaluationPeriod::where('empresa_id', $empresa_id)
            ->where('status', 'closed')
            ->orderBy('end_date', 'desc')
            ->first();
        $reEvaluation = [
            'due' => false,
            'last_evaluation_date' => $lastClosedPeriod?->end_date,
            'next_evaluation_date' => $lastClosedPeriod?->end_date?->addYears(2),
            'months_overdue' => null,
        ];
        if ($lastClosedPeriod) {
            $nextDue = $lastClosedPeriod->end_date->copy()->addYears(2);
            if ($nextDue->isPast()) {
                $reEvaluation['due'] = true;
                $reEvaluation['months_overdue'] = $nextDue->diffInMonths(now());
            } elseif ($nextDue->diffInMonths(now()) <= 3) {
                $reEvaluation['due'] = true; // Warn 3 months before
                $reEvaluation['months_overdue'] = 0;
            }
        }

        $policy = \App\Models\Nom035Configuration::where('empresa_id', $empresa_id)->first();
        $activitiesCount = \App\Models\Nom035Activity::where('empresa_id', $empresa_id)->count();
        $pendingReferrals = Nom035Respondent::where('empresa_id', $empresa_id)
            ->where('requires_clinical_valuation', true)
            ->where('clinical_valuation_status', 'pending')
            ->count();

        $wizard = [
            'step1_policy' => !empty($policy?->policy_content),
            'step2_period' => (bool)$activePeriod,
            'step3_respondents' => $respondentsForPeriod->count() > 0,
            'step4_completion' => ($coverageMetrics['coverage_rate'] ?? 0) >= 80, // 80% is a good legal target
            'step5_referrals' => $pendingReferrals === 0,
            'step6_activities' => $activitiesCount > 0,
        ];

        return Inertia::render('Nom035/Index', [
            'periods' => Nom035EvaluationPeriod::where('empresa_id', $empresa_id)->orderBy('id', 'desc')->get(),
            'activePeriod' => $activePeriod,
            'respondents' => $respondents,
            'coverageMetrics' => $coverageMetrics,
            'advancedStats' => $advancedStats,
            'recommendations' => $recommendations,
            'complianceWizard' => $wizard,
            'reEvaluation' => $reEvaluation,
        ]);
    }

    public function createPeriodo(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // Deactivate others
        Nom035EvaluationPeriod::where('empresa_id', \App\Support\EmpresaResolver::resolveId())->where('active', true)->update(['active' => false]);

        $periodo = Nom035EvaluationPeriod::create([
            'empresa_id' => \App\Support\EmpresaResolver::resolveId(),
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'active' => true,
            'status' => 'active',
        ]);

        return back()->with('success', "Periodo {$periodo->name} creado exitosamente.");
    }

    public function showPeriodo(Nom035EvaluationPeriod $periodo)
    {
        $respondents = Nom035Respondent::where('evaluation_period_id', $periodo->id)
            ->with('empleado')
            ->get();
            
        $empleados_disponibles = User::where('es_empleado', true)
            ->where('activo', true)
            ->whereNotIn('id', $respondents->pluck('empleado_id'))
            ->select('id', 'name as nombre', 'email', 'departamento', 'puesto')
            ->get();

        return Inertia::render('Nom035/Periodo', [
            'periodo' => $periodo,
            'respondents' => $respondents,
            'empleados_disponibles' => $empleados_disponibles
        ]);
    }

    public function agregarEmpleados(Request $request, Nom035EvaluationPeriod $periodo)
    {
        $data = $request->validate([
            'empleado_ids' => 'required|array',
            'empleado_ids.*' => 'exists:users,id'
        ]);

        $count = 0;
        foreach ($data['empleado_ids'] as $eid) {
            $empleado = User::find($eid);
            
            $exists = Nom035Respondent::where('evaluation_period_id', $periodo->id)
                ->where('empleado_id', $eid)->exists();

            if (!$exists) {
                Nom035Respondent::create([
                    'empresa_id' => $periodo->empresa_id,
                    'evaluation_period_id' => $periodo->id,
                    'empleado_id' => $eid,
                    'name' => $empleado->name,
                    'email' => $empleado->email,
                    'department' => $empleado->departamento,
                    'position' => $empleado->puesto,
                    'status' => 'pending',
                ]);
                $count++;

                // Notify via Ionic
                if ($empleado->fcm_token) {
                    app(\App\Services\PushNotificationService::class)->sendNotification(
                        $empleado->fcm_token,
                        'Evaluación NOM-035 Pendiente',
                        "Hola {$empleado->name}, tienes una nueva evaluación de la NOM-035 asignada. Por favor, complétala en la sección de Cumplimiento Legal.",
                        ['type' => 'nom035', 'periodo_id' => $periodo->id]
                    );
                }
            }
        }

        return back()->with('success', "$count colaboradores agregados al periodo.");
    }

    public function cuestionario(Nom035Respondent $respondent)
    {
        if ($respondent->status === 'completed') {
            return redirect()->route('nom035.resultados', $respondent);
        }

        $questions = Nom035Question::where('guide', $respondent->guide)
            ->orderBy('id')
            ->get();

        return Inertia::render('Nom035/Questionnaire', [
            'respondent' => $respondent,
            'questions' => $questions
        ]);
    }

    public function guardarCuestionario(Request $request, Nom035Respondent $respondent)
    {
        $data = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:nom035_questions,id',
            'answers.*.value' => 'required|integer|min:0|max:4',
        ]);

        foreach ($data['answers'] as $ans) {
            \App\Models\Nom035Answer::updateOrCreate(
                ['respondent_id' => $respondent->id, 'question_id' => $ans['question_id']],
                ['value' => $ans['value']]
            );
        }

        // Calculate results
        $answers = $respondent->answers()->with('question')->get();
        $results = [];
        
        if ($respondent->guide === 'Guía I') {
            $results = $this->service->calculateGuideI($answers);
        } elseif ($respondent->guide === 'Guía II') {
            $results = $this->service->calculateGuideII($answers);
        } elseif ($respondent->guide === 'Guía III') {
            $results = $this->service->calculateGuideIII($answers);
        }

        $respondent->update([
            'status' => 'completed',
            'results' => $results,
            'risk_level' => $results['total_level'] ?? 'Sin hallazgos críticos detectados',
            'total_score' => $results['total'] ?? 0,
            'requires_clinical_valuation' => $results['requires_attention'] ?? false,
            'completed_at' => now(),
        ]);

        return redirect()->route('nom035.resultados', $respondent);
    }

    public function resultados(Nom035Respondent $respondent)
    {
        return Inertia::render('Nom035/Results', [
            'respondent' => $respondent->load('period', 'empleado')
        ]);
    }

    public function verRespuestas(Nom035Respondent $respondent)
    {
        return Inertia::render('Nom035/Answers', [
            'respondent' => $respondent->load('period', 'empleado'),
            'answers' => $respondent->answers()->with('question')->get()
        ]);
    }

    public function generarPDF(Nom035Respondent $respondent)
    {
        $respondent->load('empleado', 'period', 'answers.question');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.nom035_report', [
            'respondent' => $respondent,
            'completed_at_fmt' => \Carbon\Carbon::parse($respondent->completed_at)->format('d/m/Y H:i'),
            'empresa' => \DB::table('empresa_configuracion')->first(),
            'fecha' => now()->format('d/m/Y')
        ])->setPaper('letter')
          ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        return $pdf->stream("NOM035_Resultados_{$respondent->name}.pdf");
    }

    public function reporteGeneralPDF(Nom035EvaluationPeriod $periodo)
    {
        $respondents = Nom035Respondent::where('evaluation_period_id', $periodo->id)->get();
        $stats = $this->service->getAggregatedStats($respondents);
        
        $activities = \App\Models\Nom035Activity::where('empresa_id', $periodo->empresa_id)
            ->where('activity_date', '<=', $periodo->end_date)
            ->orderBy('activity_date', 'desc')
            ->get();
            
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.nom035_general_report', [
            'periodo' => $periodo,
            'start_date_fmt' => \Carbon\Carbon::parse($periodo->start_date)->format('d/m/Y'),
            'end_date_fmt' => \Carbon\Carbon::parse($periodo->end_date)->format('d/m/Y'),
            'stats' => $stats,
            'activities' => $activities->map(function($a) {
                $a->date_fmt = \Carbon\Carbon::parse($a->activity_date)->format('d/m/Y');
                return $a;
            }),
            'empresa' => \DB::table('empresa_configuracion')->first(),
            'fecha' => now()->format('d/m/Y')
        ]);

        return $pdf->stream("NOM035_Reporte_General_{$periodo->name}.pdf");
    }

    public function notificarPendientes(Nom035EvaluationPeriod $periodo)
    {
        $pendientes = Nom035Respondent::where('evaluation_period_id', $periodo->id)
            ->where('status', '!=', 'completed')
            ->with('empleado')
            ->get();

        $count = 0;
        $pushService = app(\App\Services\PushNotificationService::class);

        foreach ($pendientes as $res) {
            if ($res->empleado && $res->empleado->fcm_token) {
                $pushService->sendNotification(
                    $res->empleado->fcm_token,
                    'Recordatorio: NOM-035',
                    "Aún tienes pendiente completar tu evaluación de la NOM-035. Tu participación es importante.",
                    ['type' => 'nom035', 'periodo_id' => $periodo->id]
                );
                $count++;
            }
        }

        return back()->with('success', "Se enviaron $count notificaciones Push a los colaboradores pendientes.");
    }

    public function cerrar(Nom035EvaluationPeriod $periodo)
    {
        $periodo->update([
            'status' => 'closed',
            'active' => false,
            'end_date' => now(),
        ]);

        return redirect()->route('nom035.index')->with('success', "El periodo {$periodo->name} ha sido cerrado correctamente.");
    }

    public function seguimientoValuacion(Request $request, Nom035Respondent $respondent)
    {
        $data = $request->validate([
            'clinical_valuation_status' => 'required|string',
            'clinical_valuation_notes' => 'nullable|string',
            'clinical_valuation_date' => 'nullable|date',
            'clinical_valuation_evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        if ($request->hasFile('clinical_valuation_evidence')) {
            $path = $request->file('clinical_valuation_evidence')->store('nom035/evidence', 'public');
            $data['clinical_valuation_evidence'] = $path;
        }

        $respondent->update($data);

        return back()->with('success', 'Seguimiento actualizado correctamente.');
    }

    public function verify($uuid)
    {
        $respondent = Nom035Respondent::where('uuid', $uuid)->firstOrFail();
        $empresa = \DB::table('empresa_configuracion')->first();
        
        return view('pdf.nom035_verify', [
            'respondent' => $respondent,
            'empresa' => $empresa
        ]);
    }

    public function auditPdf()
    {
        $empresa_id = \App\Support\EmpresaResolver::resolveId();
        $empresa = \DB::table('empresa_configuracion')->first();

        $config = \App\Models\Nom035Configuration::where('empresa_id', $empresa_id)->first();
        $activities = \App\Models\Nom035Activity::where('empresa_id', $empresa_id)->orderBy('activity_date', 'desc')->get();
        $periods = Nom035EvaluationPeriod::where('empresa_id', $empresa_id)->orderBy('id', 'desc')->get();
        $activePeriod = $periods->firstWhere('active', true) ?? $periods->first();
        $respondents = $activePeriod ? Nom035Respondent::where('evaluation_period_id', $activePeriod->id)->get() : collect();
        $complaints = \App\Models\Nom035Complaint::where('empresa_id', $empresa_id)->orderBy('created_at', 'desc')->get();
        $acceptances = \DB::table('nom035_policy_acceptances')->where('empresa_id', $empresa_id)->count();

        $sections = [
            [
                'numeral' => '5.1',
                'name' => 'Política de Prevención',
                'status' => !empty($config?->policy_content),
                'evidence' => $config?->policy_content ? 'Política documentada en sistema' . ($config->policy_pdf_path ? ' + PDF firmado' : '') : 'Sin definir',
                'last_update' => $config?->updated_at?->format('d/m/Y') ?? '-',
            ],
            [
                'numeral' => '7.1-7.3',
                'name' => 'Identificación de Factores de Riesgo',
                'status' => $activePeriod && $respondents->count() > 0,
                'evidence' => $activePeriod ? "Periodo: {$activePeriod->name} | {$respondents->count()} evaluaciones" : 'Sin periodo activo',
                'last_update' => $activePeriod?->updated_at?->format('d/m/Y') ?? '-',
            ],
            [
                'numeral' => '8.1-8.5',
                'name' => 'Análisis de Resultados',
                'status' => $respondents->where('status', 'completed')->count() > 0,
                'evidence' => $respondents->where('status', 'completed')->count() . ' evaluaciones completadas de ' . $respondents->count(),
                'last_update' => $respondents->where('status', 'completed')->sortByDesc('completed_at')->first()?->completed_at?->format('d/m/Y') ?? '-',
            ],
            [
                'numeral' => '9.1-9.3',
                'name' => 'Constancias Individuales',
                'status' => $respondents->where('status', 'completed')->count() > 0,
                'evidence' => $respondents->where('status', 'completed')->count() . ' constancias disponibles para emisión',
                'last_update' => $respondents->where('status', 'completed')->sortByDesc('completed_at')->first()?->completed_at?->format('d/m/Y') ?? '-',
            ],
            [
                'numeral' => '10.1-10.2',
                'name' => 'Medidas de Control y Prevención',
                'status' => $activities->count() > 0,
                'evidence' => $activities->count() . ' actividades registradas: ' . $activities->take(3)->pluck('title')->implode(', '),
                'last_update' => $activities->first()?->activity_date?->format('d/m/Y') ?? '-',
            ],
            [
                'numeral' => '5.6',
                'name' => 'Mecanismo de Denuncias',
                'status' => true,
                'evidence' => $complaints->count() . ' denuncias recibidas | ' . $complaints->where('status', 'resolved')->count() . ' resueltas',
                'last_update' => $complaints->first()?->created_at?->format('d/m/Y') ?? 'Sistema activo',
            ],
            [
                'numeral' => '5.4',
                'name' => 'Capacitación y Difusión',
                'status' => $acceptances > 0 || $activities->where('type', 'Capacitacion')->count() > 0,
                'evidence' => $acceptances . ' aceptaciones de política | ' . $activities->where('type', 'Capacitacion')->count() . ' capacitaciones',
                'last_update' => $activities->where('type', 'Capacitacion')->first()?->activity_date?->format('d/m/Y') ?? ($acceptances > 0 ? 'Ver matriz de aceptaciones' : '-'),
            ],
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.nom035_audit', [
            'empresa' => $empresa,
            'sections' => $sections,
            'fecha' => now()->format('d/m/Y'),
        ]);

        return $pdf->stream('NOM035_Auditoria_Cumplimiento.pdf');
    }
}
