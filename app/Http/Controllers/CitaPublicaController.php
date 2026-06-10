<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Services\DisponibilidadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\WhatsAppService;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * Controlador para el agendamiento público de citas
 * (sin autenticación requerida)
 */
class CitaPublicaController extends Controller
{
    protected DisponibilidadService $disponibilidadService;

    public function __construct(DisponibilidadService $disponibilidadService)
    {
        $this->disponibilidadService = $disponibilidadService;
    }

    /**
     * Mostrar el formulario público de agendamiento
     */
    public function index(Request $request)
    {
        // Obtener la empresa del dominio o subdomain
        $empresa = $this->getEmpresaFromRequest($request);

        if (!$empresa) {
            abort(404, 'Empresa no encontrada');
        }

        // Obtener días disponibles del mes actual y siguiente
        $mesActual = Carbon::now()->month;
        $añoActual = Carbon::now()->year;

        $diasDisponibles = array_merge(
            $this->disponibilidadService->getDiasDisponibles($empresa->id, $mesActual, $añoActual),
            $this->disponibilidadService->getDiasDisponibles($empresa->id, $mesActual + 1, $añoActual)
        );

        // FALLBACK: Si no hay técnicos configurados, mostrar próximos 21 días (excepto domingos)
        if (empty($diasDisponibles)) {
            $diasDisponibles = $this->generarDiasFallback();
        }

        $config = \App\Models\EmpresaConfiguracion::getConfig($empresa->id);

        return Inertia::render('Public/AgendarCita', [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $config->nombre_empresa ?? ($empresa->nombre_empresa ?? $empresa->nombre),
                'logo' => $config->logo_url ?? ($empresa->logo_url ?? null),
                'color_principal' => $config->color_principal ?? ($empresa->color_principal ?? '#FF6B35'),
                'whatsapp' => $config->whatsapp ?? $empresa->whatsapp,
                'telefono' => $config->telefono ?? $empresa->telefono,
            ],
            'tiendas' => Cita::TIENDAS_ORIGEN,
            'horarios' => Cita::HORARIOS_PREFERIDOS,
            'diasDisponibles' => $diasDisponibles,
            'tiposServicio' => [
                'instalacion' => 'Instalación de equipo',
                'reparacion' => 'Reparación',
                'mantenimiento' => 'Mantenimiento',
                'revision' => 'Revisión / Diagnóstico',
            ],
            'tiposEquipo' => [
                'minisplit' => 'Minisplit',
                'ventana' => 'Aire de Ventana',
                'central' => 'Aire Central',
                'portatil' => 'Aire Portátil',
                'otro' => 'Otro',
            ],
        ]);
    }

    /**
     * Generar días de fallback cuando no hay técnicos configurados
     * Muestra los próximos 21 días hábiles (Lunes a Sábado)
     */
    private function generarDiasFallback(): array
    {
        $dias = [];
        $fecha = Carbon::tomorrow(); // Empezar desde mañana
        $diasAgregados = 0;

        $nombresDia = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado'
        ];

        while ($diasAgregados < 21) {
            // Saltar domingos
            if ($fecha->dayOfWeek !== 0) {
                $dias[] = [
                    'fecha' => $fecha->format('Y-m-d'),
                    'dia_semana' => $fecha->dayOfWeek,
                    'nombre_dia' => $nombresDia[$fecha->dayOfWeek],
                    'capacidad_total' => 5,
                    'citas_programadas' => 0,
                    'disponibles' => 5,
                    'porcentaje_ocupacion' => 0,
                ];
                $diasAgregados++;
            }
            $fecha->addDay();
        }

        return $dias;
    }

    /**
     * Guardar una nueva cita pública
     */
    public function store(Request $request)
    {
        $empresa = $this->getEmpresaFromRequest($request);

        if (!$empresa) {
            return back()->withErrors(['empresa' => 'Empresa no encontrada']);
        }

        $validated = $request->validate([
            // Datos personales
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',

            // Preferencias de fecha/hora
            'dias_preferidos' => 'required|array|min:1|max:5',
            'dias_preferidos.*' => 'date|after_or_equal:today',
        ]);

        try {
            DB::beginTransaction();

            // Buscar o crear cliente
            $cliente = Cliente::firstOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'telefono' => $validated['telefono'],
                ],
                [
                    'nombre_razon_social' => $validated['nombre'],
                    'email' => $request->input('email') ?: 'contacto@climasdeldesierto.com',
                    'direccion' => ($request->input('direccion_calle') ?: 'Por definir') . ', ' . ($request->input('direccion_colonia') ?: 'Por definir'),
                    'requiere_factura' => false,
                ]
            );

            // Actualizar nombre si cambió
            if ($cliente->nombre_razon_social !== $validated['nombre']) {
                $cliente->update(['nombre_razon_social' => $validated['nombre']]);
            }

            // Crear la cita
            $cita = Cita::create([
                'empresa_id' => $empresa->id,
                'cliente_id' => $cliente->id,
                'es_publica' => true,
                'estado' => Cita::ESTADO_PENDIENTE_ASIGNACION,
                'prioridad' => Cita::PRIORIDAD_MEDIA,

                // Servicio
                'tipo_servicio' => $request->input('tipo_servicio') ?: 'mantenimiento',
                'tipo_equipo' => $request->input('tipo_equipo') ?: 'minisplit',
                'descripcion' => $request->input('descripcion') ?: 'Solicitud de cita rápida en línea.',
                'problema_reportado' => $request->input('descripcion') ?: 'Solicitud de cita rápida en línea.',

                // Tienda
                'origen_tienda' => $request->input('origen_tienda') ?: 'otro',
                'numero_ticket_tienda' => $request->input('numero_ticket_tienda') ?? null,

                // Preferencias
                'dias_preferidos' => $validated['dias_preferidos'],
                'horario_preferido' => $request->input('horario_preferido') ?: 'manana',

                // Dirección
                'direccion_calle' => $request->input('direccion_calle') ?: 'Por definir',
                'direccion_colonia' => $request->input('direccion_colonia') ?: 'Por definir',
                'direccion_cp' => $request->input('direccion_cp') ?? null,
                'direccion_referencias' => $request->input('direccion_referencias') ?? null,

                // Seguimiento
                'link_seguimiento' => Str::uuid(),
            ]);

            DB::commit();

            // Enviar WhatsApp de confirmación de recepción
            $this->enviarWhatsAppRecepcion($cita);

            $config = \App\Models\EmpresaConfiguracion::getConfig($empresa->id);

            return Inertia::render('Public/AgendarCitaExito', [
                'empresa' => [
                    'nombre' => $config->nombre_empresa ?? ($empresa->nombre_empresa ?? $empresa->nombre),
                    'whatsapp' => $config->whatsapp ?? $empresa->whatsapp,
                    'color_principal' => $config->color_principal ?? ($empresa->color_principal ?? '#FF6B35'),
                ],
                'cita' => [
                    'folio' => $cita->folio,
                    'link_seguimiento' => $cita->link_seguimiento,
                    'url_seguimiento' => route('agendar.seguimiento', $cita->link_seguimiento),
                    'dias_preferidos' => $cita->dias_preferidos,
                    'horario_preferido' => $cita->horario_preferido,
                    'horario_info' => Cita::HORARIOS_PREFERIDOS[$cita->horario_preferido] ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cita pública: ' . $e->getMessage());

            return back()->withErrors([
                'general' => 'Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo.'
            ])->withInput();
        }
    }

    /**
     * API: Obtener días disponibles de un mes
     */
    public function disponibilidad(Request $request)
    {
        $empresa = $this->getEmpresaFromRequest($request);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        $mes = $request->input('mes', Carbon::now()->month);
        $año = $request->input('año', Carbon::now()->year);

        $dias = $this->disponibilidadService->getDiasDisponibles($empresa->id, $mes, $año);

        return response()->json([
            'mes' => $mes,
            'año' => $año,
            'dias' => $dias,
        ]);
    }

    /**
     * API: Obtener horarios disponibles para una fecha
     */
    public function horariosDisponibles(Request $request)
    {
        $empresa = $this->getEmpresaFromRequest($request);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        $fecha = $request->input('fecha');

        if (!$fecha) {
            return response()->json(['error' => 'Fecha requerida'], 400);
        }

        $horarios = $this->disponibilidadService->getHorariosDisponibles($empresa->id, $fecha);

        return response()->json([
            'fecha' => $fecha,
            'horarios' => $horarios,
        ]);
    }

    /**
     * Página pública de seguimiento de cita
     */
    public function seguimiento(string $uuid)
    {
        $cita = Cita::findByLink($uuid);

        if (!$cita) {
            abort(404, 'Cita no encontrada');
        }

        $cita->load(['cliente', 'tecnico', 'venta.items.ventable', 'venta.cuentaPorCobrar']);

        $empresa = Empresa::find($cita->empresa_id);
        $config = \App\Models\EmpresaConfiguracion::getConfig($empresa->id);

        // Timeline de estados
        $timeline = $this->buildTimeline($cita);

        // Datos financieros
        $cargos = null;
        if ($cita->venta) {
            $cargos = [
                'folio' => $cita->venta->numero_venta ?? $cita->venta->id,
                'total' => $cita->venta->total,
                'estado_pago' => $cita->venta->pagado ? 'pagado' : 'pendiente',
                'items' => $cita->venta->items->map(function ($item) {
                    return [
                        'nombre' => $item->ventable->nombre ?? 'Item eliminado',
                        'cantidad' => $item->cantidad,
                        'precio' => $item->precio,
                        'subtotal' => $item->subtotal, // Usar subtotal ya que es el final con descuentos
                    ];
                }),
                'fecha_vencimiento' => $cita->venta->cuentaPorCobrar?->fecha_vencimiento?->format('d/m/Y'),
            ];
        }

        return Inertia::render('Public/SeguimientoCita', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? ($empresa->nombre_empresa ?? $empresa->nombre),
                'logo' => $config->logo_url ?? ($empresa->logo_url ?? null),
                'color_principal' => $config->color_principal ?? ($empresa->color_principal ?? '#FF6B35'),
                'whatsapp' => $config->whatsapp ?? $empresa->whatsapp,
                'telefono' => $config->telefono ?? $empresa->telefono,
            ],
            'cita' => [
                'folio' => $cita->folio,
                'estado' => $cita->estado,
                'tipo_servicio' => $cita->tipo_servicio,
                'tipo_equipo' => $cita->tipo_equipo,
                'descripcion' => $cita->descripcion,
                'origen_tienda' => $cita->origen_tienda,
                'nombre_tienda' => $cita->nombre_tienda,
                'direccion_completa' => $cita->direccion_completa,
                'direccion_referencias' => $cita->direccion_referencias,
                // Preferencias
                'dias_preferidos' => $cita->dias_preferidos,
                'horario_preferido' => $cita->horario_preferido,
                'horario_info' => $cita->horario_preferido_info,
                // Confirmación
                'esta_confirmada' => $cita->esta_confirmada,
                'fecha_confirmada' => $cita->fecha_confirmada?->format('Y-m-d'),
                'hora_confirmada' => $cita->hora_confirmada,
                'hora_confirmada_rango' => $cita->hora_confirmada_rango,
                // Técnico
                'tecnico' => $cita->tecnico ? [
                    'nombre' => $cita->tecnico->name,
                    'telefono' => $cita->tecnico->telefono ?? null,
                ] : null,
                // Cliente
                'cliente' => [
                    'nombre' => $cita->cliente->nombre_razon_social,
                ],
                // Fechas
                'created_at' => $cita->created_at->format('d/m/Y H:i'),
            ],
            'timeline' => $timeline,
            'cargos' => $cargos,
        ]);
    }

    /**
     * Construir timeline de estados de la cita
     */
    private function buildTimeline(Cita $cita): array
    {
        $estados = [
            [
                'estado' => 'recibida',
                'nombre' => 'Solicitud Recibida',
                'icono' => '📥',
                'completado' => true,
                'fecha' => $cita->created_at->format('d/m/Y H:i'),
            ],
            [
                'estado' => 'confirmada',
                'nombre' => 'Cita Confirmada',
                'icono' => '✅',
                'completado' => $cita->esta_confirmada,
                'fecha' => $cita->fecha_confirmada?->format('d/m/Y') . ' ' . $cita->hora_confirmada,
            ],
            [
                'estado' => 'en_camino',
                'nombre' => 'Técnico en Camino',
                'icono' => '🚗',
                'completado' => $cita->estado === Cita::ESTADO_EN_PROCESO,
                'fecha' => null,
            ],
            [
                'estado' => 'completado',
                'nombre' => 'Servicio Completado',
                'icono' => '🎉',
                'completado' => $cita->estado === Cita::ESTADO_COMPLETADO,
                'fecha' => $cita->estado === Cita::ESTADO_COMPLETADO
                    ? $cita->fin_servicio?->format('d/m/Y H:i')
                    : null,
            ],
        ];

        // Si está cancelada, modificar el timeline
        if ($cita->estado === Cita::ESTADO_CANCELADO) {
            $estados = array_slice($estados, 0, 1);
            $estados[] = [
                'estado' => 'cancelada',
                'nombre' => 'Cita Cancelada',
                'icono' => '❌',
                'completado' => true,
                'fecha' => $cita->updated_at->format('d/m/Y H:i'),
            ];
        }

        return $estados;
    }

    /**
     * Obtener empresa del request (por dominio, subdomain o parámetro)
     */
    private function getEmpresaFromRequest(Request $request): ?Empresa
    {
        $host = $request->getHost();
        
        // 1. Intentar por dominio configurado en empresa_configuracion
        $config = \App\Models\EmpresaConfiguracion::where('dominio_principal', $host)
            ->orWhere('dominio_secundario', $host)
            ->first();
            
        if ($config && $config->empresa_id) {
            return Empresa::find($config->empresa_id);
        }

        // 2. Fallback: Usar el resolveId() que puede venir de sesión o tokens (si los hay)
        $resolvedId = \App\Support\EmpresaResolver::resolveId();
        if ($resolvedId) {
            return Empresa::find($resolvedId);
        }

        // 3. Ya no usamos fallback a Empresa::first() por seguridad multi-tenant.
        // Si no se pudo resolver la empresa, abortamos.
        abort(404, 'No se pudo identificar la empresa correspondiente.');
    }

    /**
     * Enviar WhatsApp de confirmación de recepción
     */
    private function enviarWhatsAppRecepcion(Cita $cita)
    {
        try {
            $empresa = $cita->empresa;

            if (!$empresa || !$empresa->whatsapp_enabled) {
                return;
            }

            // Verificar configuración mínima
            if (!$empresa->whatsapp_phone_number_id || !$empresa->whatsapp_access_token) {
                Log::warning("Empresa {$empresa->id} tiene WhatsApp habilitado pero sin credenciales completas.");
                return;
            }

            $whatsappService = WhatsAppService::fromEmpresa($empresa);

            if (!$cita->cliente || !$cita->cliente->telefono) {
                return;
            }

            // Datos para la plantilla
            // Plantilla estándar: confirmacion_cita_informativa
            // params: {{1}} Nombre Cliente (único parámetro requerido por esta plantilla en Meta)
            $whatsappService->sendTemplate(
                $cita->cliente->telefono,
                'confirmacion_cita_informativa',
                'es_MX',
                [
                    $cita->cliente->nombre_razon_social ?? 'Cliente',
                ]
            );

            $cita->update([
                'whatsapp_recepcion_enviado' => true,
                'whatsapp_recepcion_at' => now(),
            ]);

            Log::info("WhatsApp de recepción enviado para cita #{$cita->folio}");

        } catch (\Exception $e) {
            Log::error("Error al enviar WhatsApp de recepción: " . $e->getMessage());
        }
    }
}
