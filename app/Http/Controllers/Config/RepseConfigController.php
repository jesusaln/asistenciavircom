<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;

class RepseConfigController extends Controller
{
    public function update(Request $request)
    {
        $config = EmpresaConfiguracion::getConfig();
        
        $validated = $request->validate([
            'repse_number' => 'nullable|string',
            'repse_expiry' => 'nullable|date',
            'repse_activity' => 'nullable|string',
            'registro_patronal_imss' => 'nullable|array',
            'registro_patronal_imss.*.nrp' => 'required|string',
            'registro_patronal_imss.*.description' => 'nullable|string',
            'responsible_name' => 'nullable|string',
            'responsible_position' => 'nullable|string',
            'repse_alert_days' => 'nullable|integer|min:0',
            'audit_contact_email' => 'nullable|email',
        ]);

        $config->update($request->only([
            'repse_number', 'repse_expiry', 'repse_activity', 'registro_patronal_imss', 'repse_alert_days', 'audit_contact_email'
        ]));

        if ($request->hasAny(['responsible_name', 'responsible_position'])) {
            \App\Models\Nom035Configuration::updateOrCreate(
                ['empresa_id' => \App\Support\EmpresaResolver::resolveId()],
                $request->only(['responsible_name', 'responsible_position'])
            );
        }

        return back()->with('success', 'Configuración legal y REPSE actualizada correctamente.');
    }

    public function uploadDoc(Request $request)
    {
        $request->validate([
            'type' => 'required|in:repse,acta,curp,csf',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $config = EmpresaConfiguracion::getConfig();
        $fields = [
            'repse' => ['path' => 'repse_constancia_path', 'name' => 'repse_constancia_name'],
            'acta' => ['path' => 'acta_constitutiva_path', 'name' => 'acta_constitutiva_name'],
            'curp' => ['path' => 'curp_pdf_path', 'name' => 'curp_pdf_name'],
            'csf' => ['path' => 'csf_pdf_path', 'name' => 'csf_pdf_name'],
        ];

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('repse/config', 'public');
        
        $config->update([
            $fields[$request->type]['path'] => $path,
            $fields[$request->type]['name'] => $originalName,
        ]);

        return back()->with('success', 'Documento legal subido correctamente.');
    }
}
