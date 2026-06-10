<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nom035Complaint;
use Illuminate\Http\Request;
use App\Support\EmpresaResolver;
use Illuminate\Support\Facades\Log;

class Nom035ComplaintController extends Controller
{
    /**
     * Store a new complaint/denuncia from Ionic
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'description' => 'required|string|min:5',
            'incident_date' => 'nullable|date',
            'is_anonymous' => 'required|boolean',
            'reporter_name' => 'nullable|string|required_if:is_anonymous,false',
            'reporter_email' => 'nullable|email|required_if:is_anonymous,false',
        ]);

        try {
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
                'incident_date' => $validated['incident_date'],
                'is_anonymous' => $validated['is_anonymous'],
                'reporter_name' => $validated['is_anonymous'] ? null : $validated['reporter_name'],
                'reporter_email' => $validated['is_anonymous'] ? null : $validated['reporter_email'],
                'evidence_paths' => $evidencePaths,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tu denuncia ha sido registrada de forma segura con evidencia adjunta.',
                'folio' => $complaint->folio
            ]);

        } catch (\Exception $e) {
            Log::error('Error al registrar denuncia NOM-035: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al procesar su solicitud. Por favor intente más tarde.'
            ], 500);
        }
    }

    /**
     * Check status of a complaint by folio
     */
    public function checkStatus($folio)
    {
        $complaint = Nom035Complaint::where('folio', $folio)
            ->select('folio', 'status', 'created_at', 'resolved_at', 'resolution_details')
            ->first();

        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'Folio no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $complaint
        ]);
    }

    /**
     * Download the PDF receipt for a complaint
     */
    public function downloadReceipt($folio)
    {
        $complaint = Nom035Complaint::where('folio', $folio)->firstOrFail();
        $empresa = \DB::table('empresa_configuracion')->first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.nom035_complaint_receipt', [
            'complaint' => $complaint,
            'empresa' => $empresa
        ]);

        return $pdf->download("ACUSE_NOM035_{$folio}.pdf");
    }
}
