<?php

namespace App\Http\Controllers;

use App\Models\Nom035Configuration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\EmpresaResolver;

class Nom035ConfigController extends Controller
{
    public function index()
    {
        $empresa_id = EmpresaResolver::resolveId();
        $config = Nom035Configuration::firstOrCreate(['empresa_id' => $empresa_id]);

        return Inertia::render('Nom035/Config', [
            'config' => $config
        ]);
    }

    public function updatePolicy(Request $request)
    {
        $empresa_id = EmpresaResolver::resolveId();
        $config = Nom035Configuration::firstOrCreate(['empresa_id' => $empresa_id]);

        $validated = $request->validate([
            'policy_content' => 'nullable|string',
            'responsible_name' => 'nullable|string|max:255',
            'responsible_position' => 'nullable|string|max:255',
        ]);

        $config->update($validated);

        return back()->with('success', 'Política actualizada correctamente.');
    }

    /**
     * API for Ionic to get the policy
     */
    public function getPolicyApi(Request $request)
    {
        $empresa_id = EmpresaResolver::resolveId();
        $user = $request->user();
        $config = Nom035Configuration::where('empresa_id', $empresa_id)->first();

        $accepted = \DB::table('nom035_policy_acceptances')
            ->where('user_id', $user->id)
            ->where('empresa_id', $empresa_id)
            ->exists();

        return response()->json([
            'success' => true,
            'policy' => $config ? $config->policy_content : 'No se ha definido una política todavía.',
            'accepted' => $accepted
        ]);
    }

    public function acceptPolicyApi(Request $request)
    {
        $empresa_id = EmpresaResolver::resolveId();
        $user = $request->user();

        \DB::table('nom035_policy_acceptances')->updateOrInsert(
            ['user_id' => $user->id, 'empresa_id' => $empresa_id],
            [
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Política aceptada correctamente.'
        ]);
    }

    public function uploadPolicyPdf(Request $request)
    {
        $empresa_id = EmpresaResolver::resolveId();
        $config = Nom035Configuration::firstOrCreate(['empresa_id' => $empresa_id]);

        $request->validate([
            'policy_pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($config->policy_pdf_path && \Storage::exists($config->policy_pdf_path)) {
            \Storage::delete($config->policy_pdf_path);
        }

        $path = $request->file('policy_pdf')->store('nom035/policies');
        $config->update(['policy_pdf_path' => $path]);

        return back()->with('success', 'PDF de política firmada subido correctamente.');
    }

    public function downloadPolicyPdf()
    {
        $empresa_id = EmpresaResolver::resolveId();
        $config = Nom035Configuration::where('empresa_id', $empresa_id)->first();

        if (!$config || !$config->policy_pdf_path || !\Storage::exists($config->policy_pdf_path)) {
            abort(404, 'No se ha subido el PDF de la política firmada.');
        }

        return \Storage::download($config->policy_pdf_path, 'politica_nom035_firmada.pdf');
    }

    public function getComplianceMatrix()
    {
        $empresa_id = EmpresaResolver::resolveId();
        
        // Empleados que deberían firmar (activos)
        $users = \App\Models\User::where('empresa_id', $empresa_id)
            ->where('active', true)
            ->select('id', 'name', 'email')
            ->get();

        $acceptances = \DB::table('nom035_policy_acceptances')
            ->where('empresa_id', $empresa_id)
            ->get()
            ->keyBy('user_id');

        $matrix = $users->map(function($user) use ($acceptances) {
            $acc = $acceptances->get($user->id);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'accepted' => (bool)$acc,
                'accepted_at' => $acc ? $acc->accepted_at : null,
                'ip' => $acc ? $acc->ip_address : null,
            ];
        });

        return Inertia::render('Nom035/ComplianceMatrix', [
            'matrix' => $matrix
        ]);
    }
}
