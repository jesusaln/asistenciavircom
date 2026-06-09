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
            'subtitulo' => 'Supervisa tu operación en tiempo real y disuade robos antes de que te cuesten dinero',
            'descripcion' => 'Instalamos sistemas de videovigilancia análogos e IP para negocios, bodegas, oficinas y hogares que necesitan control visual real. Diseñamos cobertura útil, configuramos acceso remoto y dejamos el sistema listo para monitorear desde celular, PC o centro de vigilancia.',
            'imagen' => '/images/servicios/cctv-hero.png',
            'imagen_detalle' => '/images/servicios/cctv-detail.png',
            'categoria_id' => 23,
            'color' => 'blue',
            'badge' => 'Seguridad Visual Inteligente',
            'cta_titulo' => 'Solicita una cotización de CCTV',
            'cta_subtitulo' => 'Evaluamos puntos ciegos, accesos y zonas de riesgo para proponerte cámaras donde realmente importan.',
            'cta_final_titulo' => '¿Listo para vigilar tu operación con claridad total?',
            'cta_final_subtitulo' => 'Te ayudamos a definir cámaras, almacenamiento, monitoreo remoto y cobertura real para tu espacio.',
            'metricas' => [
                ['valor' => 'Vista remota', 'label' => 'Control desde celular y escritorio'],
                ['valor' => 'Cobertura útil', 'label' => 'Menos puntos ciegos'],
                ['valor' => 'Evidencia clara', 'label' => 'Grabación y consulta inmediata'],
            ],
            'problemas' => [
                ['titulo' => 'Puntos ciegos en áreas críticas', 'desc' => 'Accesos, cajas, estacionamientos o pasillos quedan sin evidencia útil cuando ocurre un incidente.', 'icon' => 'eye'],
                ['titulo' => 'Cámaras mal ubicadas', 'desc' => 'Se invierte en equipo, pero la imagen no sirve para identificar personas, placas o maniobras.', 'icon' => 'camera'],
                ['titulo' => 'Sin acceso remoto confiable', 'desc' => 'No puedes validar lo que pasa cuando no estás en sitio o fuera del horario laboral.', 'icon' => 'mobile-screen-button'],
                ['titulo' => 'Pérdida de grabaciones', 'desc' => 'Equipos sin respaldo, sin almacenamiento suficiente o sin configuración correcta.', 'icon' => 'server'],
            ],
            'entregables' => [
                'Levantamiento y propuesta de cobertura',
                'Instalación y configuración de cámaras',
                'Acceso remoto en celular y PC',
                'Capacitación básica de uso y consulta',
                'Ajuste de grabación, alertas y almacenamiento',
            ],
            'sectores' => ['Oficinas', 'Bodegas', 'Tiendas', 'Escuelas', 'Casas', 'Puntos de venta'],
            'beneficios' => [
                ['titulo' => 'Monitoreo Remoto', 'desc' => 'Accede a tus cámaras desde cualquier lugar del mundo.', 'icon' => 'mobile-screen-button'],
                ['titulo' => 'Grabación Continua', 'desc' => 'Almacenamiento seguro de eventos importantes.', 'icon' => 'server'],
                ['titulo' => 'Alertas Inteligentes', 'desc' => 'Notificaciones inmediatas ante cualquier movimiento.', 'icon' => 'bell'],
                ['titulo' => 'Visión Nocturna', 'desc' => 'Claridad total incluso en la oscuridad más absoluta.', 'icon' => 'video']
            ]
        ],
        'control-acceso' => [
            'titulo' => 'Control de Accesos y Asistencia',
            'subtitulo' => 'Controla entradas, horarios y zonas restringidas con trazabilidad real',
            'descripcion' => 'Implementamos soluciones biométricas, faciales, con tarjeta y apertura inteligente para empresas que necesitan saber quién entra, cuándo entra y a qué área puede acceder. Reducimos errores operativos y elevamos el control sobre personal, visitantes y áreas sensibles.',
            'imagen' => '/images/servicios/control-acceso-hero.png',
            'imagen_detalle' => '/images/servicios/control-acceso-detail.png',
            'categoria_id' => 30,
            'color' => 'indigo',
            'badge' => 'Accesos, Horarios y Seguridad',
            'cta_titulo' => 'Solicita una propuesta de control de acceso',
            'cta_subtitulo' => 'Te ayudamos a definir la tecnología correcta para puertas, torniquetes, horarios y personal autorizado.',
            'cta_final_titulo' => '¿Listo para tener control real sobre tus accesos?',
            'cta_final_subtitulo' => 'Diseñamos una solución clara para asistencia, entradas restringidas y reporteo diario sin improvisación.',
            'metricas' => [
                ['valor' => 'Entradas registradas', 'label' => 'Trazabilidad completa'],
                ['valor' => 'Horarios claros', 'label' => 'Menos errores operativos'],
                ['valor' => 'Zonas restringidas', 'label' => 'Acceso por perfil o turno'],
            ],
            'problemas' => [
                ['titulo' => 'No sabes quién entró', 'desc' => 'Sin registro confiable es difícil auditar incidentes, retardos o accesos no autorizados.', 'icon' => 'user-lock'],
                ['titulo' => 'Checadas manipulables', 'desc' => 'Los métodos manuales generan suplantación, errores y conflictos con nómina.', 'icon' => 'id-card'],
                ['titulo' => 'Puertas críticas sin control', 'desc' => 'Áreas sensibles siguen operando con llaves o métodos inseguros.', 'icon' => 'shield-halved'],
                ['titulo' => 'Reportes tardíos', 'desc' => 'La información llega tarde y complica la toma de decisiones operativas.', 'icon' => 'file-alt'],
            ],
            'entregables' => [
                'Evaluación de puertas, flujos y horarios',
                'Instalación de equipos biométricos o faciales',
                'Perfiles de acceso por usuario o área',
                'Configuración de reportes y asistencia',
                'Capacitación operativa al personal',
            ],
            'sectores' => ['Oficinas', 'Plantas', 'Consultorios', 'Escuelas', 'Bodegas', 'Corporativos'],
            'beneficios' => [
                ['titulo' => 'Biometría Avanzada', 'desc' => 'Reconocimiento facial y huella dactilar de alta precisión.', 'icon' => 'id-card'],
                ['titulo' => 'Gestión de Horarios', 'desc' => 'Control exacto de retardos, faltas y tiempo extra.', 'icon' => 'clock'],
                ['titulo' => 'Zonas Restringidas', 'desc' => 'Permite el acceso solo a personal autorizado.', 'icon' => 'user-lock'],
                ['titulo' => 'Reportes en Vivo', 'desc' => 'Exporta registros de asistencia de forma instantánea.', 'icon' => 'file-alt']
            ]
        ],
        'alarmas-seguridad' => [
            'titulo' => 'Sistemas de Alarma y Detección',
            'subtitulo' => 'Detecta intrusiones y activa respuesta inmediata antes de que el daño escale',
            'descripcion' => 'Implementamos sistemas de alarma inteligentes conectados a sensores, sirenas, notificaciones y respuesta remota. Diseñamos cobertura para accesos, perímetros y zonas vulnerables, integrando seguridad física con operación diaria.',
            'imagen' => '/images/servicios/alarmas-hero.png',
            'imagen_detalle' => '/images/servicios/alarmas-detail.png',
            'categoria_id' => 7,
            'color' => 'red',
            'badge' => 'Detección y Respuesta',
            'cta_titulo' => 'Solicita una propuesta de alarma',
            'cta_subtitulo' => 'Definimos sensores, zonas y automatizaciones para reducir riesgo en accesos, ventanas y perímetro.',
            'cta_final_titulo' => '¿Listo para reaccionar antes de que ocurra un robo o intrusión?',
            'cta_final_subtitulo' => 'Diseñamos alarmas que sí cubren tus puntos vulnerables y se integran a tu operación.',
            'metricas' => [
                ['valor' => 'Alertas al instante', 'label' => 'Notificación ante eventos críticos'],
                ['valor' => 'Cobertura por zonas', 'label' => 'Perímetros y accesos definidos'],
                ['valor' => 'Integración real', 'label' => 'Sirenas, sensores y cámaras'],
            ],
            'problemas' => [
                ['titulo' => 'Accesos vulnerables', 'desc' => 'Puertas, ventanas o patios quedan sin detección oportuna durante horarios críticos.', 'icon' => 'shield-halved'],
                ['titulo' => 'Respuesta tardía', 'desc' => 'Se detecta el incidente cuando ya ocurrió daño, pérdida o ingreso no autorizado.', 'icon' => 'bell'],
                ['titulo' => 'Sistemas aislados', 'desc' => 'Cámaras y alarmas no trabajan juntas, lo que dificulta validar el evento.', 'icon' => 'link'],
                ['titulo' => 'Instalaciones improvisadas', 'desc' => 'Sensores mal colocados o mal configurados generan falsas alarmas o zonas sin protección.', 'icon' => 'triangle-exclamation'],
            ],
            'entregables' => [
                'Análisis de zonas vulnerables y propuesta',
                'Instalación de sensores, paneles y sirenas',
                'Configuración de usuarios y alertas',
                'Integración con app y monitoreo remoto',
                'Pruebas funcionales y capacitación básica',
            ],
            'sectores' => ['Casas', 'Tiendas', 'Bodegas', 'Oficinas', 'Consultorios', 'Negocios'],
            'beneficios' => [
                ['titulo' => 'Detección Perimetral', 'desc' => 'Sensores que cubren cada punto de entrada.', 'icon' => 'shield-halved'],
                ['titulo' => 'Botón de Pánico', 'desc' => 'Solicitud de ayuda inmediata en caso de emergencia.', 'icon' => 'bell'],
                ['titulo' => 'Integración Total', 'desc' => 'Conecta tu alarma con tus cámaras y luces.', 'icon' => 'sync'],
                ['titulo' => 'Cero Cables', 'desc' => 'Instalaciones inalámbricas limpias y seguras.', 'icon' => 'tower-broadcast']
            ]
        ],
        'punto-de-venta' => [
            'titulo' => 'Sistemas de Punto de Venta (POS)',
            'subtitulo' => 'Vende más rápido, controla inventario y ordena tu operación diaria desde caja',
            'descripcion' => 'Implementamos soluciones de punto de venta para tiendas, restaurantes y comercios que necesitan cobrar con agilidad, controlar existencias y tener visibilidad real de lo que se vende. Integramos hardware, software y flujos operativos para reducir errores en caja.',
            'imagen' => '/images/servicios/pos-hero.png',
            'imagen_detalle' => '/images/servicios/pos-detail.png',
            'categoria_id' => 118,
            'color' => 'emerald',
            'badge' => 'Ventas, Caja e Inventario',
            'cta_titulo' => 'Solicita una propuesta POS',
            'cta_subtitulo' => 'Te ayudamos a definir el equipo y flujo correcto según tu tipo de negocio, tickets y operación.',
            'cta_final_titulo' => '¿Listo para vender más rápido y con mejor control?',
            'cta_final_subtitulo' => 'Configuramos tu punto de venta para que caja, inventario y operación trabajen en sincronía.',
            'metricas' => [
                ['valor' => 'Cobro ágil', 'label' => 'Menos filas y menos errores'],
                ['valor' => 'Inventario visible', 'label' => 'Control por producto y movimiento'],
                ['valor' => 'Operación simple', 'label' => 'Caja, reportes y cortes claros'],
            ],
            'problemas' => [
                ['titulo' => 'Cobros lentos en caja', 'desc' => 'Las filas y errores en cobro afectan la experiencia del cliente y la rotación de ventas.', 'icon' => 'cash-register'],
                ['titulo' => 'Inventario incierto', 'desc' => 'No sabes con precisión qué tienes, qué se vende más o qué te está faltando.', 'icon' => 'boxes'],
                ['titulo' => 'Procesos manuales', 'desc' => 'Notas, cortes y cálculos improvisados hacen más lenta la operación diaria.', 'icon' => 'calculator'],
                ['titulo' => 'Poca visibilidad del negocio', 'desc' => 'Sin reportes claros es difícil tomar decisiones sobre surtido, horarios o sucursales.', 'icon' => 'chart-bar'],
            ],
            'entregables' => [
                'Definición de hardware según giro comercial',
                'Configuración de POS y periféricos',
                'Alta inicial de productos o categorías',
                'Capacitación en caja y reportes',
                'Pruebas de operación y corte',
            ],
            'sectores' => ['Tiendas', 'Restaurantes', 'Farmacias', 'Papelerías', 'Boutiques', 'Minisupers'],
            'beneficios' => [
                ['titulo' => 'Control de Inventarios', 'desc' => 'Sabe exactamente qué tienes y qué te falta.', 'icon' => 'boxes'],
                ['titulo' => 'Ventas más Rápidas', 'desc' => 'Ligeriza filas con equipos de alto rendimiento.', 'icon' => 'cash-register'],
                ['titulo' => 'Gestión de Sucursales', 'desc' => 'Cruza datos de todos tus negocios en un solo lugar.', 'icon' => 'building'],
                ['titulo' => 'Software Amigable', 'desc' => 'Fácil de usar para tus empleados y cajeros.', 'icon' => 'desktop']
            ]
        ],
        'relojes-checadores' => [
            'titulo' => 'Relojes Checadores y Asistencia',
            'subtitulo' => 'Controla asistencia, retardos y jornadas con datos confiables y listos para operar',
            'descripcion' => 'Instalamos relojes checadores modernos con biometría, nube y reporteo para empresas que necesitan ordenar asistencia y reducir errores administrativos. Automatizamos registros y dejamos la información lista para gestión interna o nómina.',
            'imagen' => '/images/servicios/relojes-hero.png',
            'imagen_detalle' => '/images/servicios/relojes-detail.png',
            'categoria_id' => 30,
            'color' => 'amber',
            'badge' => 'Asistencia y Capital Humano',
            'cta_titulo' => 'Solicita una propuesta de checador',
            'cta_subtitulo' => 'Te ayudamos a elegir la solución correcta para cantidad de empleados, turnos y sucursales.',
            'cta_final_titulo' => '¿Listo para dejar atrás el control manual de asistencia?',
            'cta_final_subtitulo' => 'Implementamos una solución clara para checadas, horarios, incidencias y reportes diarios.',
            'metricas' => [
                ['valor' => 'Checadas reales', 'label' => 'Menos suplantación y más control'],
                ['valor' => 'Reportes rápidos', 'label' => 'Información lista para revisión'],
                ['valor' => 'Operación ordenada', 'label' => 'Horarios, retardos e incidencias'],
            ],
            'problemas' => [
                ['titulo' => 'Asistencia manual o imprecisa', 'desc' => 'Los registros manuales generan errores, disputas y poca trazabilidad.', 'icon' => 'clock'],
                ['titulo' => 'Suplantación de checadas', 'desc' => 'Sin biometría o validación real, el control depende de confianza y no de datos.', 'icon' => 'user-check'],
                ['titulo' => 'Reportes lentos', 'desc' => 'Obtener horas, retardos o faltas consume tiempo valioso del equipo administrativo.', 'icon' => 'file-invoice-dollar'],
                ['titulo' => 'Múltiples turnos mal controlados', 'desc' => 'La operación se complica cuando hay sucursales, horarios rotativos o personal móvil.', 'icon' => 'calendar'],
            ],
            'entregables' => [
                'Definición del esquema de asistencia',
                'Instalación y configuración del equipo',
                'Alta de personal y horarios',
                'Reportes base para operación diaria',
                'Capacitación al responsable administrativo',
            ],
            'sectores' => ['Oficinas', 'Maquilas', 'Escuelas', 'Tiendas', 'Restaurantes', 'Corporativos'],
            'beneficios' => [
                ['titulo' => 'Sin Errores', 'desc' => 'Cálculos automáticos de horas laboradas sin errores.', 'icon' => 'calculator'],
                ['titulo' => 'Evita Suplantación', 'desc' => 'Verificación biométrica que asegura identidad.', 'icon' => 'user-check'],
                ['titulo' => 'App Móvil', 'desc' => 'Tus empleados pueden checar desde su zona de trabajo.', 'icon' => 'mobile-screen-button'],
                ['titulo' => 'Exportación Directa', 'desc' => 'Compatible con sistemas de nómina populares.', 'icon' => 'file-invoice-dollar']
            ]
        ],
        'desarrollo-web' => [
            'titulo' => 'Páginas Web y Desarrollo Digital',
            'subtitulo' => 'Convierte visitas en clientes con un sitio que sí proyecta confianza y genera oportunidades',
            'descripcion' => 'Diseñamos y desarrollamos sitios corporativos, landing pages, tiendas en línea y plataformas personalizadas orientadas a resultados. Combinamos presencia visual, estructura comercial y velocidad para que tu negocio venda mejor y se vea más serio en internet.',
            'imagen' => '/images/servicios/desarrollo-web-hero.png',
            'imagen_detalle' => '/images/servicios/desarrollo-web-detail.png',
            'categoria_id' => null,
            'color' => 'sky',
            'badge' => 'Presencia Digital que Convierte',
            'cta_titulo' => 'Solicita una propuesta web',
            'cta_subtitulo' => 'Cuéntanos si buscas presencia, leads, catálogo o e-commerce y te guiamos en el enfoque correcto.',
            'cta_final_titulo' => '¿Listo para que tu sitio realmente te ayude a vender?',
            'cta_final_subtitulo' => 'Diseñamos una presencia digital alineada con tu servicio, tu marca y tu objetivo comercial.',
            'metricas' => [
                ['valor' => 'Sitio profesional', 'label' => 'Más confianza al vender'],
                ['valor' => 'Diseño responsive', 'label' => 'Experiencia sólida en móvil'],
                ['valor' => 'Estructura comercial', 'label' => 'Más claridad para convertir'],
            ],
            'problemas' => [
                ['titulo' => 'Tu negocio no transmite confianza', 'desc' => 'Un sitio viejo o inexistente hace que clientes potenciales duden antes de contactarte.', 'icon' => 'globe'],
                ['titulo' => 'Recibes pocas solicitudes', 'desc' => 'Sin estructura comercial clara, el tráfico no se convierte en mensajes, llamadas o ventas.', 'icon' => 'bullseye'],
                ['titulo' => 'Tu contenido no refleja tu valor', 'desc' => 'La propuesta de valor se pierde cuando la web no comunica beneficios ni casos de uso.', 'icon' => 'blog'],
                ['titulo' => 'Dependes sólo de redes sociales', 'desc' => 'Sin sitio propio pierdes control, posicionamiento y claridad comercial.', 'icon' => 'share-alt'],
            ],
            'entregables' => [
                'Definición de estructura comercial del sitio',
                'Diseño visual alineado a tu marca',
                'Desarrollo responsive y optimizado',
                'CTAs, formularios o WhatsApp integrados',
                'Entrega lista para publicar y crecer',
            ],
            'sectores' => ['Servicios', 'Corporativos', 'Tiendas', 'Restaurantes', 'Consultorios', 'Profesionales'],
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
                    'imagen' => '/images/servicios/portfolio-corporativo.png',
                    'url' => '#'
                ],
                [
                    'nombre' => 'E-commerce Moderno',
                    'tipo' => 'Tienda en Línea',
                    'imagen' => '/images/servicios/portfolio-ecommerce.png',
                    'url' => '#'
                ],
                [
                    'nombre' => 'Dashboard Administrativo',
                    'tipo' => 'Plataforma Web',
                    'imagen' => '/images/servicios/portfolio-dashboard.png',
                    'url' => '#'
                ],
                [
                    'nombre' => 'Landing Page Pro',
                    'tipo' => 'Generación de Leads',
                    'imagen' => '/images/servicios/portfolio-landing.png',
                    'url' => '#'
                ]
            ]
        ],
        'redes-infraestructura' => [
            'titulo' => 'Redes e Infraestructura de Conectividad',
            'subtitulo' => 'Cableado, fibra y Wi-Fi empresarial para eliminar caídas, lentitud y desorden técnico',
            'descripcion' => 'Diseñamos e implementamos redes empresariales listas para operar con estabilidad, velocidad y crecimiento. Desde cableado estructurado Cat6/Cat6A y peinado de racks hasta fibra óptica y Wi-Fi corporativo con cobertura real, entregamos infraestructura documentada y preparada para tu operación diaria.',
            'imagen' => '/img/redes-fibra.png',
            'imagen_detalle' => '/img/redes-fibra.png',
            'categoria_id' => 92,
            'color' => 'blue',
            'badge' => 'Infraestructura TI Empresarial',
            'cta_titulo' => 'Solicita un diagnóstico de red',
            'cta_subtitulo' => 'Te ayudamos a detectar cuellos de botella, puntos ciegos, cableado deficiente y opciones de crecimiento sin improvisación.',
            'cta_final_titulo' => '¿Listo para estabilizar tu red y crecer sin improvisación?',
            'cta_final_subtitulo' => 'Te ayudamos a definir una solución viable, ordenada y pensada para tu operación real: conectividad, expansión, Wi-Fi y estructura física.',
            'metricas' => [
                ['valor' => 'Cat6 / Fibra', 'label' => 'Infraestructura certificable'],
                ['valor' => 'Wi-Fi estable', 'label' => 'Cobertura para operación continua'],
                ['valor' => 'Rack ordenado', 'label' => 'Escalabilidad y mantenimiento simple'],
            ],
            'problemas' => [
                ['titulo' => 'Internet lento o inestable', 'desc' => 'Pérdida de productividad, videollamadas cortadas y sistemas que se caen en horas pico.', 'icon' => 'gauge-high'],
                ['titulo' => 'Cableado desordenado', 'desc' => 'Racks sin etiquetar, nodos improvisados y fallas difíciles de diagnosticar.', 'icon' => 'diagram-project'],
                ['titulo' => 'Cobertura Wi-Fi deficiente', 'desc' => 'Puntos muertos, roaming deficiente y mala experiencia para personal o clientes.', 'icon' => 'wifi'],
                ['titulo' => 'Infraestructura que no crece', 'desc' => 'Cada nueva cámara, punto de venta o equipo genera más caos y más riesgo.', 'icon' => 'triangle-exclamation'],
            ],
            'entregables' => [
                'Levantamiento y diagnóstico del estado actual',
                'Diseño de red y propuesta de crecimiento',
                'Cableado estructurado, peinado y etiquetado',
                'Configuración de switches, APs y enlaces',
                'Documentación clara para mantenimiento futuro',
            ],
            'sectores' => ['Oficinas', 'Bodegas', 'Consultorios', 'Tiendas', 'Restaurantes', 'Escuelas'],
            'beneficios' => [
                ['titulo' => 'Fibra Óptica', 'desc' => 'Fusiones, tendidos y certificaciones de enlaces de alta velocidad.', 'icon' => 'bolt'],
                ['titulo' => 'Cableado Estructurado', 'desc' => 'Peinado de racks, etiquetado y certificación de nodos Cat6/6a.', 'icon' => 'network-wired'],
                ['titulo' => 'Wi-Fi Corporativo', 'desc' => 'Instalación de Access Points con roaming para cobertura total.', 'icon' => 'wifi'],
                ['titulo' => 'Ordenamiento de Racks', 'desc' => 'Limpieza, reordenamiento y documentación de nodos existentes.', 'icon' => 'server']
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
