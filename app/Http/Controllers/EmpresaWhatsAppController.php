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
            // Obtener la primera empresa (por simplicidad)
            // En un entorno multi-empresa, esto debería filtrar por la empresa del usuario
            $empresaId = EmpresaResolver::resolveId();
            $empresa = $empresaId ? Empresa::find($empresaId) : Empresa::first();

            if (!$empresa) {
                return redirect()->route('empresas.index')
                    ->with('error', 'Debe crear una empresa primero');
            }

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
            $empresa = $empresaId ? Empresa::find($empresaId) : Empresa::first();
            if (!$empresa) {
                $configuracion = \App\Models\EmpresaConfiguracion::getConfig();
                $tipoPersona = strlen($configuracion->rfc ?? '') === 12 ? 'moral' : 'fisica';
                $empresa = Empresa::create([
                    'nombre_razon_social' => $configuracion->razon_social ?? 'Empresa',
                    'tipo_persona' => $tipoPersona,
                    'rfc' => $configuracion->rfc ?? null,
                    'regimen_fiscal' => $configuracion->regimen_fiscal ?? '601',
                    'uso_cfdi' => $configuracion->uso_cfdi ?? 'G01',
                    'email' => $configuracion->email ?? null,
                    'telefono' => $configuracion->telefono ?? null,
                    'calle' => $configuracion->calle ?? 'Calle',
                    'numero_exterior' => $configuracion->numero_exterior ?? '1',
                    'numero_interior' => $configuracion->numero_interior ?? null,
                    'colonia' => $configuracion->colonia ?? 'Colonia',
                    'codigo_postal' => $configuracion->codigo_postal ?? '00000',
                    'municipio' => $configuracion->ciudad ?? 'Ciudad',
                    'estado' => $configuracion->estado ?? 'Estado',
                    'pais' => $configuracion->pais ?? 'México',
                ]);
            }

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
                'request_data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            $validated = $request->validate([
                'telefono' => 'required|string',
                'mensaje' => 'required|string|max:1000',
            ]);

            Log::info('Datos validados', ['validated' => $validated]);

            $empresaId = EmpresaResolver::resolveId();
            $empresa = $empresaId ? Empresa::find($empresaId) : Empresa::first();

            if (!$empresa) {
                Log::warning('Empresa no encontrada en prueba de WhatsApp');
                return response()->json([
                    'success' => false,
                    'message' => 'Empresa no encontrada'
                ], 404);
            }

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
                'whatsapp_app_secret',
                'whatsapp_template_payment_reminder',
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

            // Crear servicio WhatsApp para validar configuración
            try {
                Log::info('Creando servicio WhatsApp para validación');
                $whatsappService = \App\Services\WhatsAppService::fromEmpresa($empresa);

                // Solo validar que podemos crear el servicio (no enviar mensaje real)
                $configInfo = [
                    'phone_number_id' => $empresa->whatsapp_phone_number_id,
                    'business_account_id' => $empresa->whatsapp_business_account_id,
                    'sender_phone' => $empresa->whatsapp_sender_phone,
                    'default_language' => $empresa->whatsapp_default_language,
                    'template_name' => $empresa->whatsapp_template_payment_reminder,
                ];

                Log::info('Configuración validada exitosamente', ['config' => $configInfo]);

                return response()->json([
                    'success' => true,
                    'message' => 'Configuración de WhatsApp válida y completa',
                    'empresa' => $empresa->nombre_razon_social,
                    'config' => $configInfo,
                    'note' => 'Para enviar mensajes reales, necesitas crear y aprobar la plantilla en Meta Business'
                ]);

            } catch (\Exception $e) {
                Log::error('Error creando servicio WhatsApp', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error en configuración de WhatsApp: ' . $e->getMessage()
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
