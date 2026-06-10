<?php

namespace App\Http\Controllers;

use App\Models\Nom035Complaint;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\EmpresaResolver;

class Nom035ComplaintController extends Controller
{
    public function index()
    {
        $empresa_id = EmpresaResolver::resolveId();
        $complaints = Nom035Complaint::where('empresa_id', $empresa_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Nom035/Complaints/Index', [
            'complaints' => $complaints
        ]);
    }

    public function show(Nom035Complaint $complaint)
    {
        $this->authorizeAccess($complaint);

        return Inertia::render('Nom035/Complaints/Show', [
            'complaint' => $complaint
        ]);
    }

    public function update(Request $request, Nom035Complaint $complaint)
    {
        $this->authorizeAccess($complaint);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,in_review,resolved,dismissed',
            'admin_notes' => 'nullable|string',
            'resolution_details' => 'nullable|string',
        ]);

        $updateData = [
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
            'resolution_details' => $validated['resolution_details'],
        ];

        if ($validated['status'] === 'resolved' && !$complaint->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $complaint->update($updateData);

        return back()->with('success', 'Denuncia actualizada correctamente.');
    }

    private function authorizeAccess(Nom035Complaint $complaint)
    {
        if ($complaint->empresa_id !== EmpresaResolver::resolveId()) {
            abort(403);
        }
    }

    public function showPublicForm()
    {
        return Inertia::render('Nom035/Denuncia');
    }

    public function submitPublicComplaint(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:violencia,condiciones,acoso,otro',
            'description' => 'required|string|min:10',
            'incident_date' => 'nullable|date',
            'is_anonymous' => 'required|boolean',
            'reporter_name' => 'nullable|string|required_if:is_anonymous,false',
            'reporter_email' => 'nullable|email|required_if:is_anonymous,false',
            'evidence' => 'nullable|array',
            'evidence.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $empresa_id = EmpresaResolver::resolveId();

        $evidencePaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('nom035/evidence', 'public');
                $evidencePaths[] = $path;
            }
        }

        $complaint = Nom035Complaint::create([
            'empresa_id' => $empresa_id,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'incident_date' => $validated['incident_date'] ?? null,
            'is_anonymous' => $validated['is_anonymous'],
            'reporter_name' => $validated['is_anonymous'] ? null : ($validated['reporter_name'] ?? null),
            'reporter_email' => $validated['is_anonymous'] ? null : ($validated['reporter_email'] ?? null),
            'evidence_paths' => $evidencePaths,
            'status' => 'pending',
        ]);

        return Inertia::render('Nom035/Denuncia', [
            'success' => true,
            'folio' => $complaint->folio,
            'receipt_url' => route('nom035.complaint.receipt', $complaint->folio),
        ]);
    }
}
