<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmpresaWhatsAppController extends Controller
{
    /**
     * Mostrar configuración de WhatsApp
     */
    public function index()
    {
        try {
            $empresaId = EmpresaResolver::resolveId();
            
            if (!$empresaId) {
                return redirect()->route('empresas.index')
                    ->with('error', 'Debe seleccionar o crear una empresa primero');
            }

            $empresa = Empresa::findOrFail($empresaId);

            return Inertia::render('EmpresaConfiguracion/WhatsAppConfig', [
                'empresa' => $empresa->only([
                    'id',
                    'nombre_razon_social',
                    'whatsapp_enabled',
                    'whatsapp_business_account_id',
                    'whatsapp_phone_number_id',
                    'whatsapp_sender_phone',
                    'whatsapp_access_token',
                    'whatsapp_app_secret',
                    'whatsapp_webhook_verify_token',
                    'whatsapp_default_language',
                    'whatsapp_template_payment_reminder',
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar configuración de WhatsApp: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar la configuración');
        }
    }

    /**
     * Actualizar configuración de WhatsApp
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'whatsapp_enabled' => 'nullable|boolean',
                'whatsapp_business_account_id' => 'nullable|string|max:255',
                'whatsapp_phone_number_id' => 'nullable|string|max:255',
                'whatsapp_sender_phone' => 'nullable|string|max:20|regex:/^\\+[1-9]\\d{1,14}$/',
                'whatsapp_access_token' => 'nullable|string',
                'whatsapp_app_secret' => 'nullable|string|max:255',
                'whatsapp_webhook_verify_token' => 'nullable|string|max:255',
                'whatsapp_default_language' => 'nullable|string|in:es_MX,en_US',
                'whatsapp_template_payment_reminder' => 'nullable|string|max:255',
            ]);

            $empresaId = EmpresaResolver::resolveId();
            
            if (!$empresaId) {
                return redirect()->route('empresas.index')
                    ->with('error', 'Seleccione una empresa para actualizar su configuración');
            }

            $empresa = Empresa::findOrFail($empresaId);

            $data = $validated;
            if ($request->has('whatsapp_enabled')) {
                $data['whatsapp_enabled'] = $request->boolean('whatsapp_enabled');
            }

            // Actualizar empresa
            $empresa->update($data);

            Log::info('Configuración de WhatsApp actualizada', [
                'empresa_id' => $empresa->id,
                'whatsapp_enabled' => $data['whatsapp_enabled'] ?? $empresa->whatsapp_enabled,
            ]);

            return redirect()->back()->with('success', 'Configuración de WhatsApp actualizada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error al actualizar configuración de WhatsApp: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar la configuración');
        }
    }

    /**
     * Probar configuración de WhatsApp
     */
    public function test(Request $request)
    {
        try {
            Log::info('Iniciando prueba de WhatsApp', [
                'telefono' => $request->input('telefono'),
                'remote_ip' => $request->ip(),
            ]);

            $validated = $request->validate([
                'telefono' => 'required|string',
                'mensaje' => 'required|string|max:1000',
                'template_name' => 'nullable|string|max:255',
            ]);

            Log::info('Datos validados', ['validated' => $validated]);

            $empresaId = EmpresaResolver::resolveId();
            
            if (!$empresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo resolver el contexto de la empresa'
                ], 403);
            }

            $empresa = Empresa::findOrFail($empresaId);

            Log::info('Empresa encontrada', [
                'empresa_id' => $empresa->id,
                'whatsapp_enabled' => $empresa->whatsapp_enabled,
            ]);

            if (!$empresa->whatsapp_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp no está habilitado para esta empresa'
                ], 400);
            }

            // Verificar que todos los campos requeridos estén configurados
            $requiredFields = [
                'whatsapp_business_account_id',
                'whatsapp_phone_number_id',
                'whatsapp_sender_phone',
                'whatsapp_access_token',
            ];

            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (empty($empresa->$field)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                Log::warning('Campos faltantes en configuración de WhatsApp', [
                    'missing_fields' => $missingFields,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Campos requeridos no configurados: ' . implode(', ', $missingFields)
                ], 400);
            }

            // Crear servicio WhatsApp para validar configuración y enviar prueba real
            try {
                Log::info('Creando servicio WhatsApp para envío de prueba');
                $whatsappService = \App\Services\WhatsAppService::fromEmpresa($empresa);

                // Usar plantilla explícita, la configurada en empresa o fallback hello_world
                $templateName = $validated['template_name']
                    ?? $empresa->whatsapp_template_payment_reminder
                    ?? 'hello_world';
                $language = $empresa->whatsapp_default_language ?: 'es_MX';
                
                Log::info('Enviando mensaje de prueba real', [
                    'to' => $validated['telefono'],
                    'template' => $templateName,
                    'language' => $language
                ]);

                // hello_world no requiere parámetros; otras plantillas pueden requerirlos.
                $params = $templateName === 'hello_world' ? [] : ['Cliente de Prueba'];

                $response = $whatsappService->sendTemplate(
                    $validated['telefono'],
                    $templateName,
                    $language,
                    $params
                );

                return response()->json([
                    'success' => true,
                    'message' => '¡Mensaje de prueba enviado exitosamente!',
                    'whatsapp_response' => $response,
                    'note' => 'Si no recibes el mensaje, verifica que tu número esté autorizado en el panel de Meta.'
                ]);

            } catch (\Exception $e) {
                Log::error('Error en el envío de prueba de WhatsApp', [
                    'error' => $e->getMessage(),
                    'empresa_id' => $empresa->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar: ' . $e->getMessage()
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación en prueba de WhatsApp', [
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error inesperado en prueba de WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
