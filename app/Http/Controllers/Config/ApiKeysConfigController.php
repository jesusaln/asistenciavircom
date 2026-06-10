<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiKeysConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ai_provider' => 'nullable|string|in:gemini,groq,ollama',
            'groq_api_key' => 'nullable|string|max:500',
            'groq_model' => 'nullable|string|max:100',
            'groq_temperature' => 'nullable|numeric|min:0|max:2',
            'ollama_base_url' => 'nullable|string|max:255',
            'ollama_model' => 'nullable|string|max:100',
            'chatbot_enabled' => 'nullable|boolean',
            'chatbot_system_prompt' => 'nullable|string|max:5000',
            'chatbot_name' => 'nullable|string|max:100',
            // Gemini
            'gemini_api_key' => 'nullable|string|max:500',
            'gemini_model' => 'nullable|string|max:100',
            'gemini_temperature' => 'nullable|numeric|min:0|max:1',
            // WhatsApp (Meta)
            'whatsapp_business_account_id' => 'nullable|string|max:255',
            'whatsapp_phone_number_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuración API Keys', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validatedData = $validator->validated();
        
        // Separar datos para Empresa y EmpresaConfiguracion
        $whatsappData = [
            'whatsapp_business_account_id' => $validatedData['whatsapp_business_account_id'] ?? null,
            'whatsapp_phone_number_id' => $validatedData['whatsapp_phone_number_id'] ?? null,
        ];

        $aiData = array_diff_key($validatedData, $whatsappData);

        // Manejar booleanos
        if ($request->has('chatbot_enabled')) {
            $aiData['chatbot_enabled'] = $request->boolean('chatbot_enabled');
        }

        // No sobreescribir API keys si vienen vacías
        if (empty($aiData['groq_api_key'])) unset($aiData['groq_api_key']);
        if (empty($aiData['gemini_api_key'])) unset($aiData['gemini_api_key']);

        // 1. Actualizar EmpresaConfiguracion
        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($aiData);
        EmpresaConfiguracion::clearCache();

        // 2. Actualizar Empresa (modelo real de WhatsApp)
        $empresaId = \App\Support\EmpresaResolver::resolveId();
        $empresa = $empresaId ? \App\Models\Empresa::find($empresaId) : \App\Models\Empresa::first();
        
        if ($empresa) {
            $empresa->update(array_filter($whatsappData));
        }

        return redirect()->back()->with('success', 'Configuración de API Keys e IA actualizada correctamente.');
    }
}
