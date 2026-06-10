<?php

namespace App\Http\Controllers;

use App\Models\Nom035Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\EmpresaResolver;

class Nom035ActivityController extends Controller
{
    public function index()
    {
        $empresa_id = EmpresaResolver::resolveId();
        $activities = Nom035Activity::where('empresa_id', $empresa_id)
            ->orderBy('activity_date', 'desc')
            ->get();

        return Inertia::render('Nom035/Activities', [
            'activities' => $activities
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'activity_date' => 'required|date',
            'participants_count' => 'nullable|string',
            'evidence_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'status' => 'required|string'
        ]);

        $data['empresa_id'] = EmpresaResolver::resolveId();

        if ($request->hasFile('evidence_file')) {
            $path = $request->file('evidence_file')->store('nom035/activities', 'public');
            $data['evidence_file'] = $path;
        }

        Nom035Activity::create($data);

        return back()->with('success', 'Actividad registrada correctamente.');
    }

    public function destroy(Nom035Activity $activity)
    {
        if ($activity->empresa_id !== EmpresaResolver::resolveId()) {
            abort(403);
        }

        $activity->delete();
        return back()->with('success', 'Actividad eliminada.');
    }
}
