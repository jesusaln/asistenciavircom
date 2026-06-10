<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use App\Models\Empresa;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\CrmProspecto;
use App\Mail\NuevaCitaMail;
use App\Support\EmpresaResolver;
use App\Jobs\SendWhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NuevaCitaPublicaNotification;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContactoController extends Controller
{
    public function index()
    {
        return Inertia::render('Public/Contacto', [
            'empresa' => $this->getPublicEmpresaData(),
        ]);
    }

    public function agendaRapida()
    {
        return Inertia::render('Public/AgendarCitaPremium', [
            'empresa' => $this->getPublicEmpresaData(),
        ]);
    }

    /**
     * Almacenar mensaje de contacto e integrar con CRM
     */
    public function store(Request $request)
    {
        // Limpiar teléfono: solo números
        $telefono = $request->input('telefono');
        if ($telefono) {
            $telefonoLimpio = preg_replace('/\D/', '', $telefono);
            $request->merge(['telefono' => $telefonoLimpio]);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'nullable|string|digits:10',
            'asunto' => 'nullable|string|max:255',
            'mensaje' => 'required|string|max:2000',
        ], [
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
        ]);

        // Convertir nombre a MAYÚSCULAS para consistencia en CRM
        $validated['nombre'] = mb_strtoupper($validated['nombre']);
        $empresaId = EmpresaResolver::resolveId();

        try {
            DB::transaction(function () use ($validated, $empresaId) {
                // Mapear asunto para notas
                $asuntos = [
                    'ventas' => 'Ventas / Cotización',
                    'soporte' => 'Soporte Técnico',
                    'polizas' => 'Pólizas de Servicio',
                    'otro' => 'Otro Motivo',
                ];
                $asuntoLabel = $asuntos[$validated['asunto']] ?? $validated['asunto'] ?? 'Contacto General';

                $notasDetalladas = "✉️ Lead generado desde el Formulario de Contacto (Página Web)\n\n" .
                    "📋 ASUNTO: {$asuntoLabel}\n\n" .
                    "💬 MENSAJE:\n" .
                    $validated['mensaje'];

                // 1. BUSCAR O CREAR PROSPECTO EN CRM
                // Intentar buscar por teléfono o por email para evitar duplicados
                $prospecto = null;

                if ($validated['telefono']) {
                    $prospecto = CrmProspecto::where('telefono', $validated['telefono'])
                        ->where('empresa_id', $empresaId)
                        ->first();
                }

                if (!$prospecto && $validated['email']) {
                    $prospecto = CrmProspecto::where('email', $validated['email'])
                        ->where('empresa_id', $empresaId)
                        ->first();
                }

                if (!$prospecto) {
                    // Crear nuevo prospecto
                    $prospecto = CrmProspecto::create([
                        'empresa_id' => $empresaId,
                        'nombre' => $validated['nombre'],
                        'telefono' => $validated['telefono'],
                        'email' => $validated['email'],
                        'origen' => 'web',
                        'etapa' => 'prospecto',
                        'prioridad' => 'media',
                        'notas' => $notasDetalladas,
                    ]);
                } else {
                    // Actualizar prospecto existente
                    $prospecto->update([
                        'notas' => ($prospecto->notas ? $prospecto->notas . "\n\n---\n\n" : '') . $notasDetalladas,
                        'prioridad' => 'media',
                    ]);
                }

                // 2. Vincular si ya existía el cliente (pero NO crear nuevos clientes)
                $cliente = null;
                if ($validated['telefono']) {
                    $cliente = Cliente::where('telefono', $validated['telefono'])
                        ->where('empresa_id', $empresaId)
                        ->first();
                }

                if ($cliente) {
                    $prospecto->update(['cliente_id' => $cliente->id]);
                }

                // 3. REGISTRAR ACTIVIDAD (Opcional)
                try {
                    $systemUserId = User::role('super-admin')
                        ->where('empresa_id', $empresaId)
                        ->value('id');
                    if ($prospecto->actividades()) {
                        $prospecto->actividades()->create([
                            'tipo' => 'nota',
                            'resultado' => 'contactado',
                            'notas' => "✅ Mensaje de contacto recibido.\nAsunto: {$asuntoLabel}",
                            'empresa_id' => $empresaId,
                            'user_id' => $systemUserId,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('No se pudo registrar actividad en ContactoController', ['error' => $e->getMessage()]);
                }
            });

            return back()->with('success', '¡Mensaje enviado exitosamente! Nuestro equipo se pondrá en contacto contigo a la brevedad.');

        } catch (\Exception $e) {
            Log::error('Error en ContactoController@store CRM integration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'general' => 'Hubo un problema al procesar tu solicitud. Por favor intenta de nuevo más tarde.'
            ]);
        }
    }

    /**
     * Almacenar cita desde la landing page
     * Flujo completo: CRM Prospecto → Cliente → Cita
     */
    public function storeCita(Request $request)
    {
        // Limpiar teléfono: solo números
        $telefonoLimpio = preg_replace('/\D/', '', $request->input('telefono', ''));
        $request->merge(['telefono' => $telefonoLimpio]);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|digits:10',
            'email' => 'nullable|email|max:255',
            'servicio' => 'required|string|max:255',
            'fecha_preferida' => 'nullable|date',
            'hora_preferida' => 'nullable|string|max:10',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
        ]);

        // Convertir nombre a MAYÚSCULAS
        $validated['nombre'] = mb_strtoupper($validated['nombre']);

        $empresaId = EmpresaResolver::resolveId();

        try {
            return DB::transaction(function () use ($validated, $empresaId) {

                // Mapear el tipo de servicio a título legible
                $serviciosTitulos = [
                    'instalacion' => 'Instalación de Equipo',
                    'mantenimiento' => 'Mantenimiento Preventivo',
                    'reparacion' => 'Reparación / Emergencia',
                    'cotizacion' => 'Cotización de Proyecto',
                ];
                $tituloServicio = $serviciosTitulos[$validated['servicio']] ?? $validated['servicio'];

                // Notas detalladas para el CRM
                $notasDetalladas = "🌐 Lead generado desde el Formulario de Cita Rápida (Landing Page)\n\n" .
                    "📋 SERVICIO SOLICITADO:\n" .
                    "- Tipo: {$tituloServicio}\n\n" .
                    "📅 PREFERENCIA DE HORARIO:\n" .
                    "- Fecha: " . ($validated['fecha_preferida'] ?? 'Sin especificar') . "\n" .
                    "- Hora: " . ($validated['hora_preferida'] ?? 'Sin especificar') . "\n\n" .
                    "💬 DESCRIPCIÓN:\n" .
                    ($validated['descripcion'] ?? 'Sin detalles adicionales');

                // =====================================================
                // 1. BUSCAR O CREAR PROSPECTO EN CRM
                // =====================================================
                $prospecto = CrmProspecto::where('telefono', $validated['telefono'])
                    ->where('empresa_id', $empresaId)
                    ->first();

                if (!$prospecto) {
                    // Crear nuevo prospecto en el CRM
                    $prospecto = CrmProspecto::create([
                        'empresa_id' => $empresaId,
                        'nombre' => $validated['nombre'],
                        'telefono' => $validated['telefono'],
                        'email' => $validated['email'] ?? null,
                        'origen' => 'web',
                        'etapa' => 'prospecto',
                        'prioridad' => 'alta', // Alta prioridad porque solicitó cita
                        'notas' => $notasDetalladas,
                    ]);
                } else {
                    // Actualizar notas del prospecto existente
                    $prospecto->update([
                        'notas' => ($prospecto->notas ? $prospecto->notas . "\n\n---\n\n" : '') . $notasDetalladas,
                        'prioridad' => 'alta',
                    ]);
                }

                // NOTA: Se ha desactivado la creación automática de Cita y Cliente para solicitudes públicas.
                // El administrador debe convertir el prospecto a cliente desde el CRM para agendar.
                
                return back()->with('success', '¡Solicitud recibida! Hemos enviado tus datos al CRM. Te contactaremos pronto para confirmar tu cita.');
            });

        } catch (\Exception $e) {
            Log::error('Error al crear cita desde landing', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()->withErrors([
                'general' => 'Hubo un problema al registrar tu cita. Por favor intenta de nuevo o contáctanos por WhatsApp.'
            ]);
        }
    }

    public function storeAgendaRapida(Request $request)
    {
        $telefonoLimpio = preg_replace('/\D/', '', $request->input('telefono', ''));
        $request->merge(['telefono' => $telefonoLimpio]);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|digits:10',
            'mensaje' => 'nullable|string|max:1000',
            'horario_contacto' => 'nullable|string|max:100',
        ], [
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
        ]);

        $validated['nombre'] = mb_strtoupper(trim($validated['nombre']));
        $empresaId = EmpresaResolver::resolveId();

        try {
            DB::transaction(function () use ($validated, $empresaId) {
                $notasDetalladas = "📞 Solicitud de llamada para agendar cita desde /agendar-cita\n\n" .
                    "👤 Nombre: {$validated['nombre']}\n" .
                    "📱 Teléfono: {$validated['telefono']}\n" .
                    "🕐 Horario preferido para devolver llamada: " . ($validated['horario_contacto'] ?: 'Sin especificar') . "\n\n" .
                    "💬 Mensaje:\n" .
                    ($validated['mensaje'] ?: 'El cliente solicita que le llamen para ponerle cita.');

                $prospecto = CrmProspecto::where('telefono', $validated['telefono'])
                    ->where('empresa_id', $empresaId)
                    ->first();

                if (!$prospecto) {
                    $prospecto = CrmProspecto::create([
                        'empresa_id' => $empresaId,
                        'nombre' => $validated['nombre'],
                        'telefono' => $validated['telefono'],
                        'origen' => 'web',
                        'etapa' => 'prospecto',
                        'prioridad' => 'alta',
                        'notas' => $notasDetalladas,
                    ]);
                } else {
                    $prospecto->update([
                        'nombre' => $validated['nombre'],
                        'notas' => ($prospecto->notas ? $prospecto->notas . "\n\n---\n\n" : '') . $notasDetalladas,
                        'prioridad' => 'alta',
                    ]);
                }

                // NOTA: Se ha desactivado la creación automática de Cliente y Cita.
                // Los datos se guardan solo en el prospecto del CRM.
                
                if ($prospecto->etapa === 'prospecto') {
                    $prospecto->update(['etapa' => 'contactado']);
                }

                Log::info('Lead de agenda rápida creado en CRM', ['prospecto_id' => $prospecto->id]);
            });

            return redirect()
                ->route('public.agenda-rapida')
                ->with('success', 'Recibimos tus datos. Te llamaremos pronto para confirmar tu cita.');
        } catch (\Exception $e) {
            Log::error('Error al registrar agenda rápida pública', [
                'error' => $e->getMessage(),
                'telefono' => $validated['telefono'],
            ]);

            return back()->withErrors([
                'general' => 'No pudimos registrar tu solicitud en este momento. Intenta de nuevo o escríbenos por WhatsApp.'
            ])->withInput();
        }
    }

    private function getPublicEmpresaData(): ?array
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = Empresa::find($empresaId);
        $configuracion = EmpresaConfiguracion::getConfig($empresaId);

        return $empresaModel ? array_merge($empresaModel->toArray(), [
            'color_principal' => $configuracion->color_principal,
            'color_secundario' => $configuracion->color_secundario,
            'color_terciario' => $configuracion->color_terciario,
            'logo_url' => $configuracion->logo_url,
            'favicon_url' => $configuracion->favicon_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
            'telefono' => $configuracion->telefono,
            'email' => $configuracion->email,
            'direccion' => $configuracion->direccion,
            'whatsapp' => $configuracion->whatsapp,
        ]) : null;
    }
}
