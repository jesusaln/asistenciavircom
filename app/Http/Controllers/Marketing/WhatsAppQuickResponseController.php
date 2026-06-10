<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppQuickResponse;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;

class WhatsAppQuickResponseController extends Controller
{
    public function index()
    {
        return response()->json(WhatsAppQuickResponse::all());
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()?->empresa_id ?? EmpresaResolver::resolveId();
        if (!$empresaId) {
            return response()->json([
                'message' => 'No se pudo determinar la empresa.',
            ], 403);
        }

        $validated = $request->validate([
            'shortcut' => 'required|string|unique:whats_app_quick_responses,shortcut,NULL,id,empresa_id,' . $empresaId,
            'message' => 'required|string',
            'type' => 'nullable|string|in:text,image',
            'url' => 'nullable|string|url',
        ]);
        
        $qr = WhatsAppQuickResponse::create(array_merge($validated, ['empresa_id' => $empresaId]));

        return response()->json($qr);
    }

    public function update(Request $request, WhatsAppQuickResponse $whatsAppQuickResponse)
    {
        $validated = $request->validate([
            'shortcut' => 'required|string|unique:whats_app_quick_responses,shortcut,' . $whatsAppQuickResponse->id . ',id,empresa_id,' . $whatsAppQuickResponse->empresa_id,
            'message' => 'required|string',
            'type' => 'nullable|string|in:text,image',
            'url' => 'nullable|string|url',
        ]);

        $whatsAppQuickResponse->update($validated);

        return response()->json($whatsAppQuickResponse);
    }

    public function destroy(WhatsAppQuickResponse $whatsAppQuickResponse)
    {
        $whatsAppQuickResponse->delete();
        return response()->json(['success' => true]);
    }
}
