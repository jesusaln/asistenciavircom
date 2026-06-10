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
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;

class LandingController extends Controller
{
    public function index()
    {
        $config = EmpresaConfiguracion::getConfig();

        // 1. Obtener productos marcados manualmente como destacados (Prioridad 1)
        $tieneDestacado = Schema::hasColumn('productos', 'destacado');

        $destacadosManuales = Producto::with('categoria')
            ->where('estado', 'activo')
            ->when($tieneDestacado, fn ($query) => $query->where('destacado', true))
            ->orderBy('updated_at', 'desc')
            ->take($tieneDestacado ? 8 : 0)
            ->get();

        $destacadosIds = $destacadosManuales->pluck('id')->toArray();
        $finalCollection = $destacadosManuales;
        $limit = 8;

        // 2. Si faltan para llegar a 8, rellenar con MÁS VENDIDOS (Fallback 1)
        if ($finalCollection->count() < $limit) {
            $needed = $limit - $finalCollection->count();
            $masVendidosIds = VentaItem::where('ventable_type', 'producto')
                ->where('created_at', '>=', now()->subDays(180))
                ->whereNotIn('ventable_id', $destacadosIds)
                ->select('ventable_id', DB::raw('SUM(cantidad) as total_vendido'))
                ->groupBy('ventable_id')
                ->orderByDesc('total_vendido')
                ->take($needed)
                ->pluck('ventable_id')
                ->toArray();

            if (!empty($masVendidosIds)) {
                $extras = Producto::with('categoria')
                    ->whereIn('id', $masVendidosIds)
                    ->where('estado', 'activo')
                    ->get();
                $finalCollection = $finalCollection->concat($extras);
                $destacadosIds = $finalCollection->pluck('id')->toArray();
            }
        }

        // 3. Si aún faltan, rellenar con los más recientes (Fallback 2)
        if ($finalCollection->count() < $limit) {
            $needed = $limit - $finalCollection->count();
            $recientes = Producto::with('categoria')
                ->where('estado', 'activo')
                ->whereNotIn('id', $destacadosIds)
                ->latest()
                ->take($needed)
                ->get();
            $finalCollection = $finalCollection->concat($recientes);
        }

        // Mapear resultado final
        $destacados = $finalCollection->map(function ($producto) {
            $precioSinIva = $producto->precio_venta;
            $precioConIva = round($precioSinIva * 1.16, 2);
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio' => $precioConIva,
                'imagen_url' => $producto->imagen ? (str_starts_with($producto->imagen, 'http') ? $producto->imagen : Storage::disk('public')->url($producto->imagen)) : null,
                'categoria' => $producto->categoria->nombre ?? 'General',
                'cva_clave' => $producto->cva_clave,
                'tipo' => $producto->tipo_producto,
                'destacado' => $producto->destacado,
                'unidad_medida' => $producto->unidad_medida,
            ];
        });

        // Cargar contenido dinámico de la landing
        $faqs = LandingFaq::activo()->ordenado()->get(['id', 'pregunta', 'respuesta']);
        $testimonios = LandingTestimonio::activo()->ordenado()->get();
        $logosClientes = LandingLogoCliente::activo()->ordenado()->get();
        $marcas = LandingMarcaAutorizada::activo()->ordenado()->get();
        $procesos = LandingProceso::activo()->ordenado()->get();

        // Mostrar SOLO los planes destacados en la landing (Limitado a 3 como pidió el usuario)
        $planes = PlanPoliza::activos()->destacados()->ordenado()->take(3)->get();

        // Obtener planes de renta destacados
        $rentas = \App\Models\PlanRenta::publicos()->destacados()->ordenado()->take(3)->get();

        // Obtener la oferta activa y vigente (solo la primera)
        $ofertaActiva = LandingOferta::activo()->vigente()->ordenado()->first();

        $page = (str_contains(strtolower(config('app.name')), 'vircom') || str_contains(request()->getHost(), 'vircom'))
            ? 'Landing/VircomIndex'
            : 'Landing/Index';

        return Inertia::render($page, [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Mi Empresa',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
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
                'hero_imagen_url' => $config->hero_imagen_url ?? null,
            ],
            'destacados' => $destacados,
            'faqs' => $faqs,
            'testimonios' => $testimonios,
            'logosClientes' => $logosClientes,
            'marcas' => $marcas,
            'procesos' => $procesos,
            'planes' => $planes,
            'rentas' => $rentas,
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
            'articulosBlog' => \App\Models\BlogPost::publicado()
                ->orderByDesc('publicado_at')
                ->take(6)
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'titulo' => $post->titulo,
                        'extracto' => $post->resumen ?? \Illuminate\Support\Str::limit(strip_tags($post->contenido), 100),
                        'imagen' => $post->imagen_portada_url,
                        'categoria' => $post->categoria ?? 'General',
                        'icono' => '📝', // Icono por defecto
                        'fecha' => $post->publicado_at->isoFormat('D MMM YYYY'),
                        'tiempo_lectura' => $post->tiempo_lectura,
                        'destacado' => false, // Podríamos añadir lógica para esto
                        'slug' => $post->slug,
                    ];
                }),
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
                'color_principal' => $config->color_principal ?? '#FF6B35',
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
     * Página de Reparación de Minisplit (Landing para Anuncios)
     */
    public function reparacion()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Servicios/Reparacion', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
            ],
        ]);
    }

    /**
     * Página de Mantenimiento Preventivo
     */
    public function mantenimiento()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Servicios/Mantenimiento', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
            ],
        ]);
    }

    /**
     * Página de Instalación de Minisplit
     */
    public function instalacion()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Servicios/Instalacion', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
            ],
        ]);
    }

    /**
     * Página de Instalación Mirage Sin Costo (Desde tienda departamental)
     */
    public function instalacionMirage()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Servicios/InstalacionMirage', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
                'razon_social' => $config->razon_social ?? 'Climas del Desierto',
            ],
        ]);
    }

    /**
     * Página de Instalación Básica de 1500
     */
    public function instalacion1500()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Servicios/Instalacion1500', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
                'razon_social' => $config->razon_social ?? 'Climas del Desierto',
            ],
        ]);
    }

    /**
     * Página de Recarga de Gas Refrigerante
     */
    public function gas()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Servicios/RecargaGas', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
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
                'color_principal' => $config->color_principal ?? '#FF6B35',
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
                'color_principal' => $config->color_principal ?? '#FF6B35',
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
                'color_principal' => $config->color_principal ?? '#FF6B35',
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

    /**
     * Página de Eliminación de Datos de Usuario
     */
    public function eliminacion()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Legal/Eliminacion', [
            'empresa' => [
                'nombre' => $config->razon_social ?? $config->nombre_empresa ?? 'Empresa',
                'nombre_comercial' => $config->nombre_empresa ?? 'Empresa',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
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
     * Propuesta exclusiva SGS Hermosillo (Vue Inertia)
     */
    public function propuestaSgs()
    {
        $config = EmpresaConfiguracion::getConfig();
        return Inertia::render('Public/PropuestaSGS', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '6624606840',
                'email' => $config->email ?? 'contacto@climasdeldesierto.com',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '6624606840',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'estado' => $config->estado ?? 'Sonora',
                'direccion' => $config->direccion_completa ?? 'Retorno de los Oratorios #2, Col. Bicentenario',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
            ]
        ]);
    }

    /**
     * Página de aterrizaje premium para el minisplit Life 12+ (Inspirada en BYD)
     */
    public function life12plus()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Landing/Life12Plus', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
            ],
        ]);
    }

    /**
     * Página de aterrizaje premium para el minisplit Magnum 22 Inverter
     */
    public function magnum22()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Landing/Magnum22', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Climas del Desierto',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#FF6B35',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'telefono' => $config->telefono ?? '',
                'email' => $config->email ?? '',
                'whatsapp' => $config->whatsapp ?? $config->telefono ?? '',
                'ciudad' => $config->ciudad ?? 'Hermosillo',
                'facebook_url' => $config->facebook_url ?? '',
                'instagram_url' => $config->instagram_url ?? '',
            ],
        ]);
    }

    /**
     * Página Quienes Somos / Curriculum Empresarial (Vircom)
     */
    public function quienesSomos()
    {
        $config = EmpresaConfiguracion::getConfig();
        $empresaId = $config->empresa_id;

        return Inertia::render('Public/QuienesSomos', [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Mi Empresa',
                'logo_url' => $config->logo_url,
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'color_secundario' => $config->color_secundario ?? '#64748B',
                'color_terciario' => $config->color_terciario ?? '#fbbf24',
                'telefono' => $config->telefono,
                'email' => $config->email,
                'whatsapp' => $config->whatsapp ?? $config->telefono,
                'mision' => $config->mision ?? 'Potenciar la tranquilidad y productividad de las empresas en México a través de soluciones tecnológicas de vanguardia en seguridad electrónica y soporte TI. Nos comprometemos a brindar un servicio cercano, confiable y de excelencia, adaptado a las necesidades específicas del mercado nacional.',
                'vision' => $config->vision ?? 'Ser reconocidos como el socio tecnológico más confiable y visionario de México para finales de esta década. Aspiramos a redefinir los estándares de seguridad y eficiencia operativa en el país, impulsando el crecimiento sostenible de nuestros clientes mediante innovación constante.',
                'valores' => $config->valores ?? ['Excelencia en Servicio', 'Seguridad Total', 'Innovación Continua', 'Integridad y Ética', 'Compromiso Local', 'Pasión por el Cliente'],
            ],
            'logos' => \App\Models\LandingLogoCliente::where('empresa_id', $empresaId)->where('activo', true)->ordenado()->get(),
            'marcas' => \App\Models\LandingMarcaAutorizada::where('empresa_id', $empresaId)->where('activo', true)->ordenado()->get(),
        ]);
    }

    /**
     * Generar PDF del Curriculum Empresarial (Vircom)
     */
    public function curriculumPdf()
    {
        $config = \App\Models\EmpresaConfiguracion::getConfig();
        $infoEmpresa = \App\Models\EmpresaConfiguracion::getInfoEmpresa();
        $empresaId = $config->empresa_id;

        $toBase64 = function ($path) {
            if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
                return null;
            }

            $data = file_get_contents($path);
            if ($data === false) {
                return null;
            }

            $mime = mime_content_type($path) ?: 'application/octet-stream';
            if ($mime === 'image/webp') return null;

            return str_starts_with($mime, 'image/')
                 ? 'data:' . $mime . ';base64,' . base64_encode($data)
                 : null;
        };

        $localStoragePath = storage_path('app/public/landing/curriculum/');
        
        $data = [
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'ASISTENCIA VIRCOM',
                'razon_social' => $config->razon_social ?? 'JESUS ALBERTO LOPEZ NORIEGA',
                'rfc' => $config->rfc ?? 'LONJ880321KMA',
                'curp' => 'LONJ880321HSONRJ02',
                'giro' => 'Servicios de Seguridad Electrónica y Soluciones TI',
                'fundacion' => '2009',
                'trayectoria' => '15 años',
                'cobertura' => 'Nacional con presencia fuerte en el Norte de México (Sonora, Sinaloa, Baja California)',
                'sitio_web' => $config->sitio_web ?? 'www.asistenciavircom.com',
                'email' => $config->email ?? 'jlopez@asistenciavircom.com',
                'telefono' => $config->telefono ?? '6622036840',
                'direccion' => $config->direccion_completa,
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'logo_base64' => $infoEmpresa['logo_base64'],
                'mision' => $config->mision ?? 'Potenciar la tranquilidad y productividad de las empresas en México a través de soluciones tecnológicas de vanguardia en seguridad electrónica y soporte TI.',
                'vision' => $config->vision ?? 'Ser reconocidos como el socio tecnológico más confiable y visionario de México, redefiniendo los estándares de seguridad y eficiencia operativa.',
                'valores' => $config->valores ?? ['Excelencia en Servicio', 'Seguridad Total', 'Innovación Continua', 'Integridad y Ética', 'Compromiso Local', 'Pasión por el Cliente'],
            ],
            'directivo' => [
                'nombre' => 'Jesus Lopez Noriega',
                'puesto' => 'Director General',
                'telefono' => '6622036840',
                'email' => 'jlopez@asistenciavircom.com',
                'foto_base64' => $toBase64(public_path('branding/fotos/jesus_lopez.png')),
            ],
            'certificaciones' => [
                'SAT' => 'Constancia de Situación Fiscal Actualizada',
                'REPSE' => 'Registro de Prestadoras de Servicios Especializados',
                'IMSS' => 'Cumplimiento de Obligaciones de Seguridad Social',
            ],
            'experiencia_top' => [
                'Sector Privado' => 'Carl\'s Jr (Instalaciones y Mantenimiento)',
                'Sector Público' => 'Gobierno del Estado de Sonora (Sindicatura, Secretaría de Hacienda)',
            ],
            'imagenes_servicios' => [
                'seguridad' => $toBase64($localStoragePath . 'servicios_seguridad_premium_1773449576956.png'),
                'pos' => $toBase64($localStoragePath . 'puntos_de_venta_modernos_1773449592399.png'),
                'biometricos' => $toBase64($localStoragePath . 'relojes_checador_biometricos_1773449619368.png'),
                'equipo' => $toBase64($localStoragePath . 'equipo_tecnico_certificado_1773449631797.png'),
                'it' => $toBase64($localStoragePath . 'infraestructura_it_empresarial_premium_1773449646580.png'),
            ],
            'marcas' => \App\Models\LandingMarcaAutorizada::where('empresa_id', $empresaId)->where('activo', true)->ordenado()->get(),
            'logos' => \App\Models\LandingLogoCliente::where('empresa_id', $empresaId)->where('activo', true)->ordenado()->get(),
            'fecha' => now()->format('d/m/Y')
        ];

        $pdf = Pdf::loadView('pdf.curriculum', $data)
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->stream('Curriculum_Empresarial_' . str_replace(' ', '_', $data['empresa']['nombre']) . '.pdf');
    }

    /**
     * Página de Puntos de Venta (Vircom)
     */
    public function puntosVenta()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Public/PuntosVenta', [
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
}
