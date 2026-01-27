<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfiguracion;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicServicioController extends Controller
{
    private $serviciosData = [
        'camaras-cctv' => [
            'titulo' => 'Sistemas de Videovigilancia y CCTV',
            'subtitulo' => 'Protección 24/7 para tu hogar y negocio',
            'descripcion' => 'Instalamos tecnología de punta en cámaras de seguridad análogas e IP. Monitorea desde tu celular en tiempo real y protege lo que más quieres con resolución 4K y visión nocturna.',
            'imagen' => 'https://images.unsplash.com/photo-1557597774-9d2739f05a76?q=80&w=2000&auto=format&fit=crop',
            'categoria_id' => 23,
            'color' => 'blue',
            'beneficios' => [
                ['titulo' => 'Monitoreo Remoto', 'desc' => 'Accede a tus cámaras desde cualquier lugar del mundo.', 'icon' => 'mobile-alt'],
                ['titulo' => 'Grabación Continua', 'desc' => 'Almacenamiento seguro de eventos importantes.', 'icon' => 'server'],
                ['titulo' => 'Alertas Inteligentes', 'desc' => 'Notificaciones inmediatas ante cualquier movimiento.', 'icon' => 'bell'],
                ['titulo' => 'Visión Nocturna', 'desc' => 'Claridad total incluso en la oscuridad más absoluta.', 'icon' => 'video']
            ]
        ],
        'control-acceso' => [
            'titulo' => 'Control de Acceso y Asistencia',
            'subtitulo' => 'Gestiona quién entra y sale con precisión',
            'descripcion' => 'Soluciones biométricas, faciales y mediante tarjetas. Ideal para empresas que buscan automatizar el registro de personal y asegurar áreas restringidas.',
            'imagen' => 'https://images.unsplash.com/photo-1517404212328-44a3e36e0530?q=80&w=2000&auto=format&fit=crop',
            'categoria_id' => 30,
            'color' => 'indigo',
            'beneficios' => [
                ['titulo' => 'Biometría Avanzada', 'desc' => 'Reconocimiento facial y huella dactilar de alta precisión.', 'icon' => 'id-card'],
                ['titulo' => 'Gestión de Horarios', 'desc' => 'Control exacto de retardos, faltas y tiempo extra.', 'icon' => 'clock'],
                ['titulo' => 'Zonas Restringidas', 'desc' => 'Permite el acceso solo a personal autorizado.', 'icon' => 'user-lock'],
                ['titulo' => 'Reportes en Vivo', 'desc' => 'Exporta registros de asistencia de forma instantánea.', 'icon' => 'file-alt']
            ]
        ],
        'alarmas-seguridad' => [
            'titulo' => 'Sistemas de Alarma y Detección',
            'subtitulo' => 'Detección temprana de intrusos e incidentes',
            'descripcion' => 'Sistemas de alarma inteligentes vinculados a tu smartphone. Sensores de movimiento, ruptura de cristal y detectores de humo integrados.',
            'imagen' => 'https://images.unsplash.com/photo-1558002038-1055907df827?q=80&w=2000&auto=format&fit=crop',
            'categoria_id' => 7,
            'color' => 'red',
            'beneficios' => [
                ['titulo' => 'Detección Perimetral', 'desc' => 'Sensores que cubren cada punto de entrada.', 'icon' => 'shield-alt'],
                ['titulo' => 'Botón de Pánico', 'desc' => 'Solicitud de ayuda inmediata en caso de emergencia.', 'icon' => 'bell'],
                ['titulo' => 'Integración Total', 'desc' => 'Conecta tu alarma con tus cámaras y luces.', 'icon' => 'sync'],
                ['titulo' => 'Cero Cables', 'desc' => 'Instalaciones inalámbricas limpias y seguras.', 'icon' => 'broadcast-tower']
            ]
        ],
        'punto-de-venta' => [
            'titulo' => 'Sistemas de Punto de Venta (POS)',
            'subtitulo' => 'Transforma tu negocio con tecnología eficiente',
            'descripcion' => 'Equipamiento completo para tiendas, restaurantes y comercios. Básculas, lectores, cajas de dinero y software de administración especializado.',
            'imagen' => 'https://images.unsplash.com/photo-1556742049-13940130059a?q=80&w=2000&auto=format&fit=crop',
            'categoria_id' => 118,
            'color' => 'emerald',
            'beneficios' => [
                ['titulo' => 'Control de Inventarios', 'desc' => 'Sabe exactamente qué tienes y qué te falta.', 'icon' => 'boxes'],
                ['titulo' => 'Ventas más Rápidas', 'desc' => 'Ligeriza filas con equipos de alto rendimiento.', 'icon' => 'cash-register'],
                ['titulo' => 'Gestión de Sucursales', 'desc' => 'Cruza datos de todos tus negocios en un solo lugar.', 'icon' => 'building'],
                ['titulo' => 'Software Amigable', 'desc' => 'Fácil de usar para tus empleados y cajeros.', 'icon' => 'desktop']
            ]
        ],
        'relojes-checadores' => [
            'titulo' => 'Relojes Checadores y Asistencia',
            'subtitulo' => 'Optimiza la administración de tu capital humano',
            'descripcion' => 'Relojes checadores con tecnología en la nube. Olvídate de las tarjetas de cartón y moderniza el registro de jornada laboral.',
            'imagen' => 'https://images.unsplash.com/photo-1579389083078-4e7018379f7e?q=80&w=2000&auto=format&fit=crop',
            'categoria_id' => 30,
            'color' => 'amber',
            'beneficios' => [
                ['titulo' => 'Sin Errores', 'desc' => 'Cálculos automáticos de horas laboradas sin errores.', 'icon' => 'calculator'],
                ['titulo' => 'Evita Suplantación', 'desc' => 'Verificación biométrica que asegura identidad.', 'icon' => 'user-check'],
                ['titulo' => 'App Móvil', 'desc' => 'Tus empleados pueden checar desde su zona de trabajo.', 'icon' => 'mobile-alt'],
                ['titulo' => 'Exportación Directa', 'desc' => 'Compatible con sistemas de nómina populares.', 'icon' => 'file-invoice-dollar']
            ]
        ],
        'desarrollo-web' => [
            'titulo' => 'Páginas Web y Desarrollo Digital',
            'subtitulo' => 'Tu negocio abierto al mundo 24/7',
            'descripcion' => 'Diseñamos y desarrollamos sitios web corporativos, tiendas en línea y plataformas personalizadas que generan ventas y confianza.',
            'imagen' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=2000&auto=format&fit=crop',
            'categoria_id' => null,
            'color' => 'sky',
            'beneficios' => [
                ['titulo' => 'Diseño Responsivo', 'desc' => 'Tu sitio se verá perfecto en móviles y escritorio.', 'icon' => 'laptop'],
                ['titulo' => 'Optimización SEO', 'desc' => 'Aparece en los primeros resultados de Google.', 'icon' => 'search'],
                ['titulo' => 'Velocidad de Carga', 'desc' => 'Plataformas optimizadas para no hacer esperar al cliente.', 'icon' => 'bolt'],
                ['titulo' => 'E-commerce', 'desc' => 'Vende tus productos en línea las 24 horas.', 'icon' => 'shopping-bag']
            ],
            'portafolio' => [
                [
                    'nombre' => 'Portal Corporativo Premium',
                    'tipo' => 'Sitio Web Empresarial',
                    'imagen' => 'https://images.unsplash.com/photo-1467232004584-a241de8bcf5d?q=80&w=800&auto=format&fit=crop',
                    'url' => '#'
                ],
                [
                    'nombre' => 'E-commerce Moderno',
                    'tipo' => 'Tienda en Línea',
                    'imagen' => 'https://images.unsplash.com/photo-1557821552-17105176677c?q=80&w=800&auto=format&fit=crop',
                    'url' => '#'
                ],
                [
                    'nombre' => 'Dashboard Administrativo',
                    'tipo' => 'Plataforma Web',
                    'imagen' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=800&auto=format&fit=crop',
                    'url' => '#'
                ],
                [
                    'nombre' => 'Landing Page Pro',
                    'tipo' => 'Generación de Leads',
                    'imagen' => 'https://images.unsplash.com/photo-1522542550221-31fd19575a2d?q=80&w=800&auto=format&fit=crop',
                    'url' => '#'
                ]
            ]
        ],
    ];

    public function show($slug)
    {
        if (!isset($this->serviciosData[$slug])) {
            abort(404);
        }

        $servicio = $this->serviciosData[$slug];
        $config = EmpresaConfiguracion::getConfig();

        // Obtener productos destacados de la categoría si aplica
        $productos = [];
        if ($servicio['categoria_id']) {
            $productos = Producto::where('categoria_id', $servicio['categoria_id'])
                ->where('estado', 'activo')
                ->take(4)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'nombre' => $p->nombre,
                        'precio' => round($p->precio_venta * 1.16, 2),
                        'imagen' => $p->imagen,
                    ];
                });
        }

        return Inertia::render('Public/Servicios/Show', [
            'servicio' => $servicio,
            'productosDestacados' => $productos,
            'empresa' => [
                'nombre' => $config->nombre_empresa ?? 'Vircom',
                'color_principal' => $config->color_principal ?? '#3B82F6',
                'whatsapp' => $config->whatsapp ?? $config->telefono,
            ]
        ]);
    }
}
