<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfiguracion;
use App\Models\Producto;
use App\Models\VentaItem;
use App\Models\LandingFaq;
use App\Models\LandingTestimonio;
use App\Models\LandingLogoCliente;
use App\Models\LandingMarcaAutorizada;
use App\Models\LandingProceso;
use App\Models\LandingOferta;
use App\Models\PlanPoliza;
use App\Models\CrmProspecto;
use App\Models\Cliente;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class LandingController extends Controller
{
    public function index()
    {
        $config = EmpresaConfiguracion::getConfig();

        // Obtener productos MÁS VENDIDOS basados en estadísticas reales de ventas
        // Suma de cantidades vendidas en los últimos 90 días
        $masVendidosIds = VentaItem::where('ventable_type', Producto::class)
            ->where('created_at', '>=', now()->subDays(90))
            ->select('ventable_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->groupBy('ventable_id')
            ->orderByDesc('total_vendido')
            ->take(8) // Obtener top 8 para tener margen
            ->pluck('ventable_id')
            ->toArray();

        // Si hay productos vendidos, obtenerlos en orden de ventas
        if (!empty($masVendidosIds)) {
            $destacados = Producto::whereIn('id', $masVendidosIds)
                ->where('estado', 'activo')
                ->get()
                ->sortBy(function ($producto) use ($masVendidosIds) {
                    return array_search($producto->id, $masVendidosIds);
                })
                ->take(4)
                ->map(function ($producto) {
                    $precioSinIva = $producto->precio_venta;
                    $precioConIva = round($precioSinIva * 1.16, 2); // IVA 16%
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'precio' => $precioConIva,
                        'imagen_url' => $producto->imagen ? (str_starts_with($producto->imagen, 'http') ? $producto->imagen : Storage::url($producto->imagen)) : null,
                        'categoria' => $producto->categoria->nombre ?? 'General',
                    ];
                })
                ->values();
        } else {
            // Si no hay ventas, mostrar productos activos recientes como fallback
            $destacados = Producto::where('estado', 'activo')
                ->latest()
                ->take(4)
                ->get()
                ->map(function ($producto) {
                    $precioSinIva = $producto->precio_venta;
                    $precioConIva = round($precioSinIva * 1.16, 2); // IVA 16%
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'precio' => $precioConIva,
                        'imagen_url' => $producto->imagen ? (str_starts_with($producto->imagen, 'http') ? $producto->imagen : Storage::url($producto->imagen)) : null,
                        'categoria' => $producto->categoria->nombre ?? 'General',
                    ];
                });
        }

        // Cargar contenido dinámico de la landing
        $faqs = LandingFaq::activo()->ordenado()->get(['id', 'pregunta', 'respuesta']);
        $testimonios = LandingTestimonio::activo()->ordenado()->get();
        $logosClientes = LandingLogoCliente::activo()->ordenado()->get();
        $marcas = LandingMarcaAutorizada::activo()->ordenado()->get();
        $procesos = LandingProceso::activo()->ordenado()->get();
        $planes = PlanPoliza::activos()->ordenado()->get();

        // Obtener la oferta activa y vigente (solo la primera)
        $ofertaActiva = LandingOferta::activo()->vigente()->ordenado()->first();

        return Inertia::render('Landing/Index', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Mi Empresa',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'color_terciario' => $config->color_terciario ?? '#fbbf24',
                'telefono' => $config->telefono,
                'email' => $config->email,
                'whatsapp' => $config->whatsapp ?? $config->telefono,
                'nombre_comercial_config' => $config->nombre_empresa,
                // Dirección para el mapa de Google
                'direccion' => $config->direccion_completa ?? null,
                'ciudad' => $config->ciudad ?? null,
                'estado' => $config->estado ?? null,
                'codigo_postal' => $config->codigo_postal ?? null,
                // Redes Sociales
                'facebook_url' => $config->facebook_url ?? null,
                'instagram_url' => $config->instagram_url ?? null,
                'twitter_url' => $config->twitter_url ?? null,
                'tiktok_url' => $config->tiktok_url ?? null,
                'youtube_url' => $config->youtube_url ?? null,
                'linkedin_url' => $config->linkedin_url ?? null,
                // Hero Content (Configurable)
                'hero_titulo' => $config->hero_titulo ?? null,
                'hero_subtitulo' => $config->hero_subtitulo ?? null,
                'hero_descripcion' => $config->hero_descripcion ?? null,
                'hero_cta_primario' => $config->hero_cta_primario ?? null,
                'hero_cta_secundario' => $config->hero_cta_secundario ?? null,
                'hero_badge_texto' => $config->hero_badge_texto ?? null,
            ],
            'destacados' => $destacados,
            'faqs' => $faqs,
            'testimonios' => $testimonios,
            'logosClientes' => $logosClientes,
            'marcas' => $marcas,
            'procesos' => $procesos,
            'planes' => $planes,
            'oferta' => $ofertaActiva ? [
                'id' => $ofertaActiva->id,
                'titulo' => $ofertaActiva->titulo,
                'subtitulo' => $ofertaActiva->subtitulo,
                'descripcion' => $ofertaActiva->descripcion,
                'descuento' => $ofertaActiva->descuento_porcentaje,
                'precio_original' => (float) $ofertaActiva->precio_original,
                'precio_oferta' => (float) $ofertaActiva->precio_oferta,
                'caracteristicas' => $ofertaActiva->caracteristicas,
                'fecha_fin' => $ofertaActiva->fecha_fin?->toIso8601String(),
                'tiempo_restante' => $ofertaActiva->tiempo_restante,
            ] : null,
            'canLogin' => \Route::has('login'),
            'canRegister' => \Route::has('register'),
        ]);
    }

    /**
     * Página de Política de Privacidad
     */
    public function privacidad()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Legal/Privacidad', [
            'empresa' => [
                'nombre' => $config->razon_social ?? $config->nombre_empresa ?? 'Empresa',
                'nombre_comercial' => $config->nombre_empresa ?? 'Empresa',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'direccion' => $config->direccion ?? '',
                'ciudad' => $config->ciudad ?? '',
                'estado' => $config->estado ?? '',
                'codigo_postal' => $config->codigo_postal ?? '',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'rfc' => $config->rfc ?? '',
            ],
        ]);
    }

    /**
     * Página de Términos y Condiciones
     */
    public function terminos()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Legal/Terminos', [
            'empresa' => [
                'nombre' => $config->razon_social ?? $config->nombre_empresa ?? 'Empresa',
                'nombre_comercial' => $config->nombre_empresa ?? 'Empresa',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'direccion' => $config->direccion ?? '',
                'ciudad' => $config->ciudad ?? '',
                'estado' => $config->estado ?? '',
                'codigo_postal' => $config->codigo_postal ?? '',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'rfc' => $config->rfc ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
            ],
        ]);
    }

    /**
     * Asesoría de Climatización
     */
    public function asesor()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Public/AsesorClimatizacion', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Mi Empresa',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'color_terciario' => $config->color_terciario ?? '#fbbf24',
                'telefono' => $config->telefono,
                'email' => $config->email,
                'whatsapp' => $config->whatsapp ?? $config->telefono,
            ],
        ]);
    }

    /**
     * Guardar lead desde el Simulador de Climatización
     */
    public function storeLead(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'btu' => 'nullable|numeric',
            'recomendacion' => 'nullable|string',
            'form' => 'nullable|array',
        ]);

        $empresaId = EmpresaResolver::resolveId();

        try {
            return DB::transaction(function () use ($request, $empresaId) {
                $f = $request->form;
                $isPOS = isset($f['tipo_asesor']) && $f['tipo_asesor'] === 'pos';

                if ($isPOS) {
                    $notasDetalladas = "Lead generado desde el CONFIGURADOR POS.\n\n" .
                        "📊 CONFIGURACIÓN:\n" .
                        "- Kit Recomendado: " . ($request->recomendacion ?? 'N/A') . "\n" .
                        "- Software Sugerido: " . ($f['software'] ?? 'Eleventa') . "\n" .
                        "- Puntuación Complejidad: " . ($request->btu ?? 'N/A') . "\n\n" .
                        "🏢 DATOS DEL NEGOCIO:\n" .
                        "- Giro: " . ($f['giro'] ?? 'N/A') . "\n" .
                        "- Volumen Ventas: " . ($f['volumen_ventas'] ?? 'N/A') . "\n" .
                        "- Cajas/Estaciones: " . ($f['sucursales'] ?? '1') . "\n\n" .
                        "🛠️ EQUIPAMIENTO SOLICITADO:\n" .
                        "- PC Completa: " . (($f['necesita_computadora_completa'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Solo CPU: " . (($f['necesita_cpu'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Monitor: " . (($f['necesita_monitor'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Cajón de Dinero: " . (($f['necesita_cajon_dinero'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Impresora Tickets: " . (($f['necesita_impresora_tickets'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Báscula: " . (($f['necesita_bascula'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Lector de Códigos: " . (($f['necesita_lector_codigos'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Etiquetadora: " . (($f['necesita_etiquetadora'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Monitor Touch: " . (($f['necesita_monitor_touch'] ?? false) ? 'Sí' : 'No');
                } else {
                    $notasDetalladas = "Lead generado desde el Simulador de Climatización.\n\n" .
                        "📊 RESULTADOS:\n" .
                        "- BTU Calculados: " . ($request->btu ?? 'N/A') . "\n" .
                        "- Recomendación: " . ($request->recomendacion ?? 'N/A') . "\n\n" .
                        "🏠 DATOS DEL ESPACIO:\n" .
                        "- Área: " . ($f['area'] ?? 'N/A') . " m²\n" .
                        "- Altura: " . ($f['altura'] ?? 'N/A') . " m\n" .
                        "- Zona: " . ($f['zona'] ?? 'N/A') . "\n" .
                        "- Personas: " . ($f['personas'] ?? 'N/A') . "\n" .
                        "- Aparatos: " . ($f['aparatos'] ?? 'N/A') . "\n" .
                        "- Sol Directo al Techo: " . (($f['techo_directo'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Ventanales Grandes: " . (($f['ventanales'] ?? false) ? 'Sí' : 'No') . "\n" .
                        "- Aislamiento: " . ($f['aislamiento'] ?? 'N/A') . "\n" .
                        "- Exposición al Sol: " . ($f['sol'] ?? 'N/A') . "\n\n" .
                        "⚡ REQUERIMIENTOS TÉCNICOS:\n" .
                        "- Voltaje: " . ($f['voltaje'] ?? 'N/A') . "V\n" .
                        "- Función: " . ($f['funcion'] ?? 'N/A');
                }

                // 1. Crear el prospecto en el CRM
                $prospecto = CrmProspecto::create([
                    'empresa_id' => $empresaId,
                    'nombre' => $request->nombre,
                    'telefono' => $request->telefono,
                    'email' => $request->email,
                    'origen' => 'web',
                    'etapa' => 'prospecto',
                    'prioridad' => 'media',
                    'notas' => $notasDetalladas,
                ]);

                // 2. Convertirlo a cliente automáticamente (crea el registro en 'clientes')
                $cliente = $prospecto->convertirACliente();

                return response()->json([
                    'success' => true,
                    'message' => '¡Agenda solicitada con éxito!',
                    'lead_id' => $prospecto->id,
                    'cliente_id' => $cliente->id
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Error guardando lead del simulador: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo.'
            ], 500);
        }
    }

    /**
     * Descargar reporte técnico en PDF
     */
    public function downloadReport(Request $request)
    {
        $infoEmpresa = EmpresaConfiguracion::getInfoEmpresa();
        $config = EmpresaConfiguracion::getConfig();

        // Asegurar que form sea un array
        $form = $request->input('form', []);
        $isPOS = isset($form['tipo_asesor']) && $form['tipo_asesor'] === 'pos';

        $data = [
            'empresa' => [
                'nombre' => $infoEmpresa['nombre'] ?? 'Administrador POS',
                'logo_url' => $infoEmpresa['logo_url'],
                'logo_base64' => $infoEmpresa['logo_base64'],
                'telefono' => $infoEmpresa['telefono'],
                'email' => $infoEmpresa['email'],
                'whatsapp' => $config->whatsapp ?? $infoEmpresa['telefono'],
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'color_terciario' => $config->color_terciario ?? '#fbbf24',
            ],
            'btu' => $request->input('btu', 0),
            'rec' => $request->input('rec', 'N/A'),
            'form' => $form,
            'ahorro' => $request->input('ahorro', 0),
            'fecha' => now()->format('d/m/Y'),
            'is_pos' => $isPOS
        ];

        if (!$isPOS) {
            // Lógica de cálculo eléctrico para AC
            $btu = $data['btu'];
            $voltaje = $form['voltaje'] ?? '220';
            $cable = $voltaje == '110' ? ($btu <= 14000 ? '12 AWG' : '10 AWG') : ($btu <= 14000 ? '14 AWG' : ($btu <= 26000 ? '12 AWG' : '10 AWG'));
            $data['elec_cable'] = $cable;
            $data['elec_breaker'] = $voltaje == '110' ? ($btu <= 14000 ? '1 polo x 20A' : '1 polo x 30A') : ($btu <= 14000 ? '2 polos x 15A' : ($btu <= 19000 ? '2 polos x 20A' : '2 polos x 30A'));
        }

        $pdf = Pdf::loadView('pdf.asesor_reporte', $data);
        return $pdf->stream($isPOS ? 'Propuesta_POS_Personalizada.pdf' : 'Reporte_Tecnico_Climatizacion.pdf');
    }
}
