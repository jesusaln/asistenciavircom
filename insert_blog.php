<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$articulos = [
    [
        'titulo' => 'Mantenimiento de Minisplits en Hermosillo: ¿Por qué huele mal mi equipo?',
        'slug' => Str::slug('Mantenimiento de Minisplits en Hermosillo: ¿Por qué huele mal mi equipo?'),
        'resumen' => 'Descubre por qué tu equipo de aire acondicionado despide malos olores al encenderlo y conoce la importancia del mantenimiento preventivo ante el polvo y calor del desierto sonorense.',
        'contenido' => '
            <p>Si al encender tu <strong>minisplit</strong> notas un olor desagradable a humedad o polvo, es momento de actuar. En <strong>Hermosillo y todo Sonora</strong>, las condiciones climáticas extremas —calor seco intenso y polvo del desierto— exigen un cuidado especial para nuestros equipos de climatización.</p>
            <h3>El Cóctel del Desierto: Polvo + Humedad</h3>
            <p>Aunque Hermosillo sea seco, el interior del minisplit condensa agua constantemente. Cuando ese exceso de humedad se mezcla con el polvo que el filtro no pudo atrapar, se crea el ambiente perfecto para la proliferación de moho y bacterias. ¡Ese es el origen del mal olor!</p>
            <h3>¿Qué incluye un Verdadero Mantenimiento Preventivo?</h3>
            <ul>
                <li><strong>Limpieza de filtros y serpentín:</strong> Esencial para un flujo de aire puro.</li>
                <li><strong>Desazolve del drenaje:</strong> Evita goteos molestos dentro de la habitación.</li>
                <li><strong>Revisión de la turbina:</strong> Elimina el moho pegado que reduce la potencia del viento.</li>
                <li><strong>Revisión de presiones y gas refrigerante:</strong> Para asegurar que tu equipo congele como debe.</li>
            </ul>
            <h3>Servicio Técnico Especializado</h3>
            <p>En <strong>Climas del Desierto</strong> contamos con técnicos certificados que realizan limpiezas profundas con hidrolavadora a presión (no solo brochazos). No te arriesgues a respirar aire contaminado ni a elevar tu recibo a causa de forzar tu equipo.</p>
            <p><strong><a href="/contacto">Agenda hoy mismo tu mantenimiento preventivo</a></strong> y disfruta de un aire frío y libre de alergias.</p>
        ',
        'imagen_portada' => 'images/blog/tecnico-mantenimiento.png',
        'categoria' => 'Servicios técnicos',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Mantenimiento de Minisplit en Hermosillo | Climas del Desierto',
        'meta_descripcion' => '¿Tu minisplit huele mal o no enfría? Descubre la importancia del mantenimiento preventivo profesional en Hermosillo para evitar alergias.',
    ],
    [
        'titulo' => '¿Cómo prepararse para el Calor de Sonora? Calculando Toneladas',
        'slug' => Str::slug('¿Cómo prepararse para el Calor de Sonora? Calculando Toneladas'),
        'resumen' => 'Te enseñamos la fórmula exacta para elegir la capacidad correcta de tu minisplit (toneladas o BTUs) y soportar el ardiente verano sin forzar el equipo.',
        'contenido' => '
            <p>El verano en <strong>Sonora</strong> no perdona. Con temperaturas extremas, elegir el aire acondicionado incorrecto puede convertirse en una pesadilla: un equipo que nunca apaga su compresor, no logra enfriar y dispara tu recibo eléctrico.</p>
            <h3>El error más común al comprar un Minisplit</h3>
            <p>Comprar un equipo de 1 tonelada "porque estaba en oferta" para enfriar una sala de 25 metros cuadrados es tirar dinero a la basura. En el clima del desierto, la fórmula necesita ajuste.</p>
            <h3>Nuestra Fórmula Sonorense</h3>
            <ul>
                <li><strong>Cuarto pequeño (0 a 12 m²):</strong> 1 Tonelada (12,000 BTUs). Ideal para recámaras.</li>
                <li><strong>Mediano (13 a 16 m²):</strong> 1.5 Toneladas (18,000 BTUs). Para salas de estar.</li>
                <li><strong>Grande (17 a 25 m²):</strong> 2 Toneladas (24,000 BTUs). Para espacios abiertos.</li>
            </ul>
            <p><em>Nota: Si la habitación recibe el sol de tarde (2 PM a 7 PM), recomendamos subir de capacidad.</em></p>
            <h3>La solución: Compra Seguro</h3>
            <p>En <strong>Climas del Desierto</strong> evaluamos tu espacio, aislamiento y necesidades antes de venderte un equipo.</p>
        ',
        'imagen_portada' => 'images/blog/calor-sonora.png',
        'categoria' => 'Guías de compra',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Calcular Toneladas de Minisplit para Sonora',
        'meta_descripcion' => 'Evita facturas altas. Aprende a calcular cuántas toneladas o BTUs necesita tu minisplit según el tamaño de la habitación en Hermosillo.',
    ],
    [
        'titulo' => 'Minisplits Mirage: ¿Por qué son los reyes del norte de México?',
        'slug' => Str::slug('Minisplits Mirage: ¿Por qué son los reyes del norte de México?'),
        'resumen' => 'Analizamos por qué la marca Mirage domina el mercado de climatización, su durabilidad extrema, tecnología Inverter y relación calidad-precio.',
        'contenido' => '
            <p>Si miras hacia los techos en <strong>Hermosillo o Guaymas</strong>, verás una constante: compresores blancos con el inconfundible logo de <strong>Mirage</strong>. ¿Pero por qué esta marca es el estándar sonorense?</p>
            <h3>1. Diseñados para Tostarse (y seguir funcionando)</h3>
            <p>A diferencia de otras marcas extranjeras que fallan cuando el calor extremo los somete, los equipos Mirage están diseñados para operar sin problemas bajo el fuerte sol. Su tecnología soporta altas presiones sin perder eficiencia.</p>
            <h3>2. El Ahorro de la Tecnología Inverter</h3>
            <p>Al ajustar inteligentemente la velocidad del compresor en lugar de apagarse y prenderse constantemente (como los equipos más antiguos), logran mantener la temperatura deseada, logrando <strong>hasta un 60% de ahorro eléctrico</strong>.</p>
            <h3>3. Refacciones Garantizadas y Rápidas</h3>
            <p>Al ser la marca líder, encontrar un repuesto es cuestión de horas. En <strong>Climas del Desierto</strong> somos distribuidores autorizados y resolveremos cualquier tema en tiempo récord.</p>
            <h3>El Veredicto</h3>
            <p>Si buscas frío intenso con bajo consumo eléctrico, Mirage es la respuesta. <a href="/catalogo">Explora nuestras ofertas</a> y llévate tu equipo hoy mismo.</p>
        ',
        'imagen_portada' => 'images/blog/mirage-minisplit.png',
        'categoria' => 'Reseñas',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Por qué comprar Minisplit Mirage en Sonora',
        'meta_descripcion' => 'Descubre por qué los minisplits Mirage Inverter son la opción más confiable y ahorradora para soportar el verano. Distribuidor Autorizado.',
    ],
];

foreach ($articulos as $art) {
    if (!DB::table("blog_posts")->where("slug", $art["slug"])->exists()) {
        DB::table("blog_posts")->insert($art);
    }
}
echo "OK\n";
