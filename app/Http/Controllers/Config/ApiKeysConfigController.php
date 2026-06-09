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
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuración API Keys', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Manejar booleanos
        if ($request->has('chatbot_enabled')) {
            $data['chatbot_enabled'] = $request->boolean('chatbot_enabled');
        }

        // No sobreescribir API keys si vienen vacías (campo sensible no precargado)
        if (empty($data['groq_api_key'])) {
            unset($data['groq_api_key']);
        }
        if (empty($data['gemini_api_key'])) {
            unset($data['gemini_api_key']);
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de IA actualizada correctamente.');
    }
}
