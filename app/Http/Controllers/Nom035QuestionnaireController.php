<?php

namespace App\Http\Controllers;

use App\Models\Nom035Question;
use App\Models\Nom035Respondent;
use App\Models\Nom035Answer;
use App\Models\Nom035EvaluationPeriod;
use App\Models\Empleado;
use App\Services\Nom035Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail; // We'll use a generic mail for now or create a specific one

class Nom035QuestionnaireController extends Controller
{
    public function index(Request $request): Response
    {
        $token = $request->get('t');
        $empleado = null;
        if ($token) {
            $empleado = Empleado::where('numero_empleado', $token)->first();
        }

        return Inertia::render('Nom035/Welcome', [
            'company_name' => 'Climas del Desierto',
            'center_type' => '16-50',
            'prefilled_employee' => $empleado ? $empleado->load('user') : null,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:255',
            'accepted_privacy' => 'required|accepted',
        ]);

        // Find active evaluation period
        $activePeriod = Nom035EvaluationPeriod::where('active', true)->first();
        if (!$activePeriod) {
            return back()->withErrors(['period' => 'No hay un periodo de evaluación activo.']);
        }

        // Link to local employee if exists
        $empleado = Empleado::join('users', 'empleados.user_id', '=', 'users.id')
            ->where('users.email', $validated['email'])
            ->select('empleados.*')
            ->first();

        $respondent = Nom035Respondent::create([
            'empresa_id' => 8,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'department' => $validated['department'],
            'position' => $validated['position'],
            'shift' => $validated['shift'],
            'center_size' => '16-50',
            'uuid' => (string) Str::uuid(),
            'evaluation_period_id' => $activePeriod->id,
            'empleado_id' => $empleado?->id,
            'privacy_accepted_at' => now(),
            'consent_ip' => $request->ip(),
            'status' => 'in_progress',
        ]);

        return redirect()->route('nom035.questionnaire.show', ['uuid' => $respondent->uuid]);
    }

    public function show(string $uuid, string $guide = 'I'): Response|RedirectResponse
    {
        $respondent = Nom035Respondent::where('uuid', $uuid)->firstOrFail();
        
        // Guide logic (I, II, III)
        $questions = Nom035Question::where('guide', $guide)->orderBy('order_index')->get();

        $existingAnswers = Nom035Answer::where('respondent_id', $respondent->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->pluck('value', 'question_id');

        return Inertia::render('Nom035/Questionnaire', [
            'respondent' => $respondent,
            'questions' => $questions,
            'currentGuide' => $guide,
            'existingAnswers' => $existingAnswers,
        ]);
    }

    public function submit(Request $request, string $uuid): RedirectResponse
    {
        $respondent = Nom035Respondent::where('uuid', $uuid)->firstOrFail();
        $validated = $request->validate([
            'guide' => 'required|in:I,II,III',
            'answers' => 'required|array|min:1',
        ]);

        foreach ($validated['answers'] as $qId => $value) {
            Nom035Answer::updateOrCreate(
                ['respondent_id' => $respondent->id, 'question_id' => $qId],
                ['value' => (int) $value, 'empresa_id' => 8]
            );
        }

        $service = new Nom035Service();
        $currentGuide = $validated['guide'];

        if ($currentGuide === 'I') {
            $respondent->update(['guide' => 'I']);
            $results = $service->calculateGuideI($respondent->answers()->get());
            $respondent->update([
                'requires_clinical_valuation' => $results['requires_attention'] ?? false,
                'risk_level' => $results['requires_attention'] ? 'Muy Alto' : 'Nulo'
            ]);

            // For 16-50 employees, transition to Guide II
            return redirect()->route('nom035.questionnaire.show', ['uuid' => $uuid, 'guide' => 'II']);
        }

        $respondent->update(['guide' => $currentGuide]);
        $results = ($currentGuide === 'II') ? $service->calculateGuideII($respondent->answers()->get()) : $service->calculateGuideIII($respondent->answers()->get());

        $respondent->update([
            'total_score' => $results['total'] ?? 0,
            'risk_level' => $results['total_level'] ?? 'Nulo',
            'status' => 'completed',
            'completed_at' => now()
        ]);

        return redirect()->route('nom035.questionnaire.results', ['uuid' => $uuid]);
    }

    public function results(string $uuid): Response
    {
        $respondent = Nom035Respondent::where('uuid', $uuid)->firstOrFail();
        $guide = $respondent->guide ?? 'II';

        $service = new Nom035Service();
        $results = [];
        
        if ($guide === 'I') {
            $results = $service->calculateGuideI($respondent->answers()->get());
        } elseif ($guide === 'II') {
            $results = $service->calculateGuideII($respondent->answers()->get());
        } else {
            $results = $service->calculateGuideIII($respondent->answers()->get());
        }

        return Inertia::render('Nom035/Results', [
            'respondent' => $respondent,
            'results' => $results,
            'guide' => $guide,
            'answers' => $respondent->answers()->with('question')->get(),
            'company_name' => 'Climas del Desierto',
            'needs_signature' => empty($respondent->signature_path)
        ]);
    }

    public function saveSignature(Request $request, string $uuid): RedirectResponse
    {
        $respondent = Nom035Respondent::where('uuid', $uuid)->firstOrFail();
        $validated = $request->validate([
            'signature' => 'required|string', // base64
        ]);

        $imageData = $validated['signature'];
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageName = 'signature_' . $uuid . '_' . time() . '.png';
        
        \Illuminate\Support\Facades\Storage::disk('public')->put('nom035/signatures/' . $imageName, base64_decode($imageData));

        $respondent->update([
            'signature_path' => 'nom035/signatures/' . $imageName,
            'signature_date' => now()
        ]);

        return back()->with('success', 'Firma registrada correctamente.');
    }
}
