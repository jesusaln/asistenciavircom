<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$articulos = [
    [
        'titulo' => '¿Recibo de luz muy alto? 5 Razones por las que tu Minisplit gasta de más',
        'slug' => Str::slug('¿Recibo de luz muy alto? 5 Razones por las que tu Minisplit gasta de más'),
        'resumen' => 'Si tu recibo de CFE llegó por las nubes este mes, el culpable podría ser tu aire acondicionado. Te decimos cómo identificar el problema y ahorrar dinero.',
        'contenido' => '
            <p>El verano en el desierto es implacable, y nuestro mejor aliado es el aire acondicionado. Sin embargo, al abrir el recibo de la luz, la sorpresa puede ser desagradable. Si sientes que estás pagando demasiado, aquí te presentamos las 5 razones principales por las que tu equipo está devorando energía:</p>
            
            <h3>1. Filtros Sucios</h3>
            <p>Es la causa número uno. Cuando el filtro está tapado de polvo, el equipo tiene que trabajar el doble para hacer pasar el aire, consumiendo mucha más electricidad.</p>
            
            <h3>2. Falta de Gas Refrigerante</h3>
            <p>Si tu equipo tiene una pequeña fuga, tardará mucho más tiempo en enfriar la habitación, manteniendo el compresor encendido por horas sin descanso.</p>
            
            <h3>3. Mala Capacidad (Tonelaje incorrecto)</h3>
            <p>Instalar un equipo pequeño en un área muy grande hará que este nunca alcance la temperatura programada, funcionando al 100% de su capacidad todo el día.</p>
            
            <h3>4. Puertas y Ventanas con Filtraciones</h3>
            <p>De nada sirve tener un equipo eficiente si el aire frío se escapa. Revisa los sellos de tus ventanas y evita abrir las puertas constantemente.</p>
            
            <h3>5. Tecnología Antigua (On/Off vs Inverter)</h3>
            <p>Los equipos tradicionales consumen un pico de energía enorme cada vez que el compresor arranca. Los equipos Inverter mantienen un flujo constante y ahorran hasta un 60%.</p>
            
            <p><strong>¿Quieres bajar tu recibo?</strong> En Climas del Desierto te ayudamos con mantenimiento profesional o asesoría para renovar tu equipo. <a href="/contacto">Contáctanos hoy.</a></p>
        ',
        'imagen_portada' => 'images/blog/recibo-luz-alto.png',
        'categoria' => 'Ahorro Energético',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => '¿Por qué mi recibo de luz es alto? | Climas del Desierto',
        'meta_descripcion' => 'Descubre las razones por las que tu aire acondicionado consume mucha energía y aprende consejos prácticos para bajar tu recibo de CFE este verano.',
    ],
    [
        'titulo' => 'Modo Dry vs Cool: Cuándo usar cada uno para ahorrar energía',
        'slug' => Str::slug('Modo Dry vs Cool: Cuándo usar cada uno para ahorrar energía'),
        'resumen' => 'Muchos ven el icono de la gota de agua en su control y no saben para qué sirve. Aprende la diferencia y maximiza el confort en tu hogar.',
        'contenido' => '
            <p>Seguramente has notado una pequeña gota de agua (Dry) y un copo de nieve (Cool) en el control remoto de tu minisplit. Aunque ambos enfrían, funcionan de manera muy distinta y usarlos correctamente puede ahorrarte dinero.</p>
            
            <h3>Modo Cool (Copo de Nieve)</h3>
            <p>Es el modo estándar para enfriar. Aquí, el compresor trabaja para bajar la temperatura hasta el nivel que programaste. Es ideal para los días de calor intenso en Hermosillo.</p>
            
            <h3>Modo Dry (Gota de Agua)</h3>
            <p>Este es el modo de deshumidificación. Su función principal no es bajar la temperatura, sino eliminar la humedad del aire. Cuando el aire es más seco, el cuerpo siente menos calor incluso a la misma temperatura.</p>
            
            <h3>¿Cuándo usar cada uno?</h3>
            <ul>
                <li><strong>Usa COOL:</strong> Cuando el calor sea extremo y necesites bajar la temperatura rápidamente.</li>
                <li><strong>Usa DRY:</strong> En días lluviosos o con mucha humedad ambiental. También es excelente para las noches, ya que mantiene un ambiente fresco sin congelar la habitación, ahorrando mucha energía.</li>
            </ul>
            
            <p>Aprender a usar las funciones de tu equipo es el primer paso para un hogar inteligente y eficiente. ¡Pruébalo hoy!</p>
        ',
        'imagen_portada' => 'images/blog/modo-dry-vs-cool.png',
        'categoria' => 'Tutoriales',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Diferencia entre Modo Dry y Cool en Minisplit',
        'meta_descripcion' => '¿Para qué sirve el modo Dry de mi aire acondicionado? Aprende cuándo usar la función de deshumidificación para ahorrar energía y mejorar tu confort.',
    ],
    [
        'titulo' => 'Alergias en Hermosillo: Cómo tu Minisplit puede ser tu aliado o enemigo',
        'slug' => Str::slug('Alergias en Hermosillo: Cómo tu Minisplit puede ser tu aliado o enemigo'),
        'resumen' => 'El polvo del desierto y el polen pueden afectar tu salud. Mantener tus filtros limpios es vital para respirar un aire puro en interiores.',
        'contenido' => '
            <p>Hermosillo es conocido por su polvo y sus constantes cambios de clima que disparan las alergias. Lo que muchos no saben es que pasamos el 90% de nuestro tiempo en interiores, donde el aire puede estar hasta 5 veces más contaminado que afuera si no cuidamos nuestro sistema de climatización.</p>
            
            <h3>El ciclo del polvo</h3>
            <p>Tu minisplit funciona recirculando el aire de la habitación. Si los filtros están sucios, el equipo no solo deja de enfriar, sino que se convierte en un ventilador de ácaros, moho y polen que recirculan una y otra vez por tus pulmones.</p>
            
            <h3>¿Cómo prevenir síntomas de alergia?</h3>
            <ul>
                <li><strong>Limpia tus filtros cada 15 días:</strong> Es una tarea sencilla que puedes hacer tú mismo con agua y jabón suave.</li>
                <li><strong>Mantenimiento Químico Semestral:</strong> Los técnicos profesionales deben limpiar el serpentín interno donde se acumula moho que tú no puedes ver.</li>
                <li><strong>Usa equipos con filtros HEPA o Ionizadores:</strong> Muchas líneas modernas de Mirage incluyen filtros especiales para atrapar partículas microscópicas.</li>
            </ul>
            
            <p>La salud de tu familia empieza por el aire que respiran. No dejes pasar más tiempo y asegura un ambiente limpio.</p>
        ',
        'imagen_portada' => 'images/blog/alergias-polvo.png',
        'categoria' => 'Salud y Hogar',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Alergias y Aire Acondicionado en Hermosillo',
        'meta_descripcion' => '¿Sufres de alergias al encender tu minisplit? Aprende cómo limpiar tu equipo para eliminar polvo y moho, mejorando la calidad del aire en tu casa.',
    ],
    [
        'titulo' => '¿Vale la pena el Minisplit Inverter? La comparativa definitiva',
        'slug' => Str::slug('¿Vale la pena el Minisplit Inverter? La comparativa definitiva'),
        'resumen' => 'Analizamos el costo-beneficio de la tecnología Inverter frente a los equipos tradicionales On/Off. El ahorro podría ser mayor de lo que crees.',
        'contenido' => '
            <p>Al comprar un aire acondicionado, la pregunta siempre surge: ¿Gasto un poco más en un Inverter o compro uno tradicional? Aquí te sacamos de dudas con números reales.</p>
            
            <h3>¿Cómo funciona la tecnología Inverter?</h3>
            <p>Imagina que vas en un auto. Un equipo convencional va frenando y arrancando a fondo en cada semáforo (gasta mucha gasolina). Un equipo Inverter mantiene una velocidad constante y suave (ahorra mucho combustible).</p>
            
            <h3>Las 3 grandes ventajas:</h3>
            <ol>
                <li><strong>Ahorro Eléctrico:</strong> Hasta un 60% menos consumo en tu recibo de CFE.</li>
                <li><strong>Confort Térmico:</strong> No hay subidas ni bajadas bruscas de temperatura; se mantiene estable.</li>
                <li><strong>Silencio Total:</strong> Al no estar arrancando bruscamente, el equipo es mucho más silencioso dentro y fuera de casa.</li>
            </ol>
            
            <p><strong>Conclusión:</strong> Si vas a usar el aire más de 4 horas al día, el Inverter se paga solo en menos de un año con el ahorro de luz. En Climas del Desierto recomendamos Mirage Inverter por su alta eficiencia.</p>
        ',
        'imagen_portada' => 'images/blog/inverter-vs-onoff.webp',
        'categoria' => 'Guías de compra',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Inverter vs Convencional: ¿Cuál comprar?',
        'meta_descripcion' => 'Comparamos el consumo y eficiencia de los minisplits Inverter contra los tradicionales. Descubre cuánto puedes ahorrar al mes en tu recibo de luz.',
    ],
    [
        'titulo' => '5 Señales de que tu aire acondicionado necesita reparación inmediata',
        'slug' => Str::slug('5 Señales de que tu aire acondicionado necesita reparación inmediata'),
        'resumen' => 'No esperes a que deje de enfriar por completo. Aprende a identificar ruidos, olores y goteos antes de que la reparación sea más costosa.',
        'contenido' => '
            <p>Tu minisplit suele avisar antes de fallar por completo. Ignorar estas señales puede convertir una reparación sencilla en la pérdida total del compresor. Presta atención a estos puntos:</p>
            
            <h3>1. Goteo de agua en la unidad interior</h3>
            <p>El agua debe irse por el drenaje. Si gotea hacia adentro, tu charola está tapada o el equipo tiene una falla de inclinación.</p>
            
            <h3>2. Ruidos extraños (Chirrido o golpeteo)</h3>
            <p>Si escuchas ruidos metálicos, la turbina podría estar suelta o el motor de la unidad exterior está por dañarse.</p>
            
            <h3>3. El aire no sale frío</h3>
            <p>Si el flujo de aire es fuerte pero no está helado, es posible que el compresor no esté arrancando o el capacitor esté dañado.</p>
            
            <h3>4. Malos olores constantes</h3>
            <p>La acumulación de bacterias en el evaporador puede causar olores fétidos. Necesitas una limpieza química profunda.</p>
            
            <h3>5. Pantalla con códigos de error (E1, E5, P4)</h3>
            <p>Cada marca tiene sus códigos. Si ves letras y números parpadeando, apaga el equipo y llama a un técnico para evitar un corto circuito.</p>
            
            <p>En Climas del Desierto somos expertos en diagnóstico rápido. No sufras calor, ¡llámanos!</p>
        ',
        'imagen_portada' => 'images/blog/senales-falla-ac.webp',
        'categoria' => 'Mantenimiento',
        'status' => 'published',
        'publicado_at' => now(),
        'visitas' => 0,
        'meta_titulo' => 'Cómo saber si mi aire acondicionado está fallando',
        'meta_descripcion' => 'Guía para identificar fallas comunes en minisplits: ruidos, goteos y falta de frío. Evita reparaciones costosas con un diagnóstico a tiempo.',
    ],
];

foreach ($articulos as $art) {
    if (!DB::table("blog_posts")->where("slug", $art["slug"])->exists()) {
        DB::table("blog_posts")->insert($art);
        echo "Insertado: " . $art["titulo"] . "\n";
    } else {
        echo "Ya existe: " . $art["titulo"] . "\n";
    }
}
echo "Proceso completado.\n";
