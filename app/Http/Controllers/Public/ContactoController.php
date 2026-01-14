<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use App\Models\Empresa;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\CrmProspecto;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContactoController extends Controller
{
    public function index()
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = Empresa::find($empresaId);
        $configuracion = EmpresaConfiguracion::getConfig($empresaId);

        $empresa = $empresaModel ? array_merge($empresaModel->toArray(), [
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

        return Inertia::render('Public/Contacto', [
            'empresa' => $empresa,
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

                // 2. CONVERTIR A CLIENTE O VINCULAR EXISTENTE
                $cliente = null;

                if ($validated['telefono']) {
                    $cliente = Cliente::where('telefono', $validated['telefono'])
                        ->where('empresa_id', $empresaId)
                        ->first();
                }

                if (!$cliente && $validated['email']) {
                    $cliente = Cliente::where('email', $validated['email'])
                        ->where('empresa_id', $empresaId)
                        ->first();
                }

                if (!$cliente) {
                    // Convertir prospecto a cliente
                    $cliente = $prospecto->convertirACliente();
                } else {
                    // Vincular si ya existía el cliente
                    $prospecto->update(['cliente_id' => $cliente->id]);
                }

                // 3. REGISTRAR ACTIVIDAD (Opcional)
                try {
                    $systemUserId = \App\Models\User::role('super-admin')->value('id') ?? 1;
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

                // =====================================================
                // 2. CONVERTIR A CLIENTE O BUSCAR EXISTENTE
                // =====================================================
                $cliente = Cliente::where('telefono', $validated['telefono'])
                    ->where('empresa_id', $empresaId)
                    ->first();

                if (!$cliente) {
                    // Si tiene prospecto y no es cliente, convertirlo
                    if ($prospecto && !$prospecto->cliente_id) {
                        $cliente = $prospecto->convertirACliente();
                    } else {
                        // Crear cliente directamente
                        $cliente = Cliente::create([
                            'empresa_id' => $empresaId,
                            'nombre_razon_social' => $validated['nombre'],
                            'telefono' => $validated['telefono'],
                            'email' => $validated['email'] ?? null,
                            'tipo' => 'persona_fisica',
                            'origen' => 'landing_page',
                            'activo' => true,
                        ]);

                        // Vincular cliente al prospecto
                        $prospecto->update(['cliente_id' => $cliente->id]);
                    }
                }

                // =====================================================
                // 3. VALIDAR DISPONIBILIDAD Y CREAR LA CITA
                // =====================================================
                $fechaCita = $validated['fecha_preferida'] ?? now()->addDay()->format('Y-m-d');
                $horaCita = $validated['hora_preferida'] ?? '10:00';

                // Crear el datetime completo
                $fechaHoraCita = \Carbon\Carbon::parse("{$fechaCita} {$horaCita}");

                // Calcular duración (+1 hora para mantenimiento, +2 para instalación)
                $duracionHoras = in_array($validated['servicio'], ['instalacion', 'cotizacion']) ? 2 : 1;

                // Verificar si ya existe una cita en ese horario (margen de 1 hora antes/después)
                $citaExistente = Cita::where('empresa_id', $empresaId)
                    ->whereDate('fecha_hora', $fechaCita)
                    ->where('estado', '!=', 'cancelado')
                    ->whereBetween('fecha_hora', [
                        $fechaHoraCita->copy()->subHours($duracionHoras),
                        $fechaHoraCita->copy()->addHours($duracionHoras)
                    ])
                    ->first();

                if ($citaExistente) {
                    // Horario no disponible
                    return back()->withErrors([
                        'hora_preferida' => "Lo sentimos, el horario de las {$horaCita} del " . date('d/m/Y', strtotime($fechaCita)) . " ya está ocupado. Por favor selecciona otro horario o fecha."
                    ])->withInput();
                }

                // Obtener un técnico/admin por defecto para asignar la cita
                $tecnicoId = \App\Models\User::role('super-admin')->value('id') ?? 1;

                $cita = Cita::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'tecnico_id' => $tecnicoId,
                    'tipo_servicio' => $tituloServicio,
                    'problema_reportado' => $validated['descripcion'] ?? "Solicitud desde landing page: {$tituloServicio}",
                    'descripcion' => $validated['descripcion'] ?? "Solicitud desde landing page: {$tituloServicio}",
                    'fecha_hora' => $fechaHoraCita,
                    'prioridad' => 'media',
                    'estado' => 'pendiente',
                    'notas' => "Prospecto CRM ID: {$prospecto->id}\nCliente ID: {$cliente->id}\nOrigen: Landing Page",
                ]);

                // =====================================================
                // 4. REGISTRAR ACTIVIDAD EN EL PROSPECTO (Opcional)
                // =====================================================
                // Solo registrar actividad si podemos hacerlo sin errores
                // (el user_id puede ser null en solicitudes públicas)
                try {
                    if (method_exists($prospecto, 'actividades')) {
                        // Intentar obtener un usuario sistema o el primer admin
                        $systemUserId = \App\Models\User::where('email', 'sistema@' . request()->getHost())
                            ->orWhere('email', 'admin@' . request()->getHost())
                            ->value('id');

                        // Si no hay usuario sistema, intentar con el primer super-admin
                        if (!$systemUserId) {
                            $systemUserId = \App\Models\User::role('super-admin')->value('id');
                        }

                        // Solo crear actividad si tenemos un user_id válido
                        if ($systemUserId) {
                            $prospecto->actividades()->create([
                                'tipo' => 'nota',
                                'resultado' => 'contactado',
                                'notas' => "✅ Cita agendada automáticamente desde la landing page.\n" .
                                    "Servicio: {$tituloServicio}\n" .
                                    "Fecha: {$fechaCita} {$horaCita}",
                                'empresa_id' => $empresaId,
                                'user_id' => $systemUserId,
                            ]);
                        }
                    }
                } catch (\Exception $activityError) {
                    // No fallar si la actividad no se puede crear
                    Log::warning('No se pudo registrar actividad del prospecto', [
                        'prospecto_id' => $prospecto->id,
                        'error' => $activityError->getMessage(),
                    ]);
                }

                // Avanzar etapa del prospecto si está en la primera etapa
                if ($prospecto->etapa === 'prospecto') {
                    $prospecto->update(['etapa' => 'contactado']);
                }

                Log::info('Cita creada desde landing page', [
                    'prospecto_id' => $prospecto->id,
                    'cliente_id' => $cliente->id,
                    'cita_id' => $cita->id,
                    'servicio' => $tituloServicio,
                ]);

                return back()->with('success', '¡Cita agendada exitosamente! Te contactaremos pronto para confirmar los detalles.');
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
}
