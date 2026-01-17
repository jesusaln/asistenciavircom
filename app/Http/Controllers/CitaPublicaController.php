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

        return Inertia::render('Public/AgendarCita', [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre_empresa ?? $empresa->nombre,
                'logo' => $empresa->logo_url ?? null,
                'color_principal' => $empresa->color_principal ?? '#FF6B35',
                'whatsapp' => $empresa->whatsapp,
                'telefono' => $empresa->telefono,
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
            'email' => 'required|email|max:255',

            // Dirección
            'direccion_calle' => 'required|string|max:255',
            'direccion_colonia' => 'required|string|max:255',
            'direccion_cp' => 'nullable|string|max:10',
            'direccion_referencias' => 'nullable|string|max:500',

            // Preferencias de fecha/hora
            'dias_preferidos' => 'required|array|min:1|max:5',
            'dias_preferidos.*' => 'date|after_or_equal:today',
            'horario_preferido' => 'required|string|in:manana,mediodia,tarde,noche',

            // Servicio
            'tipo_servicio' => 'required|string',
            'tipo_equipo' => 'required|string',
            'origen_tienda' => 'required|string',
            'numero_ticket_tienda' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string|max:1000',

            // Aceptación
            'acepta_terminos' => 'required|accepted',
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
                    'email' => $validated['email'] ?? null,
                    'direccion' => $validated['direccion_calle'] . ', ' . $validated['direccion_colonia'],
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
                'tipo_servicio' => $validated['tipo_servicio'],
                'tipo_equipo' => $validated['tipo_equipo'],
                'descripcion' => $validated['descripcion'] ?? '',
                'problema_reportado' => $validated['descripcion'] ?? '',

                // Tienda
                'origen_tienda' => $validated['origen_tienda'],
                'numero_ticket_tienda' => $validated['numero_ticket_tienda'] ?? null,

                // Preferencias
                'dias_preferidos' => $validated['dias_preferidos'],
                'horario_preferido' => $validated['horario_preferido'],

                // Dirección
                'direccion_calle' => $validated['direccion_calle'],
                'direccion_colonia' => $validated['direccion_colonia'],
                'direccion_cp' => $validated['direccion_cp'] ?? null,
                'direccion_referencias' => $validated['direccion_referencias'] ?? null,

                // Seguimiento
                'link_seguimiento' => Str::uuid(),
            ]);

            DB::commit();

            // TODO: Enviar WhatsApp de confirmación de recepción
            // $this->enviarWhatsAppRecepcion($cita);

            return Inertia::render('Public/AgendarCitaExito', [
                'empresa' => [
                    'nombre' => $empresa->nombre_empresa ?? $empresa->nombre,
                    'whatsapp' => $empresa->whatsapp,
                    'color_principal' => $empresa->color_principal ?? '#FF6B35',
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
                'nombre' => $empresa->nombre_empresa ?? $empresa->nombre,
                'logo' => $empresa->logo_url ?? null,
                'color_principal' => $empresa->color_principal ?? '#FF6B35',
                'whatsapp' => $empresa->whatsapp,
                'telefono' => $empresa->telefono,
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
                    'nombre' => $cita->cliente->nombre,
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
        // Por ahora, usar la primera empresa activa
        // TODO: Implementar lógica de multi-tenant por dominio
        return Empresa::first();
    }
}
